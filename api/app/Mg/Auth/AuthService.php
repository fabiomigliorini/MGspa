<?php

namespace Mg\Auth;

use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\UnencryptedToken;
use Laravel\Passport\Bridge\ClientRepository;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Passport;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Mg\Usuario\Usuario;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Serviço utilitário do Auth — wrappers do Passport + manipulação de cookies +
 * helpers spec-compliant pra revoke/introspect (RFC 7009 / RFC 7662).
 *
 * Estático por convenção do projeto (FeriadoService et al.). Dependências do
 * container resolvidas via app() onde necessário.
 */
class AuthService
{
    /**
     * Emite token via Passport AccessTokenController.
     * Passport 13 exige PSR-7 Request E ResponseInterface vazia.
     * Usamos GuzzleHttp\Psr7\HttpFactory porque o Passport traz guzzlehttp/psr7
     * como dep mas não registra ResponseFactoryInterface no container.
     */
    public static function issueToken(ServerRequestInterface $request): array
    {
        $psrResponse = (new HttpFactory())->createResponse();
        $response = app(AccessTokenController::class)->issueToken($request, $psrResponse);
        return json_decode((string) $response->getContent(), true);
    }

    public static function findUsuarioIdByUsername(?string $username): ?int
    {
        if (empty($username)) {
            return null;
        }
        return Usuario::where('usuario', $username)->value('codusuario');
    }

    /**
     * Aplica os cookies access_token e (opcionalmente) user_id na resposta.
     *
     * CRÍTICO: se $userId === null, NÃO toca no cookie user_id — paridade
     * com o comportamento original do MGAuth pro refresh_token grant
     * (não sobrescrever user_id com string vazia).
     *
     * @template T of JsonResponse|RedirectResponse|Response
     * @param T $response
     * @return T
     */
    public static function applyAuthCookies($response, string $accessToken, ?int $userId, int $minutes)
    {
        $domain = config('services.auth.cookie_domain');
        $secure = (bool) config('services.auth.cookie_secure');
        $sameSite = config('services.auth.cookie_same_site');

        $response = $response->cookie(
            'access_token', $accessToken, $minutes, '/', $domain, $secure, false, false, $sameSite
        );

        if ($userId !== null) {
            $response = $response->cookie(
                'user_id', (string) $userId, $minutes, '/', $domain, $secure, false, false, $sameSite
            );
        }

        return $response;
    }

    /**
     * @template T of JsonResponse|Response
     * @param T $response
     * @return T
     */
    public static function clearAuthCookies($response)
    {
        $domain = config('services.auth.cookie_domain');
        $secure = (bool) config('services.auth.cookie_secure');
        $sameSite = config('services.auth.cookie_same_site');

        return $response
            ->cookie('access_token', '', -1, '/', $domain, $secure, false, false, $sameSite)
            ->cookie('user_id', '', -1, '/', $domain, $secure, false, false, $sameSite);
    }

    public static function revokeAllTokensFor($user): void
    {
        if (!$user) {
            return;
        }
        $user->tokens->each(fn(Token $token) => $token->revoke());
    }

    /**
     * Extrai client_id/client_secret da request conforme RFC 6749 §2.3.1.
     * Aceita Basic Auth header (preferred) OU body form-urlencoded.
     *
     * @return array{0: ?string, 1: ?string} [clientId, clientSecret]
     */
    public static function extractClientCredentials(Request $request): array
    {
        // 1. Basic Auth header (preferred per RFC 6749 §2.3.1)
        $authHeader = $request->header('Authorization', '');
        if (is_string($authHeader) && stripos($authHeader, 'Basic ') === 0) {
            $decoded = base64_decode(substr($authHeader, 6), true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$id, $secret] = explode(':', $decoded, 2);
                return [$id ?: null, $secret ?: null];
            }
        }

        // 2. Body fallback
        return [
            $request->input('client_id'),
            $request->input('client_secret'),
        ];
    }

    /**
     * Valida client_credentials usando o ClientRepository do Passport
     * (que via container resolve pro PlainOrHashedClientRepository nosso,
     * compat com secrets plain text legados no mgsis.oauth_clients).
     */
    public static function validateClient(?string $clientId, ?string $clientSecret): bool
    {
        if (empty($clientId) || empty($clientSecret)) {
            return false;
        }
        /** @var ClientRepository $repo */
        $repo = app(ClientRepository::class);
        // Passport ClientRepository requer grantType, mas pro revoke/introspect
        // não validamos grant — passamos null que o nosso PlainOrHashedClientRepository
        // ignora (só compara secret).
        return $repo->validateClient($clientId, $clientSecret, null);
    }

    /**
     * Resolve um token (access ou refresh) a partir do JWT string ou do ID raw.
     *
     * Para access_token: Passport 13 emite JWT — extraímos o `jti` claim,
     * que é o PK em oauth_access_tokens.
     * Para refresh_token: é uma string opaca encriptada que serve como PK
     * em oauth_refresh_tokens (após decrypt via Crypto::decryptRefreshToken
     * — mas Passport não expõe; usamos lookup direto no banco também aceita
     * o ID raw se o cliente passar `token_type_hint=refresh_token`).
     *
     * Conforme RFC 7009 §1.1 e RFC 7662 §2.1, se token_type_hint estiver
     * errado, devemos tentar o outro tipo antes de desistir.
     *
     * @return Token|RefreshToken|null
     */
    public static function findTokenById(string $tokenString, ?string $hint = null)
    {
        $tryAccess = function () use ($tokenString): ?Token {
            $jti = self::parseJwtJti($tokenString);
            if ($jti === null) {
                return null;
            }
            $token = Token::find($jti);
            if (!$token || $token->revoked) {
                return null;
            }
            if ($token->expires_at && $token->expires_at->isPast()) {
                return null;
            }
            return $token;
        };

        $tryRefresh = function () use ($tokenString): ?RefreshToken {
            // Refresh tokens podem chegar como JWT (raros) ou string opaca.
            // O ID do refresh é uma string sha256-like de 80 chars; o JWT do
            // access_token contém `jti` mas refresh_tokens não. Tentamos lookup direto.
            $refresh = RefreshToken::find($tokenString);
            if (!$refresh || $refresh->revoked) {
                return null;
            }
            if ($refresh->expires_at && $refresh->expires_at->isPast()) {
                return null;
            }
            return $refresh;
        };

        if ($hint === 'refresh_token') {
            return $tryRefresh() ?? $tryAccess();
        }
        // default ou hint=access_token
        return $tryAccess() ?? $tryRefresh();
    }

    /**
     * Parseia JWT sem validar assinatura e extrai o `jti` claim.
     * Retorna null se a string não for JWT válido.
     */
    private static function parseJwtJti(string $jwt): ?string
    {
        try {
            $config = Configuration::forSymmetricSigner(
                new Sha256(),
                // Chave dummy — não vamos validar assinatura, só parsear
                InMemory::plainText(str_repeat('a', 32)),
            );
            $parsed = $config->parser()->parse($jwt);
            if (!$parsed instanceof UnencryptedToken) {
                return null;
            }
            return $parsed->claims()->get('jti');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
