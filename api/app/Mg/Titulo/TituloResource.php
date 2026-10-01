<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TituloResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'codtitulo' => $this->codtitulo,
            'codtipotitulo' => $this->codtipotitulo,
            'tipotitulo' => $this->TipoTitulo->tipotitulo,
            'codfilial' => $this->codfilial,
            'codpessoa' => $this->codpessoa,
            'fantasia' => $this->Pessoa->fantasia,
            'codportador' => $this->codportador,
            'portador' => $this->Portador->portador ?? null,
            'codnegocioparcela' => $this->codnegocioparcela,
            'codtituloagrupamento' => $this->codtituloagrupamento,
            'numero' => $this->numero,
            'fatura' => $this->fatura,
            'emissao' => $this->emissao,
            'transacao' => $this->transacao,
            'vencimento' => $this->vencimento,
            'vencimentooriginal' => $this->vencimentooriginal,
            'transacaoliquidacao' => $this->transacaoliquidacao,
            'estornado' => $this->estornado,
            'boleto' => $this->boleto,
            'nossonumero' => $this->nossonumero,
            'gerencial' => $this->gerencial,
            'observacao' => $this->observacao,
            // com sinal: positivo a receber, negativo a pagar
            'valor' => $this->valor,
            'saldo' => $this->saldo,
        ];
    }
}
