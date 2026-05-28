<?php

namespace Mg\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * POST /oauth/introspect (RFC 7662).
 *
 * Spec exige `token` (REQUIRED) e `token_type_hint` opcional.
 * Client credentials extraídos no controller via AuthService.
 */
class IntrospectRequest extends FormRequest
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
