<?php

namespace App\Http\Requests\Mg\Contrato;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Mg\Contrato\Contrato;
use Mg\Contrato\ContratoFixacao;
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
            // Vencimento (quando recebe) = eixo do fluxo de caixa. Opcional.
            'datavencimento' => ['nullable', 'date'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'preco' => ['required', 'numeric', 'gte:0'],
            // Moeda do preço: FK inteira (codmoeda). O front pré-seleciona Real.
            'codmoeda' => ['required', 'integer', 'exists:tblmoeda,codmoeda'],
            // Config fiscal declarada no modal (base/alíquota/UPF/grupofethab). O
            // líquido oficial é derivado das travas de câmbio (recalcular), não
            // vem daqui. Isenção FETHAB = linha do grupo com UPF/alíquota zerada.
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
     * volume em aberto). Na edição, ignora a própria fixação.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Piso: o que já tem câmbio travado não pode ser desfeito pela edição.
            $fixacao = $this->route('codfixacao') !== null
                ? ContratoFixacao::find($this->route('codfixacao'))
                : null;
            $travado = $fixacao
                ? (float) $fixacao->ContratoFixacaoCambioS()->whereNull('inativo')->sum('valor')
                : 0.0;
            if ($travado > 0) {
                if ((int) $this->input('codmoeda') !== (int) $fixacao->codmoeda) {
                    $validator->errors()->add('codmoeda', 'Não dá para trocar a moeda: já há câmbio travado.');
                }
                if ((float) $this->input('quantidade') * (float) $this->input('preco') < $travado - 0.005) {
                    $validator->errors()->add(
                        'quantidade',
                        'Quantidade × preço fica abaixo do câmbio já travado ('
                            . number_format($travado, 2, ',', '.') . ').',
                    );
                }
            }

            $contrato = Contrato::find($this->route('codcontrato'));
            if (!$contrato || $contrato->quantidade === null) {
                return; // sem teto (volume em aberto) ou contrato inexistente
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
