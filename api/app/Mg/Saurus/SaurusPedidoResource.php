<?php

namespace Mg\Saurus;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class SaurusPedidoResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        // PDV Saurus e cartoes carregados antes levam as chaves da Saurus: nao saem (os cartoes
        // vao em SaurusPagamentoS)
        unset($ret['saurus_pdv'], $ret['saurus_pagamento_s']);
        // nome e serial sao da maquineta do PDV Saurus (TASK-264)
        $maquineta = $this->SaurusPdv->Maquineta ?? null;
        $ret['pos'] = $maquineta->serial ?? null;
        $ret['apelido'] = $maquineta->apelido ?? null;
        $ret['statusdescricao'] = SaurusService::STATUS_DESCRIPTION[$this->status]??null;
        $ret['tipodescricao'] = SaurusService::TYPE_DESCRIPTION[$this->modpagamento]??null;
        // pagamento que a confirmacao criou (o da venda)
        $ret['codpagamento'] = \Mg\Pagamento\Pagamento::where('codsauruspedido', $this->codsauruspedido)
            ->where('estado', '!=', \Mg\Pagamento\PagamentoService::ESTADO_CANCELADO)
            ->value('codpagamento');
        $ret['SaurusPagamentoS'] = SaurusPagamentoResource::collection(
            $this->SaurusPagamentoS()->orderBy('criacao', 'desc')->get()
        );
        return $ret;
    }
}
