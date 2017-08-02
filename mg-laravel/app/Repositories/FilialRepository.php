<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Filial;

class FilialRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\Filial';

    public static function validationRules ($model = null)
    {
        $rules = [
            'codempresa' => [
                'numeric',
                'nullable',
            ],
            'codpessoa' => [
                'numeric',
                'nullable',
            ],
            'filial' => [
                'max:20',
                'required',
            ],
            'emitenfe' => [
                'boolean',
                'required',
            ],
            'acbrnfemonitorcaminho' => [
                'max:100',
                'nullable',
            ],
            'acbrnfemonitorcaminhorede' => [
                'max:100',
                'nullable',
            ],
            'acbrnfemonitorbloqueado' => [
                'date',
                'nullable',
            ],
            'acbrnfemonitorcodusuario' => [
                'numeric',
                'nullable',
            ],
            'empresadominio' => [
                'numeric',
                'nullable',
            ],
            'acbrnfemonitorip' => [
                'max:20',
                'nullable',
            ],
            'acbrnfemonitorporta' => [
                'numeric',
                'nullable',
            ],
            'odbcnumeronotafiscal' => [
                'max:500',
                'nullable',
            ],
            'crt' => [
                'numeric',
                'required',
            ],
            'nfcetoken' => [
                'max:32',
                'nullable',
            ],
            'nfcetokenid' => [
                'max:6',
                'nullable',
            ],
            'nfeambiente' => [
                'numeric',
                'required',
            ],
            'senhacertificado' => [
                'max:50',
                'nullable',
            ],
            'ultimonsu' => [
                'numeric',
                'nullable',
            ],
            'tokenibpt' => [
                'max:200',
                'nullable',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'codempresa.numeric' => 'O campo "codempresa" deve ser um número!',
            'codpessoa.numeric' => 'O campo "codpessoa" deve ser um número!',
            'filial.max' => 'O campo "filial" não pode conter mais que 20 caracteres!',
            'filial.required' => 'O campo "filial" deve ser preenchido!',
            'emitenfe.boolean' => 'O campo "emitenfe" deve ser um verdadeiro/falso (booleano)!',
            'emitenfe.required' => 'O campo "emitenfe" deve ser preenchido!',
            'acbrnfemonitorcaminho.max' => 'O campo "acbrnfemonitorcaminho" não pode conter mais que 100 caracteres!',
            'acbrnfemonitorcaminhorede.max' => 'O campo "acbrnfemonitorcaminhorede" não pode conter mais que 100 caracteres!',
            'acbrnfemonitorbloqueado.date' => 'O campo "acbrnfemonitorbloqueado" deve ser uma data!',
            'acbrnfemonitorcodusuario.numeric' => 'O campo "acbrnfemonitorcodusuario" deve ser um número!',
            'empresadominio.numeric' => 'O campo "empresadominio" deve ser um número!',
            'acbrnfemonitorip.max' => 'O campo "acbrnfemonitorip" não pode conter mais que 20 caracteres!',
            'acbrnfemonitorporta.numeric' => 'O campo "acbrnfemonitorporta" deve ser um número!',
            'odbcnumeronotafiscal.max' => 'O campo "odbcnumeronotafiscal" não pode conter mais que 500 caracteres!',
            'crt.numeric' => 'O campo "crt" deve ser um número!',
            'crt.required' => 'O campo "crt" deve ser preenchido!',
            'nfcetoken.max' => 'O campo "nfcetoken" não pode conter mais que 32 caracteres!',
            'nfcetokenid.max' => 'O campo "nfcetokenid" não pode conter mais que 6 caracteres!',
            'nfeambiente.numeric' => 'O campo "nfeambiente" deve ser um número!',
            'nfeambiente.required' => 'O campo "nfeambiente" deve ser preenchido!',
            'senhacertificado.max' => 'O campo "senhacertificado" não pode conter mais que 50 caracteres!',
            'ultimonsu.numeric' => 'O campo "ultimonsu" deve ser um número!',
            'tokenibpt.max' => 'O campo "tokenibpt" não pode conter mais que 200 caracteres!',
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

}
