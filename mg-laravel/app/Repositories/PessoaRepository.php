<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;

use App\Models\Pessoa;

class PessoaRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\Pessoa';

    public static function validationRules ($model = null)
    {
        $rules = [
            'pessoa' => [
                'max:100',
                'required',
            ],
            'fantasia' => [
                'max:50',
                'required',
            ],
            'cliente' => [
                'boolean',
                'required',
            ],
            'fornecedor' => [
                'boolean',
                'required',
            ],
            'fisica' => [
                'boolean',
                'required',
            ],
            'codsexo' => [
                'numeric',
                'nullable',
            ],
            'cnpj' => [
                'numeric',
                'nullable',
            ],
            'ie' => [
                'max:20',
                'nullable',
            ],
            'consumidor' => [
                'boolean',
                'required',
            ],
            'contato' => [
                'max:100',
                'nullable',
            ],
            'codestadocivil' => [
                'numeric',
                'nullable',
            ],
            'conjuge' => [
                'max:100',
                'nullable',
            ],
            'endereco' => [
                'max:100',
                'nullable',
            ],
            'numero' => [
                'max:10',
                'nullable',
            ],
            'complemento' => [
                'max:50',
                'nullable',
            ],
            'codcidade' => [
                'numeric',
                'nullable',
            ],
            'bairro' => [
                'max:50',
                'nullable',
            ],
            'cep' => [
                'max:8',
                'nullable',
            ],
            'enderecocobranca' => [
                'max:100',
                'nullable',
            ],
            'numerocobranca' => [
                'max:10',
                'nullable',
            ],
            'complementocobranca' => [
                'max:50',
                'nullable',
            ],
            'codcidadecobranca' => [
                'numeric',
                'nullable',
            ],
            'bairrocobranca' => [
                'max:50',
                'nullable',
            ],
            'cepcobranca' => [
                'max:8',
                'nullable',
            ],
            'telefone1' => [
                'max:50',
                'nullable',
            ],
            'telefone2' => [
                'max:50',
                'nullable',
            ],
            'telefone3' => [
                'max:50',
                'nullable',
            ],
            'email' => [
                'max:100',
                'nullable',
            ],
            'emailnfe' => [
                'max:100',
                'nullable',
            ],
            'emailcobranca' => [
                'max:100',
                'nullable',
            ],
            'codformapagamento' => [
                'numeric',
                'nullable',
            ],
            'credito' => [
                'numeric',
                'nullable',
            ],
            'creditobloqueado' => [
                'boolean',
                'required',
            ],
            'observacoes' => [
                'max:255',
                'nullable',
            ],
            'mensagemvenda' => [
                'max:500',
                'nullable',
            ],
            'vendedor' => [
                'boolean',
                'required',
            ],
            'rg' => [
                'max:30',
                'nullable',
            ],
            'desconto' => [
                'numeric',
                'nullable',
            ],
            'notafiscal' => [
                'numeric',
                'required',
            ],
            'toleranciaatraso' => [
                'numeric',
                'required',
            ],
            'codgrupocliente' => [
                'numeric',
                'nullable',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'pessoa.max' => 'O campo "pessoa" não pode conter mais que 100 caracteres!',
            'pessoa.required' => 'O campo "pessoa" deve ser preenchido!',
            'fantasia.max' => 'O campo "fantasia" não pode conter mais que 50 caracteres!',
            'fantasia.required' => 'O campo "fantasia" deve ser preenchido!',
            'cliente.boolean' => 'O campo "cliente" deve ser um verdadeiro/falso (booleano)!',
            'cliente.required' => 'O campo "cliente" deve ser preenchido!',
            'fornecedor.boolean' => 'O campo "fornecedor" deve ser um verdadeiro/falso (booleano)!',
            'fornecedor.required' => 'O campo "fornecedor" deve ser preenchido!',
            'fisica.boolean' => 'O campo "fisica" deve ser um verdadeiro/falso (booleano)!',
            'fisica.required' => 'O campo "fisica" deve ser preenchido!',
            'codsexo.numeric' => 'O campo "codsexo" deve ser um número!',
            'cnpj.numeric' => 'O campo "cnpj" deve ser um número!',
            'ie.max' => 'O campo "ie" não pode conter mais que 20 caracteres!',
            'consumidor.boolean' => 'O campo "consumidor" deve ser um verdadeiro/falso (booleano)!',
            'consumidor.required' => 'O campo "consumidor" deve ser preenchido!',
            'contato.max' => 'O campo "contato" não pode conter mais que 100 caracteres!',
            'codestadocivil.numeric' => 'O campo "codestadocivil" deve ser um número!',
            'conjuge.max' => 'O campo "conjuge" não pode conter mais que 100 caracteres!',
            'endereco.max' => 'O campo "endereco" não pode conter mais que 100 caracteres!',
            'numero.max' => 'O campo "numero" não pode conter mais que 10 caracteres!',
            'complemento.max' => 'O campo "complemento" não pode conter mais que 50 caracteres!',
            'codcidade.numeric' => 'O campo "codcidade" deve ser um número!',
            'bairro.max' => 'O campo "bairro" não pode conter mais que 50 caracteres!',
            'cep.max' => 'O campo "cep" não pode conter mais que 8 caracteres!',
            'enderecocobranca.max' => 'O campo "enderecocobranca" não pode conter mais que 100 caracteres!',
            'numerocobranca.max' => 'O campo "numerocobranca" não pode conter mais que 10 caracteres!',
            'complementocobranca.max' => 'O campo "complementocobranca" não pode conter mais que 50 caracteres!',
            'codcidadecobranca.numeric' => 'O campo "codcidadecobranca" deve ser um número!',
            'bairrocobranca.max' => 'O campo "bairrocobranca" não pode conter mais que 50 caracteres!',
            'cepcobranca.max' => 'O campo "cepcobranca" não pode conter mais que 8 caracteres!',
            'telefone1.max' => 'O campo "telefone1" não pode conter mais que 50 caracteres!',
            'telefone2.max' => 'O campo "telefone2" não pode conter mais que 50 caracteres!',
            'telefone3.max' => 'O campo "telefone3" não pode conter mais que 50 caracteres!',
            'email.max' => 'O campo "email" não pode conter mais que 100 caracteres!',
            'emailnfe.max' => 'O campo "emailnfe" não pode conter mais que 100 caracteres!',
            'emailcobranca.max' => 'O campo "emailcobranca" não pode conter mais que 100 caracteres!',
            'codformapagamento.numeric' => 'O campo "codformapagamento" deve ser um número!',
            'credito.numeric' => 'O campo "credito" deve ser um número!',
            'creditobloqueado.boolean' => 'O campo "creditobloqueado" deve ser um verdadeiro/falso (booleano)!',
            'creditobloqueado.required' => 'O campo "creditobloqueado" deve ser preenchido!',
            'observacoes.max' => 'O campo "observacoes" não pode conter mais que 255 caracteres!',
            'mensagemvenda.max' => 'O campo "mensagemvenda" não pode conter mais que 500 caracteres!',
            'vendedor.boolean' => 'O campo "vendedor" deve ser um verdadeiro/falso (booleano)!',
            'vendedor.required' => 'O campo "vendedor" deve ser preenchido!',
            'rg.max' => 'O campo "rg" não pode conter mais que 30 caracteres!',
            'desconto.numeric' => 'O campo "desconto" deve ser um número!',
            'notafiscal.numeric' => 'O campo "notafiscal" deve ser um número!',
            'notafiscal.required' => 'O campo "notafiscal" deve ser preenchido!',
            'toleranciaatraso.numeric' => 'O campo "toleranciaatraso" deve ser um número!',
            'toleranciaatraso.required' => 'O campo "toleranciaatraso" deve ser preenchido!',
            'codgrupocliente.numeric' => 'O campo "codgrupocliente" deve ser um número!',
        ];

        return $messages;
    }

    public static function details($model)
    {
        return parent::details ($model);
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        return parent::query ($filter, $sort, $fields);
    }

    public static function autocomplete($params)
    {
        $qry = app(static::$modelClass)::query();
        $qry->select('codpessoa', 'pessoa', 'fantasia', 'cnpj', 'inativo', 'fisica');

        // foreach (explode(' ', $params['pessoa']) as $palavra) {
        //     if (!empty($palavra)) {
        //         $qry->whereRaw("(tblpessoa.pessoa ilike '%{$palavra}%' or tblpessoa.fantasia ilike '%{$palavra}%')");
        //     }
        // }

        //$numero = (int)numeroLimpo($params['pessoa']);
        $numero = preg_replace("/[^0-9]/", "", $params['pessoa']);
		if ($numero > 0) {
          //$qry->orWhere('codpessoa', $numero);
          //$qry->whereRaw("OR cast(Cnpj as char(20)) ILIKE '%{$numero}%'");
          $qry->where('cnpj', 'ilike', "$numero");
		}

        $qry->limit(10)->orderBy('fantasia')->orderBy('pessoa');

        $res = [];
        foreach ($qry->get() as $item) {
            $res[] = [
                'label' => $item->fantasia,
                'value' => $item->fantasia,
                'id' => $item->codpessoa,
                'sublabel' => $item->pessoa,
                'stamp' => $item->cnpj
            ];
        }
        return $res;
    }

}
