<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TituloDetalheResource extends Resource
{
    public function toArray($request)
    {
        $debito = (float)$this->debito;
        $credito = (float)$this->credito;
        $saldo = (float)$this->saldo;
        $debitosaldo = (float)$this->debitosaldo;
        $creditosaldo = (float)$this->creditosaldo;
        $valor = $debito - $credito;
        $operacao = ($valor < 0) ? 'CR' : 'DB';
        $operacaosaldo = ($saldo < 0 || $credito > $debito) ? 'CR' : 'DB';

        $movimentos = $this->MovimentoTituloS->map(function ($m) {
            $debMov = (float)$m->debito;
            $credMov = (float)$m->credito;
            $valMov = $debMov - $credMov;
            $opMov = ($valMov < 0) ? 'CR' : 'DB';
            return [
                'codmovimentotitulo' => (int)$m->codmovimentotitulo,
                'codtipomovimentotitulo' => (int)$m->codtipomovimentotitulo,
                'tipomovimentotitulo' => optional($m->TipoMovimentoTitulo)->tipomovimentotitulo,
                'codportador' => $m->codportador ? (int)$m->codportador : null,
                'portador' => optional($m->Portador)->portador,
                'codliquidacaotitulo' => $m->codliquidacaotitulo ? (int)$m->codliquidacaotitulo : null,
                'codtituloagrupamento' => $m->codtituloagrupamento ? (int)$m->codtituloagrupamento : null,
                'codnegocio' => optional($m->NegocioFormaPagamento)->codnegocio,
                'codboletoretorno' => $m->codboletoretorno ? (int)$m->codboletoretorno : null,
                'codcobranca' => $m->codcobranca ? (int)$m->codcobranca : null,
                'codtitulorelacionado' => $m->codtitulorelacionado ? (int)$m->codtitulorelacionado : null,
                'historico' => $m->historico,
                'transacao' => $m->transacao,
                'sistema' => $m->sistema,
                'criacao' => $m->criacao,
                'usuariocriacao' => optional($m->UsuarioCriacao)->usuario,
                'debito' => $debMov,
                'credito' => $credMov,
                'valor' => $valMov,
                'operacao' => $opMov,
            ];
        });

        return [
            'codtitulo'        => (int)$this->codtitulo,
            'numero'           => $this->numero,
            'fatura'           => $this->fatura,
            'codpessoa'        => (int)$this->codpessoa,
            'fantasia'         => optional($this->Pessoa)->fantasia,
            'pessoa'           => optional($this->Pessoa)->pessoa,
            'codfilial'        => (int)$this->codfilial,
            'filial'           => optional($this->Filial)->filial,
            'codtipotitulo'    => (int)$this->codtipotitulo,
            'tipotitulo'       => optional($this->TipoTitulo)->tipotitulo,
            'tipotitulocredito' => (bool)optional($this->TipoTitulo)->credito,
            'tipotitulodebito'  => (bool)optional($this->TipoTitulo)->debito,
            'tipotitulopagar'   => (bool)optional($this->TipoTitulo)->pagar,
            'tipotituloreceber' => (bool)optional($this->TipoTitulo)->receber,
            'codcontacontabil' => $this->codcontacontabil ? (int)$this->codcontacontabil : null,
            'contacontabil'    => optional($this->ContaContabil)->contacontabil,
            'codportador'      => $this->codportador ? (int)$this->codportador : null,
            'portador'         => optional($this->Portador)->portador,
            'portadorcodbanco' => optional($this->Portador)->codbanco ? (int)$this->Portador->codbanco : null,
            'portadorcodfilial' => optional($this->Portador)->codfilial ? (int)$this->Portador->codfilial : null,
            'codnegocioformapagamento' => $this->codnegocioformapagamento ? (int)$this->codnegocioformapagamento : null,
            'codnegocio'       => optional($this->NegocioFormaPagamento)->codnegocio,
            'codtituloagrupamento' => $this->codtituloagrupamento ? (int)$this->codtituloagrupamento : null,
            'codusuariocriacao' => $this->codusuariocriacao ? (int)$this->codusuariocriacao : null,
            'usuariocriacao'   => optional($this->UsuarioCriacao)->usuario,
            'codusuarioalteracao' => $this->codusuarioalteracao ? (int)$this->codusuarioalteracao : null,
            'usuarioalteracao' => optional($this->UsuarioAlteracao)->usuario,
            'emissao'          => $this->emissao,
            'vencimento'       => $this->vencimento,
            'vencimentooriginal' => $this->vencimentooriginal,
            'transacao'        => $this->transacao,
            'transacaoliquidacao' => $this->transacaoliquidacao,
            'estornado'        => $this->estornado,
            'gerencial'        => (bool)$this->gerencial,
            'boleto'           => (bool)$this->boleto,
            'nossonumero'      => $this->nossonumero,
            'remessa'          => $this->remessa,
            'debito'           => $debito,
            'credito'          => $credito,
            'saldo'            => $saldo,
            'debitosaldo'      => $debitosaldo,
            'creditosaldo'     => $creditosaldo,
            'valor'            => $valor,
            'operacao'         => $operacao,
            'operacaosaldo'    => $operacaosaldo,
            'observacao'       => $this->observacao,
            'criacao'          => $this->criacao,
            'alteracao'        => $this->alteracao,
            'sistema'          => $this->sistema,
            'movimentos'       => $movimentos,
            'gerado_automaticamente' => (!empty($this->codnegocioformapagamento) || !empty($this->codtituloagrupamento)),
        ];
    }
}
