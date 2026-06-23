<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Mg\Contrato\Contrato;
use Mg\Contrato\ContratoService;

class ContratoFixacaoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'data' => ['required', 'date'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'preco' => ['required', 'numeric', 'gte:0'],
            // moeda guarda o iso (FK tblmoeda.iso). Aberto ao cadastro de moedas.
            'moeda' => ['nullable', 'string', 'exists:tblmoeda,iso'],
            // Moeda estrangeira (!= BRL) exige a cotação em R$ p/ converter o preço
            // (senão precoReal gravaria o valor em USD sem converter).
            'dolar' => [
                'nullable',
                'numeric',
                'gt:0',
                Rule::requiredIf(fn () => $this->filled('moeda') && $this->input('moeda') !== 'BRL'),
            ],
            // isentofethab é DERIVADO no controller a partir das linhas do grupo
            // FETHAB (sem valor = isento); aceito aqui só por compatibilidade.
            'isentofethab' => ['nullable', 'boolean'],
            // Snapshot dos impostos digitado/ajustado no modal. O líquido oficial
            // é recalculado no controller a partir dessas linhas (não confia no
            // total que veio do cliente). Ausente = calcula on-the-fly na leitura.
            'tributos' => ['nullable', 'array'],
            'tributos.*.codtributo' => ['nullable', 'integer'],
            'tributos.*.codigo' => ['nullable', 'string', 'max:20'],
            'tributos.*.descricao' => ['nullable', 'string', 'max:100'],
            'tributos.*.base' => ['required_with:tributos', Rule::in(['UNIDADE', 'VALOR'])],
            // Alíquota não passa de 100% (acima disso a dedução superaria o bruto).
            'tributos.*.percentual' => ['required_with:tributos', 'numeric', 'gte:0', 'max:100'],
            'tributos.*.upf' => ['nullable', 'numeric', 'gte:0'],
            'tributos.*.grupofethab' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Trava de teto: a soma das fixações ativas + esta não pode passar da
     * quantidade contratada (sacas). Pula contratos sem teto (quantidade NULL =
     * volume em aberto). Na edição, ignora a própria fixação. Backend é a
     * fonte de verdade — o front só espelha.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $contrato = Contrato::find($this->route('codcontrato'));
            if (!$contrato || $contrato->quantidade === null) {
                return; // sem teto (volume em aberto) ou contrato inexistente (404 noutro lugar)
            }
            $nova = (float) $this->input('quantidade');
            $codfixacao = $this->route('codfixacao');
            $jaFixado = ContratoService::sacasFixadas(
                (int) $contrato->codcontrato,
                $codfixacao !== null ? (int) $codfixacao : null,
            );
            if ($jaFixado + $nova > (float) $contrato->quantidade + 1e-6) {
                $saldo = max(0, (float) $contrato->quantidade - $jaFixado);
                $validator->errors()->add(
                    'quantidade',
                    'Excede o saldo a fixar do contrato (' . number_format($saldo, 0, ',', '.') . ' sc).',
                );
            }
        });
    }
}
