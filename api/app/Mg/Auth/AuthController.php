<?php

namespace Mg\Auth;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Auth endpoints 100% RFC compliant.
 *
 *   POST /oauth/token       (RFC 6749 §3.2 — qualquer grant_type)
 *   POST /oauth/revoke      (RFC 7009)
 *   POST /oauth/introspect  (RFC 7662)
 *
 * Para userinfo veja {@see UserInfoController}.
 * Para authorize/approve/deny os controllers do Passport são usados direto.
 */
class AuthController extends Controller
{
    /**
     * POST /oauth/token — RFC 6749 §3.2.
     *
     * Wrapper do Passport AccessTokenController que adiciona cookies pros
     * grants `password` e `refresh_token` (UX SSO cross-domain). Demais
     * grants (authorization_code, client_credentials) não setam cookies
     * porque são server-to-server.
     */
    public function token(TokenRequest $request, ServerRequestInterface $psrRequest): JsonResponse|Response
    {
        $grant = (string) $request->input('grant_type');

        // Garante que o PSR-7 enxergue todos os campos do form. A ponte
        // Symfony→PSR-7 do Laravel deveria popular ParsedBody a partir do
        // POST bag, mas em alguns pipelines (CSRF check, FormRequest, etc.)
        // o body é consumido antes e ParsedBody chega vazio — League OAuth
        // então estoura `invalid_request`.
        $psrRequest = $psrRequest->withParsedBody($request->all());

        try {
            $payload = AuthService::issueToken($psrRequest);
        } catch (OAuthServerException $e) {
            Log::warning('AuthController::token — OAuthServerException', [
                'grant_type' => $grant,
                'error_type' => $e->getErrorType(),
                'message' => $e->getMessage(),
                'hint' => method_exists($e, 'getHint') ? $e->getHint() : null,
            ]);
            return $this->oauthErrorResponse($e);
        } catch (Exception $e) {
            Log::error('AuthController::token — erro inesperado ao emitir token', [
                'grant_type' => $grant,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'server_error',
                'error_description' => 'Erro inesperado ao emitir token.',
            ], 500);
        }

        $response = response()->json($payload);

        if ($grant === 'password') {
            $userId = AuthService::findUsuarioIdByUsername($request->input('username'));
            $response = AuthService::applyAuthCookies(
                $response,
                $payload['access_token'],
                $userId,
                (int) ($payload['expires_in'] / 60),
            );
        } elseif ($grant === 'refresh_token') {
            // Não sabemos o user_id direto do refresh; deixamos cookie user_id intacto.
            $response = AuthService::applyAuthCookies(
                $response,
                $payload['access_token'],
                null,
                (int) ($payload['expires_in'] / 60),
            );
        }

        return $response;
    }

    /**
     * POST /oauth/revoke — RFC 7009.
     *
     * Sempre retorna 200 (§2.2), mesmo se token não existir, exceto:
     *  - 400 se body inválido (RevokeRequest handle)
     *  - 401 se client_credentials inválidas
     */
    public function revoke(RevokeRequest $request): Response
    {
        [$clientId, $clientSecret] = AuthService::extractClientCredentials($request);
        if (!AuthService::validateClient($clientId, $clientSecret)) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $token = AuthService::findTokenById(
            $request->input('token'),
            $request->input('token_type_hint'),
        );

        if ($token instanceof Token) {
            // Revoga o access_token + refresh_tokens derivados (cascata via Passport)
            $token->revoke();
            RefreshToken::where('access_token_id', $token->id)->update(['revoked' => true]);
        } elseif ($token instanceof RefreshToken) {
            // Revoga o refresh + access_token associado
            $token->revoke();
            $accessToken = Token::find($token->access_token_id);
            $accessToken?->revoke();
        }
        // Token desconhecido/já revogado/expirado: silent 200 per spec §2.2

        return response()->noContent(200);
    }

    /**
     * POST /oauth/introspect — RFC 7662.
     *
     * Sempre 200 com `{active: bool, ...}` exceto:
     *  - 400 se body inválido (IntrospectRequest handle)
     *  - 401 se client_credentials inválidas
     */
    public function introspect(IntrospectRequest $request): JsonResponse
    {
        [$clientId, $clientSecret] = AuthService::extractClientCredentials($request);
        if (!AuthService::validateClient($clientId, $clientSecret)) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $token = AuthService::findTokenById(
            $request->input('token'),
            $request->input('token_type_hint'),
        );

        if (!$token) {
            return response()->json(['active' => false]);
        }

        if ($token instanceof Token) {
            return response()->json([
                'active' => true,
                'scope' => implode(' ', $token->scopes ?? []),
                'client_id' => $token->client_id,
                'username' => $token->user?->usuario,
                'token_type' => 'Bearer',
                'exp' => $token->expires_at?->timestamp,
                'iat' => $token->created_at?->timestamp,
                'sub' => $token->user_id === null ? null : (string) $token->user_id,
            ]);
        }

        // RefreshToken — sem expires_at acessível direto, mas spec aceita campos opcionais
        $accessToken = Token::find($token->access_token_id);
        return response()->json([
            'active' => true,
            'scope' => $accessToken ? implode(' ', $accessToken->scopes ?? []) : null,
            'client_id' => $accessToken?->client_id,
            'username' => $accessToken?->user?->usuario,
            'token_type' => 'refresh_token',
            'exp' => $token->expires_at?->timestamp,
            'sub' => $accessToken?->user_id === null ? null : (string) $accessToken?->user_id,
        ]);
    }

    private function oauthErrorResponse(OAuthServerException $e): JsonResponse
    {
        $code = $e->getCode();
        if ($code < 400 || $code >= 600) {
            $code = 400;
        }
        // Mensagem genérica spec-compliant — a OAuthServerException do Passport
        // já tem mensagens próprias; expomos pelo getMessage().
        return response()->json([
            'error' => $code === 401 ? 'invalid_client' : 'invalid_request',
            'error_description' => $e->getMessage() ?: null,
        ], $code);
    }
}
