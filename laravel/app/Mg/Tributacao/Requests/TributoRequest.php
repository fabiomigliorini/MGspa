<?php

namespace Mg\Tributacao\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TributoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('tributo'); // pega o ID na rota (PUT)

        return [
            'codigo' => [
                'required',
                'string',
                'max:10',
                Rule::unique('tbltributo')
                    ->where(fn ($q) => $q->where('ente', $this->ente))
                    ->ignore($id, 'codtributo'),
            ],
            'descricao' => 'required|string|max:100',
            'ente'      => 'required|in:FEDERAL,ESTADUAL,MUNICIPAL',
        ];
    }

    /**
     * Mensagens mais amigáveis
     */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'Já existe um tributo com este código para este ente.',
        ];
    }
}
