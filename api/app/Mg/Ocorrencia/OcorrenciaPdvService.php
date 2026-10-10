<?php

namespace Mg\Ocorrencia;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Auditoria\Auditoria;
use Mg\Auditoria\AuditoriaService;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Negocio\NegocioService;
use Mg\Pagamento\PagamentoService;

/**
 * Livro de ocorrencias (TASK-205), o que o servidor olha no PDV monitorado:
 * o estado do negocio no fechamento e o que ele mesmo sobrescreve ou apaga
 * na sincronizacao. Cada fato vai para a auditoria e e' amarrado na
 * ocorrencia do negocio daquele tipo (OcorrenciaService::doNegocio).
 */
class OcorrenciaPdvService
{
    const SAIDA = 2;

    // desconto a vista sem autorizacao no cadastro (TASK-190 troca pela
    // regra por forma de pagamento e categoria de cliente)
    const DESCONTO_AVISTA = 5;
    const MEIOS_AVISTA = [
        PagamentoService::MEIO_DINHEIRO,
        PagamentoService::MEIO_DEBITO,
        PagamentoService::MEIO_PIX,
    ];

    // ---- no fechamento: o estado final do negocio ----

    // Chamado dentro da transacao do PdvNegocioService::fechar
    public static function noFechamento(Negocio $negocio): void
    {
        if (!OcorrenciaService::monitorado($negocio->Pdv, $negocio->criacao)) {
            return;
        }
        $codusuario = $negocio->codusuario;
        OcorrenciaService::doNegocio($negocio, OcorrenciaService::TIPO_ITEM_EXCLUIDO, static::itensExcluidos($negocio), $codusuario);
        foreach (static::precos($negocio) as $tipo => $auditorias) {
            OcorrenciaService::doNegocio($negocio, $tipo, $auditorias, $codusuario);
        }
        OcorrenciaService::doNegocio($negocio, OcorrenciaService::TIPO_VALE_EXCLUIDO, static::valesExcluidos($negocio), $codusuario);
        static::semFinanceiro($negocio);
        static::desconto($negocio);
    }

