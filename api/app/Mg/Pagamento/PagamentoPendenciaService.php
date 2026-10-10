<?php

namespace Mg\Pagamento;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Auditoria\AuditoriaService;
use Mg\Conferencia\ConferenciaService;
use Mg\Negocio\NegocioService;
use Mg\Portador\PortadorAutorizador;
use Mg\Portador\PortadorUsuario;
use Mg\Titulo\MovimentoTitulo;

/**
 * Pagamento = fato; amarracao = outra coisa (conceito do Fabio, 09/10/2026).
 *
 * O pagamento so' registra o dinheiro que andou. O que ele pagou fica nas
 * amarracoes: movimentos de titulo (baixa, vale, adiantamento) e a venda
 * (codnegocio, o pagamento inteiro, so' quando a venda esta' fechada).
 *
 * Pendente = o saldo do pagamento (pago - devolvido) nao bate com o que esta'
 * amarrado. E' uma conta, nao um status: desamarrar, cancelar a venda ou
 * devolver parte faz o pagamento aparecer sozinho.
 *
 * Fica de fora o que nao e' fato a amarrar: rascunho da venda aberta (P),
 * cancelado, devolucao (o efeito dela esta' no original), transferencia,
 * pagamento sem documento por motivo (taxa, tarifa, rendimento), acerto de
 * RH, meios internos (vale, compensacao, folha, permuta, perda) e o que e'
 * de antes do inicio do razao.
 */
class PagamentoPendenciaService
{
    // meios que nao sao dinheiro: nao ficam pendentes
    const MEIOS_INTERNOS = [
        PagamentoService::MEIO_VALE,
        PagamentoService::MEIO_COMPENSACAO,
        PagamentoService::MEIO_FOLHA,
        PagamentoService::MEIO_PERMUTA,
        PagamentoService::MEIO_PERDA,
    ];

