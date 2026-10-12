<?php

namespace App\Http\Requests\Mg\Grao;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Mg\Pessoa\Pessoa;

/**
 * Cadastro do motorista feito de dentro do modal de Operação do pátio. Mais
 * exigente que a compra na loja: sem telefone e endereço não cadastra.
 */
class CargaMotoristaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->cpf),
            'telefone' => preg_replace('/\D/', '', (string) $this->telefone),
            'cep' => preg_replace('/\D/', '', (string) $this->cep),
            'nome' => trim(preg_replace('/\s+/', ' ', (string) $this->nome)),
        ]);
    }

    public function rules()
    {
        return [
            'cpf' => [
                'required',
                'digits:11',
                'cpf_cnpj',
                function (string $attribute, mixed $value, Closure $fail) {
                    $existe = Pessoa::where('fisica', true)->where('cnpj', (int) $value)->first();
                    if ($existe) {
                        $fail("CPF já cadastrado: {$existe->fantasia} (#{$existe->codpessoa}).");
                    }
                },
            ],
            'nome' => ['required', 'string', 'min:5', 'max:100'],
            // Celular: DDD + número, 11 dígitos.
            'telefone' => ['required', 'digits:11'],
            'cep' => ['required', 'digits:8'],
            // Rua, número e complemento num campo só (como o pátio pede).
            'endereco' => ['required', 'string', 'max:100'],
            'bairro' => ['required', 'string', 'max:50'],
            'codcidade' => ['required', 'exists:tblcidade,codcidade'],
        ];
    }
}