    // Item excluido; o juntado por bipe repetido (o produto continua no
    // negocio, so' que numa linha so') nao conta
    private static function itensExcluidos(Negocio $negocio): array
    {
        $regs = DB::select('
            select i.codnegocioprodutobarra, i.inativo
            from tblnegocioprodutobarra i
            where i.codnegocio = :codnegocio
            and i.inativo is not null
            and not exists (
                select 1 from tblnegocioprodutobarra a
                where a.codnegocio = i.codnegocio
                and a.codprodutobarra = i.codprodutobarra
                and a.inativo is null
            )
            order by i.codnegocioprodutobarra
        ', ['codnegocio' => $negocio->codnegocio]);
        return array_map(fn ($r) => AuditoriaService::registrar(
            'tblnegocioprodutobarra',
            $r->codnegocioprodutobarra,
            AuditoriaService::TIPO_ITEM_EXCLUIDO,
            ['inativo' => null],
            ['inativo' => $r->inativo]
        ), $regs);
    }

    // Preco praticado diferente do cadastro (ProdutoBarra::getPrecoAttribute),
    // aceitando o preco anterior a uma mudanca feita depois que o item entrou.
    // So' venda de balcao: fica de fora negocio com vale, Mercos, Woo, que
    // recebeu comanda, item de devolucao e item que outro usuario lancou
    // (orcamento apropriado), onde o preco nao foi o caixa que fez.
    // [tipo da ocorrencia => auditorias]
    private static function precos(Negocio $negocio): array
    {
        if (!$negocio->NaturezaOperacao->venda) {
            return [];
        }
        $foraDoBalcao = DB::selectOne("
            select
                exists (select 1 from tblnegociovale v where v.codnegocio = :n1 and v.inativo is null)
                or exists (select 1 from tblmercospedido m where m.codnegocio = :n2)
                or exists (select 1 from tblwoopedidonegocio w where w.codnegocio = :n3)
                or exists (
                    select 1 from tblnegocio c
                    where c.codnegociostatus = :cancelado
                    and c.lancamento >= :desde
                    and c.observacoes like :unificado
                ) as fora
        ", [
            'n1' => $negocio->codnegocio,
            'n2' => $negocio->codnegocio,
            'n3' => $negocio->codnegocio,
            'cancelado' => NegocioService::STATUS_CANCELADO,
            'desde' => Carbon::parse($negocio->criacao)->subDays(30),
            'unificado' => '%Unificado no negócio #' . $negocio->codnegocio . '%',
        ])->fora;
        if ($foraDoBalcao) {
            return [];
        }

        $regs = DB::select('
            select i.codnegocioprodutobarra, i.valorunitario,
                coalesce(pe.preco, round(p.preco * pe.quantidade, 2), p.preco) as cadastro
            from tblnegocioprodutobarra i
            ' . OcorrenciaService::SQL_JOIN_PRODUTO . '
            where i.codnegocio = :codnegocio
            and i.inativo is null
            and i.codnegocioprodutobarradevolucao is null
            and i.codusuariocriacao = :codusuario
            and abs(i.valorunitario - coalesce(pe.preco, round(p.preco * pe.quantidade, 2), p.preco)) >= 0.005
            and not exists (
                select 1 from tblprodutohistoricopreco h
                where h.codproduto = p.codproduto
                and h.criacao >= i.criacao
                and (
                    (h.codprodutoembalagem = pb.codprodutoembalagem
                        and abs(h.precoantigo - i.valorunitario) < 0.005)
                    or (h.codprodutoembalagem is null and pe.preco is null
                        and abs(round(h.precoantigo * coalesce(pe.quantidade, 1), 2) - i.valorunitario) < 0.005)
                )
            )
            order by i.codnegocioprodutobarra
        ', ['codnegocio' => $negocio->codnegocio, 'codusuario' => $negocio->codusuario]);

        $ret = [];
        foreach ($regs as $r) {
            $tipo = $r->valorunitario < $r->cadastro
                ? OcorrenciaService::TIPO_PRECO_ABAIXO
                : OcorrenciaService::TIPO_PRECO_ACIMA;
            $ret[$tipo][] = AuditoriaService::registrar(
                'tblnegocioprodutobarra',
                $r->codnegocioprodutobarra,
                AuditoriaService::TIPO_PRECO_CADASTRO,
                ['valorunitario' => round((float) $r->cadastro, 2)],
                ['valorunitario' => round((float) $r->valorunitario, 2)]
            );
        }
        return $ret;
    }

    private static function valesExcluidos(Negocio $negocio): array
    {
        return $negocio->NegocioValeS()
            ->whereNotNull('inativo')
            ->orderBy('codnegociovale')
            ->get()
            ->map(fn ($nv) => AuditoriaService::registrar(
                'tblnegociovale',
                $nv->codnegociovale,
                AuditoriaService::TIPO_VALE_EXCLUIDO,
                ['inativo' => null],
                ['inativo' => $nv->getRawOriginal('inativo')]
            ))
            ->all();
    }

    // Saida que nao gera financeiro fechada no caixa (uso e consumo, perda,
    // brinde): mercadoria saiu sem pagamento. Transferencia entre filiais e'
    // rotina e fica de fora. E' estado, sem auditoria.
    private static function semFinanceiro(Negocio $negocio): void
    {
        $nat = $negocio->NaturezaOperacao;
        if ($nat->financeiro || $nat->transferencia || $nat->codoperacao != static::SAIDA) {
            return;
        }
        OcorrenciaService::registrar([
            'tipo' => OcorrenciaService::TIPO_SEM_FINANCEIRO,
            'tabela' => 'tblnegocio',
            'codigo' => $negocio->codnegocio,
            'codnegocio' => $negocio->codnegocio,
            'codpdv' => $negocio->codpdv,
            'codfilial' => OcorrenciaService::filial($negocio->Pdv, $negocio->codfilial),
            'codusuario' => $negocio->codusuario,
            'descricao' => "Fechou em {$nat->naturezaoperacao} — R$ " . formataNumero($negocio->valortotal),
            'valor' => (float) $negocio->valortotal,
        ]);
    }

    // Desconto acima do maior entre o do cadastro do cliente e 5% sobre a
    // parte paga a vista (pix, dinheiro, debito). E' estado, sem auditoria.
    private static function desconto(Negocio $negocio): void
    {
        if (!$negocio->NaturezaOperacao->venda || !$negocio->NaturezaOperacao->financeiro) {
            return;
        }
        $desconto = round((float) $negocio->valordesconto, 2);
        if ($desconto <= 0) {
            return;
        }
        $percentualPessoa = (float) ($negocio->Pessoa->desconto ?? 0);
        $permitidoPessoa = round((float) $negocio->valorprodutos * $percentualPessoa / 100, 2);
        // principal = valor antes do desconto do pagamento, sem troco
        $baseAvista = (float) $negocio->PagamentoS()
            ->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)
            ->whereIn('meio', static::MEIOS_AVISTA)
            ->sum('principal');
        $permitidoAvista = round($baseAvista * static::DESCONTO_AVISTA / 100, 2);
        $permitido = max($permitidoPessoa, $permitidoAvista);
        $excesso = round($desconto - $permitido, 2);
        if ($excesso <= 0.01) {
            return;
        }
        $percentual = $negocio->valorprodutos > 0 ? $desconto / $negocio->valorprodutos * 100 : 0;
        OcorrenciaService::registrar([
            'tipo' => OcorrenciaService::TIPO_DESCONTO_ACIMA,
            'tabela' => 'tblnegocio',
            'codigo' => $negocio->codnegocio,
            'codnegocio' => $negocio->codnegocio,
            'codpdv' => $negocio->codpdv,
            'codfilial' => OcorrenciaService::filial($negocio->Pdv, $negocio->codfilial),
            'codusuario' => $negocio->codusuario,
            'descricao' => 'Desconto de R$ ' . formataNumero($desconto) . ' (' . formataNumero($percentual, 1) . '%)'
                . ', permitido R$ ' . formataNumero($permitido),
            'valor' => $excesso,
        ]);
    }