    // movimentos de titulo que amarram (baixa, vale, adiantamento) e nao
    // foram estornados
    public static function movimentosAtivos(Pagamento $pag)
    {
        // a linha de estorno tem o tipo da original: quem diz que e' estorno
        // e' o codmovimentotituloestorno
        return MovimentoTitulo::where('codpagamento', $pag->codpagamento)
            ->where('codtipomovimentotitulo', '<', 900)
            ->whereNull('codmovimentotituloestorno')
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('tblmovimentotitulo as e')
                ->whereColumn('e.codmovimentotituloestorno', 'tblmovimentotitulo.codmovimentotitulo'))
            ->get();
    }

    // pago - devolvido (cancelamento no cartao, devolucao de PIX)
    public static function saldo(Pagamento $pag): float
    {
        $devolvido = Pagamento::where('codpagamentoorigem', $pag->codpagamento)
            ->where('estado', PagamentoService::ESTADO_EFETIVADO)
            ->sum('total');
        return round(abs((float) $pag->total) - abs((float) $devolvido), 2);
    }

    // o que esta' amarrado: titulos + a venda fechada
    public static function amarrado(Pagamento $pag): float
    {
        // a soma com sinal: receber e pagar no mesmo pagamento se compensam
        // (o pagamento e' o liquido deles)
        $titulos = abs(static::movimentosAtivos($pag)->sum(fn ($m) => (float) $m->total));
        $venda = 0;
        if (!empty($pag->codnegocio) && optional($pag->Negocio)->codnegociostatus == NegocioService::STATUS_FECHADO) {
            $venda = abs((float) $pag->total);
        }
        return round($titulos + $venda, 2);
    }

    // o que ainda da' para amarrar
    public static function livre(Pagamento $pag): float
    {
        return round(static::saldo($pag) - static::amarrado($pag), 2);
    }

    // venda aberta: o integrado dela esta' "em andamento", nao pendente
    public static function emAndamento(Pagamento $pag): bool
    {
        return !empty($pag->codnegocio)
            && optional($pag->Negocio)->codnegociostatus == NegocioService::STATUS_ABERTO;
    }

    // entra na conta de pendencia?
    public static function considerado(Pagamento $pag): bool
    {
        return $pag->estado == PagamentoService::ESTADO_EFETIVADO
            && !in_array((int) $pag->meio, static::MEIOS_INTERNOS)
            && empty($pag->codpagamentoorigem)
            && empty($pag->motivo)
            && empty($pag->codperiodocolaboradoracerto)
            && !(!empty($pag->codportadororigem) && !empty($pag->codportadordestino))
            && $pag->transacao && $pag->transacao->gte(ConferenciaService::inicio());
    }

    public static function pendente(Pagamento $pag): bool
    {
        return static::considerado($pag)
            && !static::emAndamento($pag)
            && abs(static::livre($pag)) > 0.005;
    }

    // Pendentes (tela "Pagamentos nao resolvidos" e a forma "Ja' recebido"
    // do wizard). $filtros: codpessoa, codfilial, sentido ('entrada' |
    // 'saida'), codpagamento, so' os portadores em que o usuario tem papel.
    // Cada linha: o pagamento, saldo, amarrado e livre.
    public static function listar(array $filtros = []): array
    {
        // a pessoa e a filial do contexto vem primeiro (o limite de 500 nao
        // esconde o pagamento do cliente que esta' no caixa)
        $binds = [
            'inicio' => ConferenciaService::inicio()->format('Y-m-d H:i:s'),
            'primeiropessoa' => !empty($filtros['codpessoaprimeiro']) ? (int) $filtros['codpessoaprimeiro'] : null,
            'primeirofilial' => !empty($filtros['codfilialprimeiro']) ? (int) $filtros['codfilialprimeiro'] : null,
        ];
        $where = [];
        if (!empty($filtros['codpessoa'])) {
            $where[] = 'p.codpessoa = :codpessoa';
            $binds['codpessoa'] = (int) $filtros['codpessoa'];
        }
        if (!empty($filtros['codfilial'])) {
            $where[] = 'p.codfilial = :codfilial';
            $binds['codfilial'] = (int) $filtros['codfilial'];
        }
        if (!empty($filtros['codportador'])) {
            $where[] = 'coalesce(p.codportadordestino, p.codportadororigem) = :codportador';
            $binds['codportador'] = (int) $filtros['codportador'];
        }
        if (!empty($filtros['codpagamento'])) {
            $where[] = 'p.codpagamento = :codpagamento';
            $binds['codpagamento'] = (int) $filtros['codpagamento'];
        }
        if (($filtros['sentido'] ?? null) === 'entrada') {
            $where[] = 'p.codportadordestino is not null';
        } elseif (($filtros['sentido'] ?? null) === 'saida') {
            $where[] = 'p.codportadororigem is not null';
        }
        $meus = PortadorAutorizador::codportadores(PortadorUsuario::PAPEL_DEPOSITANTE);
        if ($meus !== null) {
            if (empty($meus)) {
                return [];
            }
            $where[] = 'coalesce(p.codportadordestino, p.codportadororigem) in (' . implode(',', array_map('intval', $meus)) . ')';
        }
        $internos = implode(',', static::MEIOS_INTERNOS);
        $sql = "
            with base as (
                select
                    p.codpagamento,
                    abs(p.total) - coalesce((
                        select abs(sum(d.total)) from tblpagamento d
                        where d.codpagamentoorigem = p.codpagamento and d.estado = 'E'
                    ), 0) as saldo,
                    abs(coalesce((
                        select sum(m.total) from tblmovimentotitulo m
                        where m.codpagamento = p.codpagamento
                          and m.codtipomovimentotitulo < 900
                          and m.codmovimentotituloestorno is null
                          and not exists (
                              select 1 from tblmovimentotitulo e
                              where e.codmovimentotituloestorno = m.codmovimentotitulo
                          )
                    ), 0))
                    + case when n.codnegociostatus = " . NegocioService::STATUS_FECHADO . " then abs(p.total) else 0 end
                    as amarrado
                from tblpagamento p
                left join tblnegocio n on (n.codnegocio = p.codnegocio)
                where p.estado = 'E'
                  and p.meio not in ({$internos})
                  and p.codpagamentoorigem is null
                  and p.motivo is null
                  and p.codperiodocolaboradoracerto is null
                  and not (p.codportadororigem is not null and p.codportadordestino is not null)
                  and p.transacao >= :inicio
                  and coalesce(n.codnegociostatus, 0) != " . NegocioService::STATUS_ABERTO . "
                  " . (empty($where) ? '' : ' and ' . implode(' and ', $where)) . "
            )
            select b.codpagamento, b.saldo, b.amarrado, round(b.saldo - b.amarrado, 2) as livre
            from base b
            join tblpagamento pp on (pp.codpagamento = b.codpagamento)
            where abs(b.saldo - b.amarrado) > 0.005
            order by
                (coalesce(pp.codpessoa, 0) = coalesce(cast(:primeiropessoa as bigint), -1)) desc,
                (coalesce(pp.codfilial, 0) = coalesce(cast(:primeirofilial as bigint), -1)) desc,
                b.codpagamento desc
            limit 500
        ";
        $linhas = collect(DB::select($sql, $binds))->keyBy('codpagamento');
        if ($linhas->isEmpty()) {
            return [];
        }
        $pags = Pagamento::with(PagamentoListaService::RELACOES)
            ->whereIn('codpagamento', $linhas->keys())
            ->orderBy('codpagamento', 'desc')
            ->get();
        return $pags->map(fn ($pag) => [
            'pagamento' => $pag,
            'saldo' => (float) $linhas[$pag->codpagamento]->saldo,
            'amarrado' => (float) $linhas[$pag->codpagamento]->amarrado,
            'livre' => (float) $linhas[$pag->codpagamento]->livre,
        ])->all();
    }

    // linhas para a tela e para a forma "Ja' recebido" do wizard
    public static function formatar(array $linhas): array
    {
        return array_map(function ($l) {
            $pag = $l['pagamento'];
            $portador = $pag->PortadorDestino ?? $pag->PortadorOrigem;
            return [
                'codpagamento' => (int) $pag->codpagamento,
                'uuid' => $pag->uuid,
                'meio' => $pag->meio,
                'meiodescricao' => PagamentoService::descricao($pag),
                // hora de Cuiaba sem fuso, como o detalhe e o extrato
                'transacao' => optional($pag->transacao)->format('Y-m-d\TH:i:s'),
                'entrada' => !empty($pag->codportadordestino),
                'total' => (float) $pag->total,
                'saldo' => $l['saldo'],
                'amarrado' => $l['amarrado'],
                'livre' => $l['livre'],
                'codpessoa' => $pag->codpessoa,
                'pessoa' => optional($pag->Pessoa)->fantasia,
                'codportador' => optional($portador)->codportador,
                'portador' => optional($portador)->portador,
                'codfilial' => $pag->codfilial,
                'maquineta' => optional($pag->Maquineta)->apelido,
                'autorizacao' => $pag->autorizacao,
                'nsu' => $pag->nsu,
                'bandeira' => $pag->bandeira,
                'codnegocio' => $pag->codnegocio,
                // veio do banco ou da maquineta (PIX pela chave, PIX QR, Stone,
                // SafraPay, boleto): nao se cancela; pode ser o "ja' lancado"
                'integrado' => !PagamentoTituloService::manual($pag),
                // o que o usuario pode fazer: amarrar (ja' filtrado: depositante)
                // e, como operador do portador, devolver e casar com o digitado
                'operador' => PortadorAutorizador::pode(
                    (int) ($pag->codportadordestino ?? $pag->codportadororigem),
                    PortadorUsuario::PAPEL_OPERADOR
                ),
                'codpdv' => $pag->codpdv,
                'pdv' => optional($pag->Pdv)->apelido,
            ];
        }, $linhas);
    }

    // ---- dar destino ao pagamento nao resolvido ----

    // quem da' destino: operador (ou gestor) do portador do pagamento
    public static function autorizar(Pagamento $pag, string $acao): void
    {
        $portador = $pag->portadorDoPagamento();
        if ($portador) {
            PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, $acao);
        }
    }

    // "Ja' lancado": o digitado que casa com o integrado (a adquirente
    // atrasou, o caixa digitou pelo comprovante): mesmo sentido e valor,
    // manual, efetivado, ate' 3 dias de diferenca; no cartao, a mesma
    // autorizacao quando os dois tem
    public static function duplicados(Pagamento $integrado): array
    {
        $entrada = !empty($integrado->codportadordestino);
        $dia = $integrado->transacao->copy();
        $q = Pagamento::with(['Maquineta:codmaquineta,apelido', 'Pessoa:codpessoa,fantasia', 'Negocio:codnegocio,codnegociostatus'])
            ->where('estado', PagamentoService::ESTADO_EFETIVADO)
            ->where('codpagamento', '!=', $integrado->codpagamento)
            ->whereBetween('total', [(float) $integrado->total - 0.005, (float) $integrado->total + 0.005])
            ->whereBetween('transacao', [$dia->copy()->subDays(3), $dia->copy()->addDays(3)])
            ->whereNull('codpixcob')->whereNull('codpix')->whereNull('codpagarmepedido')
            ->whereNull('codsauruspedido')->whereNull('codliopedido')
            ->whereNull('codpagamentoorigem');
        $q->whereNotNull($entrada ? 'codportadordestino' : 'codportadororigem');
        if (in_array((int) $integrado->meio, PagamentoService::MEIOS_CARTAO)) {
            $q->whereIn('meio', PagamentoService::MEIOS_CARTAO);
            if (!empty($integrado->autorizacao)) {
                $q->where(fn ($w) => $w->whereNull('autorizacao')->orWhere('autorizacao', $integrado->autorizacao));
            }
        } elseif (in_array((int) $integrado->meio, PagamentoTituloService::MEIOS_BANCO)) {
            // no banco o digitado e' transferencia, deposito, PIX ou boleto, na
            // mesma conta
            $q->whereIn('meio', PagamentoTituloService::MEIOS_BANCO);
            $portador = $integrado->codportadordestino ?? $integrado->codportadororigem;
            $q->where(fn ($w) => $w->where('codportadordestino', $portador)->orWhere('codportadororigem', $portador));
        } else {
            $q->where('meio', $integrado->meio);
        }
        return $q->orderByRaw('abs(extract(epoch from transacao - ?))', [$dia])->limit(20)->get()
            ->filter(fn ($p) => PagamentoTituloService::manual($p))
            ->map(fn ($p) => [
                'codpagamento' => (int) $p->codpagamento,
                'meiodescricao' => PagamentoService::descricao($p),
                'transacao' => optional($p->transacao)->format('Y-m-d\TH:i:s'),
                'total' => (float) $p->total,
                'maquineta' => optional($p->Maquineta)->apelido,
                'autorizacao' => $p->autorizacao,
                'pessoa' => optional($p->Pessoa)->fantasia,
                'codnegocio' => $p->codnegocio,
                'titulos' => static::movimentosAtivos($p)->count(),
            ])->values()->all();
    }

    // O integrado e o digitado sao o mesmo dinheiro: fica o integrado (o fato
    // confirmado), as amarracoes do digitado (titulos e venda) passam para
    // ele, e o digitado e' cancelado como registro indevido (auditoria).
    public static function jaLancado(Pagamento $integrado, Pagamento $digitado, string $justificativa): Pagamento
    {
        $integrado = Pagamento::lockForUpdate()->findOrFail($integrado->codpagamento);
        $digitado = Pagamento::lockForUpdate()->findOrFail($digitado->codpagamento);
        if ($integrado->estado != PagamentoService::ESTADO_EFETIVADO || $digitado->estado != PagamentoService::ESTADO_EFETIVADO) {
            abort(422, 'Os dois pagamentos precisam estar efetivados!');
        }
        if (PagamentoTituloService::manual($integrado)) {
            abort(422, "O pagamento {$integrado->codpagamento} não é integrado: cancele o que foi digitado errado.");
        }
        if (!PagamentoTituloService::manual($digitado)) {
            abort(422, "O pagamento {$digitado->codpagamento} também é integrado!");
        }
        if (abs((float) $integrado->total - (float) $digitado->total) > 0.005
            || empty($integrado->codportadordestino) != empty($digitado->codportadordestino)) {
            abort(422, 'Os pagamentos não têm o mesmo valor e sentido!');
        }
        if (static::amarrado($integrado) > 0.005 || !empty($integrado->codnegocio)) {
            abort(422, "O pagamento {$integrado->codpagamento} já está amarrado!");
        }
        // o lote da maquineta e o caixa do digitado ainda abertos
        \Mg\Conferencia\PagamentoCorrecaoService::exigirAberta($digitado);

        $portador = $integrado->codportadordestino ?? $integrado->codportadororigem;
        foreach (static::movimentosAtivos($digitado) as $mov) {
            $mov->codpagamento = $integrado->codpagamento;
            $mov->codportador = $portador;
            $mov->save();
        }
        if (!empty($digitado->codnegocio)) {
            $codnegocio = $digitado->codnegocio;
            PagamentoService::amarrarVenda($digitado, null, "É o mesmo que o pagamento {$integrado->codpagamento}");
            PagamentoService::amarrarVenda($integrado, $codnegocio, "Era o pagamento {$digitado->codpagamento}, digitado");
        }
        if (empty($integrado->codpessoa) && !empty($digitado->codpessoa)) {
            $integrado->codpessoa = $digitado->codpessoa;
            $integrado->save();
        }
        $antes = ['estado' => $digitado->estado, 'indevido' => (bool) $digitado->indevido];
        $digitado->indevido = true;
        PagamentoService::cancelar($digitado, "Registro indevido: é o mesmo que o pagamento {$integrado->codpagamento}. {$justificativa}");
        AuditoriaService::registrar(
            'tblpagamento',
            $digitado->codpagamento,
            AuditoriaService::TIPO_REGISTRO_INDEVIDO,
            $antes,
            ['estado' => $digitado->estado, 'indevido' => true, 'codpagamentointegrado' => $integrado->codpagamento],
            $justificativa
        );
        return PagamentoListaService::carregar($integrado->codpagamento);
    }

    // Devolver: o PIX ou o cartao entrou por engano; registra a devolucao do
    // PIX / o cancelamento no cartao (pagamento contrario, sai do banco ou da
    // adquirente), no valor que esta' livre ou menos
    public static function devolver(Pagamento $pag, float $valor, string $justificativa): Pagamento
    {
        $pag = Pagamento::lockForUpdate()->findOrFail($pag->codpagamento);
        if ($pag->estado != PagamentoService::ESTADO_EFETIVADO || empty($pag->codportadordestino)) {
            abort(422, 'Só se devolve um recebimento efetivado!');
        }
        if (!in_array((int) $pag->meio, [PagamentoService::MEIO_CREDITO, PagamentoService::MEIO_DEBITO, PagamentoService::MEIO_PIX])) {
            abort(422, 'Devolver é para PIX e cartão; o resto se cancela.');
        }
        PagamentoTituloService::exigirCentavos($valor);
        $livre = static::livre($pag);
        if ($valor <= 0 || $valor > $livre + 0.005) {
            abort(422, 'Só dá para devolver o que está livre: R$ ' . number_format($livre, 2, ',', '.'));
        }
        $origem = ($pag->meio == PagamentoService::MEIO_PIX)
            ? $pag->codportadordestino
            : (PagamentoTituloService::portadorDaMaquineta($pag->Maquineta)->codportador ?? $pag->codportadordestino);
        $dev = PagamentoService::contrario($pag, [
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'transacao' => Carbon::now(),
            'efetivacao' => Carbon::now(),
            'codusuarioefetivacao' => auth()->user()->codusuario ?? null,
            'principal' => round($valor, 2),
            'juros' => 0,
            'multa' => 0,
            'desconto' => 0,
            'codnegocio' => null,
            'codportadororigem' => $origem,
            'codportadordestino' => null,
            'parcelas' => null,
            'observacoes' => $justificativa,
        ]);
        return PagamentoListaService::carregar($dev->codpagamento);
    }
}
