<?php

namespace Mg\Meta;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class MetaResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        $ret['unidades'] = $this->MetaUnidadeNegocioS->map(function ($unidade) {
            $unidadeArr = $unidade->toArray();
            $unidadeArr['descricao'] = $unidade->UnidadeNegocio->descricao ?? null;

            $pessoas = MetaUnidadeNegocioPessoa::where('codmeta', $this->codmeta)
                ->where('codunidadenegocio', $unidade->codunidadenegocio)
                ->with('Pessoa:codpessoa,pessoa,fantasia')
                ->get();

            $unidadeArr['pessoas'] = $pessoas->map(function ($pessoa) {
                $pessoaArr = $pessoa->toArray();
                $pessoaArr['pessoa'] = $pessoa->Pessoa->fantasia ?? $pessoa->Pessoa->pessoa ?? null;

                $pessoaArr['fixos'] = MetaUnidadeNegocioPessoaFixo::where('codmeta', $this->codmeta)
                    ->where('codunidadenegocio', $pessoa->codunidadenegocio)
                    ->where('codpessoa', $pessoa->codpessoa)
                    ->get()
                    ->toArray();

                return $pessoaArr;
            });

            return $unidadeArr;
        });

        return $ret;
    }
}
