<?php

namespace Mg\NotaFiscalServico\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Mg\Filial\Filial;
use Mg\NotaFiscalServico\NfseXmlService;

class NotaFiscalServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codfilial' => 'required|integer|exists:tblfilial,codfilial',
            // Com ISS retido o tomador e' obrigatorio: e' ele quem retem (E0204)
            'codpessoa' => 'nullable|required_if:issretido,true,1|integer|exists:tblpessoa,codpessoa',
            // Competencia nao pode ser depois da emissao (E0015)
            'competencia' => 'required|date_format:Y-m-d|before_or_equal:today',
            'codtributacaonacional' => 'required|integer|exists:tbltributacaonacional,codtributacaonacional',
            'codnbs' => 'required|integer|exists:tblnbs,codnbs',
            'descricao' => 'required|string|max:2000',
            'valorservico' => 'required|numeric|gt:0|max:999999999999.99',
            'valordescontoincondicionado' => 'nullable|numeric|min:0',
            'valordescontocondicionado' => 'nullable|numeric|min:0',
            // Ate 5% (E0595). Obrigatoria so com ISS retido; sem retencao (Simples, ISS
            // no DAS) nao vai na nota (E0625).
            'issaliquota' => 'nullable|required_if:issretido,true,1|numeric|gt:0|max:5',
            'issretido' => 'required|boolean',
            // pTotTribSN: ate 99,99%. Obrigatorio para filial do Simples (ver withValidator)
            'tributospercentual' => 'nullable|numeric|gt:0|max:99.99',
            'codcidadeprestacao' => 'required|integer|exists:tblcidade,codcidade',
            'observacoes' => 'nullable|string|max:2000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // ME/EPP: o Sistema Nacional exige o % aproximado dos tributos do Simples (E0712)
            $filial = Filial::find($this->input('codfilial'));
            if ($filial && NfseXmlService::simples($filial) && empty($this->input('tributospercentual'))) {
                $validator->errors()->add('tributospercentual', 'Informe o % aproximado dos tributos do Simples.');
            }
            // No Simples o ISS e' uma fatia do DAS: nunca passa do % total de tributos
            if (
                $this->boolean('issretido')
                && $this->filled('issaliquota')
                && $this->filled('tributospercentual')
                && (float) $this->input('issaliquota') > (float) $this->input('tributospercentual')
            ) {
                $validator->errors()->add('issaliquota', 'A alíquota do ISS não pode passar do % de Tributos do Simples.');
            }
            $descontos = (float) $this->input('valordescontoincondicionado', 0)
                + (float) $this->input('valordescontocondicionado', 0);
            if ($descontos >= (float) $this->input('valorservico', 0) && $descontos > 0) {
                $validator->errors()->add('valordescontoincondicionado', 'Os descontos não podem ser maiores ou iguais ao valor do serviço.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'codfilial.required' => 'Informe a filial.',
            'competencia.required' => 'Informe a competência.',
            'competencia.before_or_equal' => 'A competência não pode ser depois de hoje.',
            'codpessoa.required_if' => 'Com ISS retido, informe o tomador (é ele quem retém o ISS).',
            'codtributacaonacional.required' => 'Informe o código de tributação nacional.',
            'codnbs.required' => 'Informe o NBS.',
            'descricao.required' => 'Informe a descrição do serviço.',
            'descricao.max' => 'A descrição do serviço aceita até 2000 caracteres.',
            'valorservico.required' => 'Informe o valor do serviço.',
            'valorservico.gt' => 'O valor do serviço tem que ser maior que zero.',
            'issaliquota.max' => 'A alíquota do ISS não pode passar de 5%.',
            'issaliquota.required_if' => 'Com ISS retido, informe a alíquota do ISS.',
            'issaliquota.gt' => 'A alíquota do ISS tem que ser maior que zero.',
            'tributospercentual.max' => 'O % de tributos aceita até 99,99%.',
            'codcidadeprestacao.required' => 'Informe o local da prestação.',
            'observacoes.max' => 'As observações aceitam até 2000 caracteres.',
        ];
    }
}
