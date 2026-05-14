<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Negocio\NegocioNotaFiscalResource;

class TituloDetalheResource extends Resource
{
    private function notasVinculadas(): array
    {
        $origens = [];

        if (!empty($this->codtituloagrupamento)) {
            $ag = TituloAgrupamento::with([
                'MovimentoTituloS.TipoMovimentoTitulo',
                'MovimentoTituloS.Titulo.NegocioFormaPagamento',
            ])->find($this->codtituloagrupamento);
            if ($ag) {
                foreach ($ag->MovimentoTituloS as $m) {
                    if (optional($m->TipoMovimentoTitulo)->estorno) continue;
                    $t = $m->Titulo;
                    if (!$t) continue;
                    if ($t->codtituloagrupamento === $ag->codtituloagrupamento) continue;
                    $cn = optional($t->NegocioFormaPagamento)->codnegocio;
                    if (!$cn) continue;
                    $origens[] = [
                        'codtitulo' => (int)$t->codtitulo,
                        'numero' => $t->numero,
                        'codnegocio' => (int)$cn,
                    ];
                }
            }
        } elseif (!empty($this->codnegocioformapagamento)) {
            $cn = optional($this->NegocioFormaPagamento)->codnegocio;
            if ($cn) {
                $origens[] = [
                    'codtitulo' => (int)$this->codtitulo,
                    'numero' => $this->numero,
                    'codnegocio' => (int)$cn,
                ];
            }
        }

        if (empty($origens)) return [];

        $notas = [];
        $cacheNegocio = [];
        foreach ($origens as $o) {
            $cn = $o['codnegocio'];
            if (!isset($cacheNegocio[$cn])) {
                $cacheNegocio[$cn] = NegocioNotaFiscalResource::buscarPorCodnegocio($cn);
            }
            foreach ($cacheNegocio[$cn] as $nf) {
                $notas[] = array_merge((array)$nf, [
                    'codtitulo' => $o['codtitulo'],
                    'numero_titulo' => $o['numero'],
                ]);
            }
        }

        usort($notas, function ($a, $b) {
            $cmp = strcmp((string)($b['emissao'] ?? ''), (string)($a['emissao'] ?? ''));
            if ($cmp !== 0) return $cmp;
            return ($b['codnotafiscal'] ?? 0) <=> ($a['codnotafiscal'] ?? 0);
        });

        return $notas;
    }

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

        $boletos = $this->TituloBoletoS->map(function ($b) {
            return [
                'codtituloboleto'      => (int)$b->codtituloboleto,
                'codportador'          => $b->codportador ? (int)$b->codportador : null,
                'portador'             => optional($b->Portador)->portador,
                'nossonumero'          => $b->nossonumero,
                'estadotitulocobranca' => (int)$b->estadotitulocobranca,
                'tipobaixatitulo'      => $b->tipobaixatitulo ? (int)$b->tipobaixatitulo : null,
                'vencimento'           => $b->vencimento,
                'dataregistro'         => $b->dataregistro,
                'datarecebimento'      => $b->datarecebimento,
                'datacredito'          => $b->datacredito,
                'databaixaautomatica'  => $b->databaixaautomatica,
                'valororiginal'        => (float)$b->valororiginal,
                'valoratual'           => (float)$b->valoratual,
                'valorpagamentoparcial' => (float)$b->valorpagamentoparcial,
                'valorabatimento'      => (float)$b->valorabatimento,
                'valorjuromora'        => (float)$b->valorjuromora,
                'valormulta'           => (float)$b->valormulta,
                'valordesconto'        => (float)$b->valordesconto,
                'valorreajuste'        => (float)$b->valorreajuste,
                'valoroutro'           => (float)$b->valoroutro,
                'valorpago'            => (float)$b->valorpago,
                'valorliquido'         => (float)$b->valorliquido,
                'inativo'              => $b->inativo,
            ];
        });

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
            'boletos'          => $boletos,
            'notas'            => $this->notasVinculadas(),
            'gerado_automaticamente' => (!empty($this->codnegocioformapagamento) || !empty($this->codtituloagrupamento)),
        ];
    }
}
