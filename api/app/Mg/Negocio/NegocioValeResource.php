<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

/**
 * Vale emitido dentro do negocio, no formato que o PDV guarda no
 * IndexedDB — inclusive os itens, que viajam dentro do vale.
 *
 * A recarga do negocio pela API substitui o objeto inteiro na tela, entao
 * tudo que a secao do vale mostra tem que sair daqui: o nome do
 * favorecido, a descricao do modelo e os itens com produto/imagem.
 */
class NegocioValeResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        $ret['favorecido'] = $this->PessoaFavorecido->fantasia ?? null;
        $ret['modelo'] = $this->ValeModelo->modelo ?? null;

        $ret['itens'] = NegocioValeProdutoBarraResource::collection(
            $this->NegocioValeProdutoBarraS
        );

        return $ret;
    }
}
