<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação de forma do POST de recarga. As regras de NEGÓCIO (saldo, empresa,
 * período) ficam em BeeRecargaService::linhasSolicitadas, porque só valem se
 * lidas dentro do lock da transação — aqui não haveria como garantir isso.
 *
 * `itens` ausente = lote do mês (todo mundo com saldo, pelo saldo cheio).
 * `itens` presente = lote avulso (só os informados, com o valor informado).
 */
class GerarRecargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codempresa' => 'required|integer|exists:tblempresa,codempresa',
            'dia' => 'nullable|date',
            'observacao' => 'nullable|string|max:200',
            'itens' => 'nullable|array|min:1',
            'itens.*.codperiodocolaborador' => 'required|integer|exists:tblperiodocolaborador,codperiodocolaborador',
            'itens.*.valor' => 'required|numeric|min:0.01',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $itens = $this->input('itens');
            if (!is_array($itens)) {
                return;
            }

            // Duas linhas do mesmo vínculo no mesmo lote virariam dois itens que
            // a planilha somaria num CPF só — funciona, mas o RH marcou duas
            // vezes sem querer. Melhor recusar do que somar em silêncio.
            $vinculos = array_column($itens, 'codperiodocolaborador');
            if (count($vinculos) !== count(array_unique($vinculos))) {
                $v->errors()->add('itens', 'Colaborador repetido no lote.');
            }
        });
    }
}