    // ---- na sincronizacao do PDV: o que o servidor sobrescreve ou apaga ----

    // Antes de gravar o item que veio do PDV: quantidade menor que a gravada
    // vai para a auditoria e para a ocorrencia; o aumento so' entra quando o
    // item ja' tinha diminuido (a volta fica visivel ao gerente)
    public static function quantidadeAlterada(Negocio $negocio, NegocioProdutoBarra $npb, array $item): void
    {
        if (!$npb->exists || !empty($npb->inativo) || !empty($item['inativo']) || !isset($item['quantidade'])) {
            return;
        }
        $nova = (float) $item['quantidade'];
        $atual = (float) $npb->quantidade;
        if (abs($nova - $atual) < 0.0005) {
            return;
        }
        if (!OcorrenciaService::monitorado($negocio->Pdv, $negocio->criacao)) {
            return;
        }
        $jaDiminuiu = Auditoria::where('tabela', 'tblnegocioprodutobarra')
            ->where('codigo', $npb->codnegocioprodutobarra)
            ->where('tipo', AuditoriaService::TIPO_QUANTIDADE_ALTERADA)
            ->exists();
        if ($nova > $atual && !$jaDiminuiu) {
            return;
        }
        $aud = AuditoriaService::registrar(
            'tblnegocioprodutobarra',
            $npb->codnegocioprodutobarra,
            AuditoriaService::TIPO_QUANTIDADE_ALTERADA,
            ['quantidade' => $atual, 'valortotal' => (float) $npb->valortotal],
            ['quantidade' => $nova, 'valortotal' => round((float) ($item['valortotal'] ?? 0), 2)]
        );
        OcorrenciaService::doNegocio($negocio, OcorrenciaService::TIPO_QUANTIDADE_DIMINUIDA, [$aud], Auth::user()->codusuario ?? null);
    }

    // Pagamento pendente que o PDV tirou (o servidor apaga a linha)
    public static function pagamentosApagados(Negocio $negocio, $pagamentos): void
    {
        if ($pagamentos->isEmpty() || !OcorrenciaService::monitorado($negocio->Pdv, $negocio->criacao)) {
            return;
        }
        $auditorias = $pagamentos->map(fn ($pag) => AuditoriaService::registrar(
            'tblpagamento',
            $pag->codpagamento,
            AuditoriaService::TIPO_PAGAMENTO_APAGADO,
            [
                'meio' => $pag->meio,
                'principal' => (float) $pag->principal,
                'desconto' => (float) $pag->desconto,
                'valortroco' => $pag->valortroco === null ? null : (float) $pag->valortroco,
                'total' => (float) $pag->total,
                'autorizacao' => $pag->autorizacao,
                'codmaquineta' => $pag->codmaquineta,
            ],
            null
        ))->all();
        OcorrenciaService::doNegocio($negocio, OcorrenciaService::TIPO_PAGAMENTO_APAGADO, $auditorias, Auth::user()->codusuario ?? null);
    }

    // Parcela a prazo (sem titulo) que o PDV tirou
    public static function parcelasApagadas(Negocio $negocio, $parcelas): void
    {
        if ($parcelas->isEmpty() || !OcorrenciaService::monitorado($negocio->Pdv, $negocio->criacao)) {
            return;
        }
        $auditorias = $parcelas->map(fn ($np) => AuditoriaService::registrar(
            'tblnegocioparcela',
            $np->codnegocioparcela,
            AuditoriaService::TIPO_PARCELA_APAGADA,
            [
                'condicao' => $np->condicao,
                'numero' => $np->numero,
                'vencimento' => Carbon::parse($np->vencimento)->format('Y-m-d'),
                'valor' => (float) $np->valor,
                'juros' => (float) $np->juros,
            ],
            null
        ))->all();
        OcorrenciaService::doNegocio($negocio, OcorrenciaService::TIPO_PARCELA_APAGADA, $auditorias, Auth::user()->codusuario ?? null);
    }
}
