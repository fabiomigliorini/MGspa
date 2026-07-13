<?php

namespace Mg\UnidadeReferencia;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class UnidadeReferenciaResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta — reexpostas abaixo.
        unset($ret['estado'], $ret['cidade'], $ret['unidade_referencia_valor_s']);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Estado'] = $this->whenLoaded('Estado');
        $ret['Cidade'] = $this->whenLoaded('Cidade');

        // histórico de valores por competência (mais recente primeiro)
        $ret['valores'] = $this->whenLoaded(
            'UnidadeReferenciaValorS',
            fn () => UnidadeReferenciaValorResource::collection($this->UnidadeReferenciaValorS)
        );

        return $ret;
    }
}
