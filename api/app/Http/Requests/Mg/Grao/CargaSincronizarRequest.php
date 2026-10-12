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
            'uuid' => ['required', 'uuid'],
            // Ausente/null = aparelho antigo (sem controle de versao) ou criacao
            // reenviada: CargaService::sincronizar aplica direto, como hoje.
            'versao' => ['nullable', 'integer', 'min:1'],
            'codsafra' => ['required', 'exists:tblsafra,codsafra'],
            'sentido' => ['required', Rule::in(CargaService::SENTIDOS)],
            'etapa' => ['required', Rule::in(CargaService::ETAPAS)],
            'data' => ['required', 'date'],
            'inativo' => ['nullable', 'date'],

            // Identificacao (snapshot textual + FKs)
            'placa' => ['nullable', 'string', 'max:10'],
            'placacarreta' => ['nullable', 'string', 'max:10'],
            'placacarreta2' => ['nullable', 'string', 'max:10'],
            'codveiculo' => ['nullable', 'exists:tblveiculo,codveiculo'],
            'codpessoamotorista' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'motorista' => ['nullable', 'string', 'max:60'],
            // Motorista SEM cadastro (só dígitos em CPF/celular/CEP). Nullable:
            // carga antiga e offline chegam sem eles — quem exige é o modal do pátio.
            'cpfmotorista' => ['nullable', 'digits:11', 'cpf_cnpj'],
            'telefonemotorista' => ['nullable', 'digits:11'],
            'cepmotorista' => ['nullable', 'digits:8'],
            'enderecomotorista' => ['nullable', 'string', 'max:100'],
            'bairromotorista' => ['nullable', 'string', 'max:50'],
            'codcidademotorista' => ['nullable', 'exists:tblcidade,codcidade'],
            'observacao' => ['nullable', 'string'],

            // Pesos: inteiros em kg, ate 150.000 (maior PBT real fica bem abaixo
            // disso). tara > pbt so e recusada quando os dois vierem juntos.
            'pbt' => ['nullable', 'integer', 'between:0,150000'],
            'tara' => ['nullable', 'integer', 'between:0,150000', function ($attribute, $value, $fail) {
                if ($value !== null && $this->pbt !== null && $value > $this->pbt) {
                    $fail('A tara (' . $value . ' kg) e maior que o PBT (' . $this->pbt . ' kg). Confira as pesagens.');
                }
            }],

            // Tabela resolvida + leituras da classificacao (o modelo por formula).
            // `distinct` no parametro: o mesmo parametro nao pode ter duas leituras
            // na mesma carga.
            'classificacao' => ['array'],
            'classificacao.*.codparametroclassificacao' => ['required', 'distinct', 'exists:tblparametroclassificacao,codparametroclassificacao'],
            'classificacao.*.leitura' => ['nullable', 'numeric', 'between:0,100'],

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
