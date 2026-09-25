<?php

namespace Mg\Vale;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ValeModeloResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // relacoes eager-loaded nao viajam cruas; o que interessa delas
        // sai nos campos abaixo.
        unset(
            $ret['pessoa_favorecido'],
            $ret['vale_modelo_produto_barra_s'],
            $ret['usuario_criacao'],
            $ret['usuario_alteracao'],
        );

        $ret['favorecido'] = optional($this->PessoaFavorecido)->fantasia;

        // O kit vem junto na listagem: sao 204 modelos com ~21 itens cada, e
        // trazer tudo evita uma ida ao servidor para abrir a edicao.
        $ret['itens'] = ValeModeloProdutoBarraResource::collection($this->ValeModeloProdutoBarraS);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        return $ret;
    }
}
