<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TituloListaResource extends Resource
{
    public function toArray($request)
    {
        $saldo = (float)$this->saldo;
        $valor = (float)$this->valor;
        $operacao = ($valor < 0) ? 'CR' : 'DB';
        $operacaosaldo = $this->ehReceber() ? 'DB' : 'CR';

        return [
            'codtitulo'        => (int)$this->codtitulo,
            'numero'           => $this->numero,
            'fatura'           => $this->fatura,
            'codpessoa'        => (int)$this->codpessoa,
            'fantasia'         => optional($this->Pessoa)->fantasia,
            'codfilial'        => (int)$this->codfilial,
            'filial'           => optional($this->Filial)->filial,
            'codtipotitulo'    => (int)$this->codtipotitulo,
            'tipotitulo'       => optional($this->TipoTitulo)->tipotitulo,
            'codcontacontabil' => $this->codcontacontabil ? (int)$this->codcontacontabil : null,
            'contacontabil'    => optional($this->ContaContabil)->contacontabil,
            'codportador'      => $this->codportador ? (int)$this->codportador : null,
            'portador'         => optional($this->Portador)->portador,
            'codusuariocriacao' => $this->codusuariocriacao ? (int)$this->codusuariocriacao : null,
            'codnegocio'       => optional($this->NegocioFormaPagamento)->codnegocio,
            'codtituloagrupamento' => $this->codtituloagrupamento ? (int)$this->codtituloagrupamento : null,
            'emissao'          => $this->emissao,
            'vencimento'       => $this->vencimento,
            'vencimentooriginal' => $this->vencimentooriginal,
            'transacao'        => $this->transacao,
            'transacaoliquidacao' => $this->transacaoliquidacao,
            'estornado'        => $this->estornado,
            'gerencial'        => (bool)$this->gerencial,
            'boleto'           => (bool)$this->boleto,
            'nossonumero'      => $this->nossonumero,
            'saldo'            => $saldo,
            'valor'            => $valor,
            'operacao'         => $operacao,
            'operacaosaldo'    => $operacaosaldo,
            'observacao'       => $this->observacao,
            'criacao'          => $this->criacao,
        ];
    }
}
