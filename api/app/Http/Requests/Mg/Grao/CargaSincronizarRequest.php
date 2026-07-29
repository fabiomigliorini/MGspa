<?php

namespace App\Http\Requests\Mg\Grao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\Grao\CargaService;

class CargaSincronizarRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Descarta linhas de classificacao sem codparametroclassificacao ANTES da
     * validacao — o service ja as ignora, e assim uma linha em branco (materializada
     * pela UI) nunca reprova a carga inteira via a regra `required`.
     */
    protected function prepareForValidation()
    {
        if (is_array($this->classificacao)) {
            $this->merge([
                'classificacao' => array_values(array_filter(
                    $this->classificacao,
                    fn ($c) => is_array($c) && !empty($c['codparametroclassificacao'])
                )),
            ]);
        }
    }

    public function rules()
    {
        return [
            'uuid' => ['required', 'string'],
            'codsafra' => ['required', 'exists:tblsafra,codsafra'],
            'sentido' => ['required', Rule::in(CargaService::SENTIDOS)],
            'etapa' => ['required', Rule::in(CargaService::ETAPAS)],
            'data' => ['required', 'date'],
            'inativo' => ['nullable', 'date'],

            // Identificacao (snapshot textual + FKs)
            'placa' => ['nullable', 'string', 'max:10'],
            'placacarreta' => ['nullable', 'string', 'max:10'],
            'codveiculo' => ['nullable', 'exists:tblveiculo,codveiculo'],
            'codpessoamotorista' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'motorista' => ['nullable', 'string', 'max:60'],
            'observacao' => ['nullable', 'string'],

            // Pesos
            'pbt' => ['nullable', 'numeric', 'gte:0'],
            'tara' => ['nullable', 'numeric', 'gte:0'],

            // Tabela resolvida + leituras da classificacao (o modelo por formula)
            'codtabelaclassificacao' => ['nullable', 'exists:tbltabelaclassificacao,codtabelaclassificacao'],
            'classificacao' => ['array'],
            'classificacao.*.codparametroclassificacao' => ['required', 'exists:tblparametroclassificacao,codparametroclassificacao'],
            'classificacao.*.leitura' => ['nullable', 'numeric'],

            // Pontos (origem/destino)
            'pontos' => ['array'],
            'pontos.*.papel' => ['required', Rule::in(['ORIGEM', 'DESTINO'])],
            'pontos.*.contatipo' => ['required', Rule::in(CargaService::CONTATIPOS)],
            'pontos.*.codplantio' => ['nullable', 'exists:tblplantio,codplantio'],
            'pontos.*.codunidadearmazenadora' => ['nullable', 'exists:tblunidadearmazenadora,codunidadearmazenadora'],
            'pontos.*.codcontrato' => ['nullable', 'exists:tblcontrato,codcontrato'],
            'pontos.*.liquido' => ['nullable', 'numeric', 'gte:0'],
            'pontos.*.numeronf' => ['nullable', 'string', 'max:20'],
            // teto = numeric(14,2): 12 dígitos inteiros; sem ele um valor gigante daria 500 no insert
            'pontos.*.valornf' => ['nullable', 'numeric', 'gte:0', 'max:999999999999.99'],
            'pontos.*.chavenf' => ['nullable', 'string', 'max:44'],
        ];
    }
}
