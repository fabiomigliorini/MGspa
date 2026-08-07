<?php

namespace Mg\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Página HTML de login (UX, não faz parte da spec OAuth2/OIDC).
 *
 * Era uma closure em routes/web.php. Virou controller porque closure não é
 * serializável e bloqueia o `php artisan route:cache`.
 */
class LoginController extends Controller
{
    /**
     * Se já tem cookie access_token válido, redireciona pra redirect_uri.
     * Senão renderiza o form que via JavaScript POSTa em /oauth/token.
     */
    public function show(Request $request)
    {
        $accessToken = $request->cookie('access_token');

        if ($accessToken) {
            $request->headers->set('Authorization', 'Bearer ' . $accessToken);
            if (Auth::guard('api')->check()) {
                return redirect()->to(
                    $request->query('redirect_uri') ?? config('services.auth.default_redirect')
                );
            }
        }

        return view('login', [
            'redirect_uri' => $request->query('redirect_uri'),
            'error' => $request->boolean('error'),
        ]);
    }
}
