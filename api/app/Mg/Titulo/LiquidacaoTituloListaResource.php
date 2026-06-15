<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class LiquidacaoTituloListaResource extends Resource
{
    public function toArray($request)
    {
        $debito = (float)$this->debito;
        $credito = (float)$this->credito;
        $valor = $debito - $credito;
        $operacao = ($valor < 0) ? 'CR' : 'DB';

        return [
            'codliquidacaotitulo' => (int)$this->codliquidacaotitulo,
            'codpessoa'           => (int)$this->codpessoa,
            'fantasia'            => optional($this->Pessoa)->fantasia,
            'codportador'         => $this->codportador ? (int)$this->codportador : null,
            'portador'            => optional($this->Portador)->portador,
            'transacao'           => $this->transacao,
            'criacao'             => $this->criacao,
            'estornado'           => $this->estornado,
            'codperiodo'          => $this->codperiodo,
            'codusuariocriacao'   => $this->codusuariocriacao,
            'usuariocriacao'      => $this->usuariocriacao,
            'debito'              => $debito,
            'credito'             => $credito,
            'valor'               => abs($valor),
            'operacao'            => $operacao,
            'observacao'          => $this->observacao,
        ];
    }
}
