<?php

namespace Mg\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * POST /oauth/revoke (RFC 7009).
 *
 * Spec exige `token` (REQUIRED) e `token_type_hint` opcional.
 * Client credentials são extraídos no controller via AuthService.
 *
 * Em caso de validation fail, retorna 400 com `{"error": "invalid_request"}`
 * conforme RFC 7009 §2.2.1.
 */
class RevokeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'token_type_hint' => 'sometimes|string|in:access_token,refresh_token',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['error' => 'invalid_request'], 400)
        );
    }
}
