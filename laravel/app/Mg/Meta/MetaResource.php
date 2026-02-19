<?php

namespace Mg\Meta;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class MetaResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        $this->loadMissing(['MetaUnidadeNegocioS.UnidadeNegocio']);

        $pessoas = MetaUnidadeNegocioPessoa::where('codmeta', $this->codmeta)
            ->with('Pessoa:codpessoa,pessoa,fantasia')
            ->get()
            ->groupBy('codunidadenegocio');

        $fixos = MetaUnidadeNegocioPessoaFixo::where('codmeta', $this->codmeta)
            ->get()
            ->groupBy(fn ($f) => $f->codunidadenegocio . '|' . $f->codpessoa);

        $ret['unidades'] = $this->MetaUnidadeNegocioS->map(function ($unidade) use ($pessoas, $fixos) {
            $unidadeArr = $unidade->toArray();
            $unidadeArr['descricao'] = $unidade->UnidadeNegocio->descricao ?? null;

            $pessoasUnidade = $pessoas->get($unidade->codunidadenegocio, collect());

            $unidadeArr['pessoas'] = $pessoasUnidade->map(function ($pessoa) use ($fixos) {
                $pessoaArr = $pessoa->toArray();
                $pessoaArr['pessoa'] = $pessoa->Pessoa->fantasia ?? $pessoa->Pessoa->pessoa ?? null;

                $chave = $pessoa->codunidadenegocio . '|' . $pessoa->codpessoa;
                $pessoaArr['fixos'] = $fixos->get($chave, collect())->toArray();

                return $pessoaArr;
            });

            return $unidadeArr;
        });

        return $ret;
    }
}
