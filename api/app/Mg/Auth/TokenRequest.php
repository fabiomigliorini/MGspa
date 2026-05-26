<?php

namespace Mg\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /oauth/token (RFC 6749 §3.2).
 *
 * Só valida grant_type — demais campos (client_id, client_secret, username,
 * password, refresh_token, code, etc.) são validados pelo Passport conforme
 * o grant solicitado.
 */
class TokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grant_type' => 'required|string',
        ];
    }
}
