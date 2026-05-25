<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Porta direta do AuthController do MGAuth.
 *
 * Endpoints (paths reais quando expostos via routes/api.php):
 *   POST /api/oauth/token         — getToken (form login + redirect c/ cookie)
 *   POST /api/oauth/token/json    — getTokenJson (login JSON)
 *   POST /api/refresh             — refreshToken
 *   GET  /api/check-token         — checkToken (validação via Bearer)
 *   POST /api/logout              — logout (revoga tokens + limpa cookies)
 *
 * Mantém o MESMO contrato HTTP do MGAuth (mesmos paths, mesmos payloads,
 * mesmos cookies em .mgpapelaria.com.br) — MGLara, MGsis e os 4 frontends
 * Quasar continuam funcionando trocando só AUTH_API_URL.
 */
class AuthController extends Controller
{
    public function getToken(ServerRequestInterface $request): RedirectResponse
    {
        $body = $request->getParsedBody();
        $redirectUri = $body['redirect_uri'] ?? config('services.auth.default_redirect');

        $validate = validator($body, [
            'username' => 'required',
            'password' => 'required',
            'redirect_uri' => 'required',
        ]);

        if ($validate->fails()) {
            return redirect('/login?redirect_uri=' . urlencode($redirectUri) . '&error=true');
        }

        unset($body['_token'], $body['redirect_uri']);

        $updatedRequest = $request->withParsedBody(array_merge($body, [
            'client_id' => config('services.auth.client_id'),
            'client_secret' => config('services.auth.client_secret'),
            'grant_type' => 'password',
        ]));

        $usuarioId = $this->findUsuarioIdByUsername($body['username'] ?? '');

        try {
            $response = $this->issueToken($updatedRequest);
            $payload = json_decode((string) $response->getContent(), true);

            return $this->withAuthCookies(
                redirect()->to($redirectUri),
                $payload['access_token'],
                $usuarioId,
                (int) ($payload['expires_in'] / 60),
            );
        } catch (Exception $e) {
            return redirect('/login?redirect_uri=' . urlencode($redirectUri) . '&error=true');
        }
    }

    public function getTokenJson(ServerRequestInterface $request): JsonResponse|Response
    {
        $body = $request->getParsedBody();

        $validate = validator($body, [
            'grant_type' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), 400);
        }

        $usuarioId = $this->findUsuarioIdByUsername($body['username'] ?? '');

        try {
            $response = $this->issueToken($request);
            $payload = json_decode((string) $response->getContent(), true);

            return $this->withAuthCookies(
                response()->json($payload),
                $payload['access_token'],
                $usuarioId,
                (int) ($payload['expires_in'] / 60),
            );
        } catch (OAuthServerException $e) {
            return response()->json(['message' => 'Your credentials are incorrect. Please try again'], 401);
        } catch (Exception $e) {
            if ($e->getCode() === 400) {
                return response()->json(['message' => 'Invalid request. Please enter a username or a password.'], 400);
            }
            if ($e->getCode() === 401) {
                return response()->json(['message' => 'Your credentials are incorrect. Please try again'], 401);
            }
            return response()->json(['message' => 'Something went wrong on the server.'], 500);
        }
    }

    public function refreshToken(ServerRequestInterface $request): JsonResponse|Response
    {
        $body = $request->getParsedBody();

        $validate = validator($body, [
            'refresh_token' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
            'grant_type' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), 400);
        }

        try {
            $response = $this->issueToken($request);
            $payload = json_decode((string) $response->getContent(), true);

            return $this->withAuthCookies(
                response()->json($payload),
                $payload['access_token'],
                null,
                (int) ($payload['expires_in'] / 60),
            );
        } catch (OAuthServerException $e) {
            return response()->json(['message' => 'Your credentials are incorrect. Please try again'], 401);
        } catch (Exception $e) {
            if ($e->getCode() === 400) {
                return response()->json(['message' => 'Invalid request. Please enter a username or a password.'], 400);
            }
            if ($e->getCode() === 401) {
                return response()->json(['message' => 'Your credentials are incorrect. Please try again'], 401);
            }
            return response()->json(['message' => 'Something went wrong on the server.'], 500);
        }
    }

    public function checkToken(Request $request): JsonResponse
    {
        // checkToken não tem middleware auth:api (queremos resposta 401 custom).
        // Resolvemos o user explicitamente via guard 'api' (Passport).
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario nao logado!',
                'expires_in' => null,
                'user_id' => null,
                'usuario' => null,
            ], 401);
        }

        $token = $user->token();
        $expiresIn = $token && $token->expires_at
            ? now()->diffInSeconds($token->expires_at, false)
            : null;

        return response()->json([
            'message' => 'Token is valid',
            'expires_in' => $expiresIn,
            'user_id' => $user->codusuario,
            'usuario' => $user->usuario,
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->tokens->each(function ($token): void {
                $token->delete();
            });
        }

        return $this->withClearAuthCookies(
            response()->json('Logged out successfully', 200),
        );
    }

    private function findUsuarioIdByUsername(string $username): ?int
    {
        if ($username === '') {
            return null;
        }
        $usuario = Usuario::where('usuario', $username)->first();
        return $usuario?->codusuario;
    }

    /**
     * Emite o access token via Passport. No Passport 13, `issueToken()`
     * passou a exigir 2 args: o ServerRequestInterface PSR-7 e uma
     * ResponseInterface PSR-7 vazia (que o servidor OAuth preenche).
     *
     * Usa GuzzleHttp\Psr7\HttpFactory direto: o Passport 13 traz
     * guzzlehttp/psr7 como dep mas não registra a interface
     * ResponseFactoryInterface no container do Laravel.
     */
    private function issueToken(ServerRequestInterface $request): \Symfony\Component\HttpFoundation\Response
    {
        $psrResponse = (new HttpFactory())->createResponse();
        return app(AccessTokenController::class)->issueToken($request, $psrResponse);
    }

    /**
     * @template T of \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @param T $response
     * @return T
     */
    private function withAuthCookies($response, string $accessToken, ?int $userId, int $minutes)
    {
        $domain = config('services.auth.cookie_domain');
        $secure = (bool) config('services.auth.cookie_secure');
        $sameSite = config('services.auth.cookie_same_site');

        return $response
            ->cookie('access_token', $accessToken, $minutes, '/', $domain, $secure, false, false, $sameSite)
            ->cookie('user_id', (string) ($userId ?? ''), $minutes, '/', $domain, $secure, false, false, $sameSite);
    }

    /**
     * @template T of \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     * @param T $response
     * @return T
     */
    private function withClearAuthCookies($response)
    {
        $domain = config('services.auth.cookie_domain');
        $secure = (bool) config('services.auth.cookie_secure');
        $sameSite = config('services.auth.cookie_same_site');

        return $response
            ->cookie('access_token', '', -1, '/', $domain, $secure, false, false, $sameSite)
            ->cookie('user_id', '', -1, '/', $domain, $secure, false, false, $sameSite);
    }
}
