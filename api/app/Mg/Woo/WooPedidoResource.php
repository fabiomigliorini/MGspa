<?php

namespace Mg\Woo;

use Illuminate\Http\Resources\Json\JsonResource;

class WooPedidoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = [

            // Campos Principais
            'codwoopedido' => $this->codwoopedido, // bigserial
            'id' => $this->id,
            'status' => $this->status,
            'criacaowoo' => $this->criacaowoo ? $this->criacaowoo->format('Y-m-d H:i:s') : null,
            'alteracaowoo' => $this->alteracaowoo ? $this->alteracaowoo->format('Y-m-d H:i:s') : null,
            'valortotal' => (float) $this->valortotal, // numeric(14, 2)
            'nome' => $this->nome,
            'cidade' => $this->cidade,
            'pagamento' => $this->pagamento,
            'entrega' => $this->entrega,
            'valorfrete' => (float) $this->valorfrete, // numeric(14, 2)
            'jsonwoo' => $this->jsonwoo, // json

            // Campos de Metadados / Controle (Timestamps e Usuários)
            'criacao' => $this->criacao ? $this->criacao->format('Y-m-d H:i:s') : null,
            'codusuariocriacao' => $this->codusuariocriacao,
            'alteracao' => $this->alteracao ? $this->alteracao->format('Y-m-d H:i:s') : null,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];

        // negocios associados
        $ret['negocios'] = $this->WooPedidoNegocioS()->with('Negocio')->get()->map(function ($item) {
            return [
                'codnegocio' => $item->codnegocio,
                'negociostatus' => $item->Negocio->NegocioStatus->negociostatus ?? null,
                'valor' => (float) $item->Negocio->valortotal,
            ];
        });

        return $ret;
    }
}