<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Illuminate\Support\Facades\DB;

class TituloDetalheResource extends Resource
{
    private function notasVinculadas(): array
    {
        if (empty($this->codnegocioparcela) && empty($this->codtituloagrupamento)) {
            return [];
        }

        $estornos = implode(', ', MovimentoTituloService::TIPOS_ESTORNO);

        $sql = "
            select distinct
                nf.codnotafiscal,
                nf.codfilial,
                f.filial,
                nat.naturezaoperacao,
                nf.emitida,
                nf.serie,
                nf.modelo,
                nf.numero,
                nf.status,
                nf.emissao,
                nf.valortotal
            from tblnotafiscal nf
            inner join tblfilial f on (f.codfilial = nf.codfilial)
            inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnotafiscal = nf.codnotafiscal)
            inner join tblnegocioprodutobarra npb on (npb.codnegocioprodutobarra = nfpb.codnegocioprodutobarra)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
            where npb.codnegocio in (
                select nfp.codnegocio
                from tbltitulo t
                inner join tblnegocioparcela nfp on (nfp.codnegocioparcela = t.codnegocioparcela)
                where t.codtitulo = :codtitulo1
                union
                select nfp.codnegocio
                from tbltitulo tag
                inner join tblmovimentotitulo mt on (mt.codtituloagrupamento = tag.codtituloagrupamento)
                inner join tbltitulo t on (t.codtitulo = mt.codtitulo)
                inner join tblnegocioparcela nfp on (nfp.codnegocioparcela = t.codnegocioparcela)
                where tag.codtitulo = :codtitulo2
                  and tag.codtituloagrupamento is not null
                  and mt.codmovimentotituloestorno is null
                  and mt.codtipomovimentotitulo not in ({$estornos})
            )
            order by nf.emissao desc, nf.codnotafiscal desc
        ";

        $codtitulo = (int)$this->codtitulo;
        $rows = DB::select($sql, ['codtitulo1' => $codtitulo, 'codtitulo2' => $codtitulo]);

        return array_map(fn($r) => [
            'codnotafiscal' => (int)$r->codnotafiscal,
            'codfilial'     => (int)$r->codfilial,
            'filial'        => $r->filial,
            'naturezaoperacao' => $r->naturezaoperacao,
            'emitida' => $r->emitida,
            'serie'         => $r->serie,
            'modelo'        => $r->modelo,
            'numero'        => $r->numero,
            'status'        => $r->status,
            'emissao'       => $r->emissao,
            'valortotal'    => (float)$r->valortotal,
        ], $rows);
    }

    public function toArray($request)
    {
        $saldo = (float)$this->saldo;
        $valor = (float)$this->valor;
        $operacao = ($valor < 0) ? 'CR' : 'DB';
        $operacaosaldo = $this->ehReceber() ? 'DB' : 'CR';

        $atualizacao = TituloService::calcularAtualizacao($saldo, $this->vencimento);

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

        // quem desfez quem: codigo do movimento original => codigo do estorno dele
        $estornadoPor = $this->MovimentoTituloS
            ->whereNotNull('codmovimentotituloestorno')
            ->pluck('codmovimentotitulo', 'codmovimentotituloestorno');

        $movimentos = $this->MovimentoTituloS->map(function ($m) use ($estornadoPor) {
            // principal é o efeito no saldo; na baixa, juros, multa e
            // desconto vêm na mesma linha e total é o que foi pago
            $principal = (float)$m->principal;
            $opMov = ($principal < 0) ? 'CR' : 'DB';
            return [
                'codmovimentotitulo' => (int)$m->codmovimentotitulo,
                'codtipomovimentotitulo' => (int)$m->codtipomovimentotitulo,
                'tipomovimentotitulo' => optional($m->TipoMovimentoTitulo)->tipomovimentotitulo,
                'codportador' => $m->codportador ? (int)$m->codportador : null,
                'portador' => optional($m->Portador)->portador,
                'codpagamento' => $m->codpagamento ? (int)$m->codpagamento : null,
                'codperiodocolaboradoracerto' => $m->codperiodocolaboradoracerto ? (int)$m->codperiodocolaboradoracerto : null,
                'codtituloagrupamento' => $m->codtituloagrupamento ? (int)$m->codtituloagrupamento : null,
                'codnegocio' => optional($m->Pagamento)->codnegocio,
                'codboletoretorno' => $m->codboletoretorno ? (int)$m->codboletoretorno : null,
                'codcobranca' => $m->codcobranca ? (int)$m->codcobranca : null,
                'codtitulorelacionado' => $m->codtitulorelacionado ? (int)$m->codtitulorelacionado : null,
                'historico' => $m->historico,
                'transacao' => $m->transacao,
                'criacao' => $m->criacao,
                'codusuariocriacao' => $m->codusuariocriacao ? (int)$m->codusuariocriacao : null,
                'codmovimentotituloestorno' => $m->codmovimentotituloestorno ? (int)$m->codmovimentotituloestorno : null,
                'codmovimentotituloestornadopor' => isset($estornadoPor[$m->codmovimentotitulo])
                    ? (int)$estornadoPor[$m->codmovimentotitulo]
                    : null,
                'estorno' => $m->ehEstorno(),
                'principal' => $principal,
                'juros' => (float)$m->juros,
                'multa' => (float)$m->multa,
                'desconto' => (float)$m->desconto,
                'total' => (float)$m->total,
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
            'tipotitulonatureza' => optional($this->TipoTitulo)->natureza,
            'tipotitulopagar'   => (bool)optional($this->TipoTitulo)->pagar,
            'tipotituloreceber' => (bool)optional($this->TipoTitulo)->receber,
            'codcontacontabil' => $this->codcontacontabil ? (int)$this->codcontacontabil : null,
            'contacontabil'    => optional($this->ContaContabil)->contacontabil,
            'codportador'      => $this->codportador ? (int)$this->codportador : null,
            'portador'         => optional($this->Portador)->portador,
            'portadorcodbanco' => optional($this->Portador)->codbanco ? (int)$this->Portador->codbanco : null,
            'portadorcodfilial' => optional($this->Portador)->codfilial ? (int)$this->Portador->codfilial : null,
            'codnegocioparcela' => $this->codnegocioparcela ? (int)$this->codnegocioparcela : null,
            'codnegocio'       => optional($this->NegocioParcela)->codnegocio,
            'codtituloagrupamento' => $this->codtituloagrupamento ? (int)$this->codtituloagrupamento : null,
            'codusuariocriacao' => $this->codusuariocriacao ? (int)$this->codusuariocriacao : null,
            'codusuarioalteracao' => $this->codusuarioalteracao ? (int)$this->codusuarioalteracao : null,
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
            'saldo'            => $saldo,
            'valor'            => $valor,
            'operacao'         => $operacao,
            'operacaosaldo'    => $operacaosaldo,
            'diasatraso'       => $atualizacao['diasatraso'],
            'juros'            => $atualizacao['juros'],
            'multa'            => $atualizacao['multa'],
            'valoratualizado'  => $atualizacao['valoratualizado'],
            'observacao'       => $this->observacao,
            'criacao'          => $this->criacao,
            'alteracao'        => $this->alteracao,
            'movimentos'       => $movimentos,
            'boletos'          => $boletos,
            'notas'            => $this->notasVinculadas(),
            'gerado_automaticamente' => (!empty($this->codnegocioparcela) || !empty($this->codtituloagrupamento)),
        ];
    }
}
