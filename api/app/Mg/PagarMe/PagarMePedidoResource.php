<?php

namespace Mg\PagarMe;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class PagarMePedidoResource extends Resource
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
        // nome e serial sao da maquineta do POS (TASK-264)
        $maquineta = $this->PagarMePos->Maquineta ?? null;
        $ret['pos'] = $maquineta->serial ?? null;
        $ret['apelido'] = $maquineta->apelido ?? null;
        $ret['statusdescricao'] = PagarMeService::STATUS_DESCRIPTION[$this->status]??null;
        $ret['tipodescricao'] = PagarMeService::TYPE_DESCRIPTION[$this->tipo]??null;
        // pagamento que a confirmacao criou (o da venda)
        $ret['codpagamento'] = \Mg\Pagamento\Pagamento::where('codpagarmepedido', $this->codpagarmepedido)
            ->where('estado', '!=', \Mg\Pagamento\PagamentoService::ESTADO_CANCELADO)
            ->value('codpagamento');
        $ret['PagarMePagamentoS'] = PagarMePagamentoResource::collection(
            $this->PagarMePagamentoS()->orderBy('criacao', 'desc')->get()
        );
        return $ret;
    }
}
