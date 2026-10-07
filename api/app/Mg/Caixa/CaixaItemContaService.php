<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Portador\PortadorLancamentoService;
use Mg\Titulo\TituloService;

/**
 * Conta corrente da maquineta de parceiro (doc-4, "Itens de parceiro";
 * TASK-39 #22): o que devemos ao parceiro por aquela maquineta.
 *   Credito: o bordero que o caixa lanca (tipo M no movimento do portador).
 *   Debito: o titulo a pagar gerado aqui (tblcaixaitemacerto tipo T).
 *   Ajuste: com sinal e observacao (comissao que o parceiro desconta, saldo
 *   inicial) (tblcaixaitemacerto tipo A).
 * Cada botao faz uma coisa: gerar titulo so' cria o titulo (em aberto, pago
 * pelo caminho normal do contas) e o debito ligado a ele; cancelar o debito
 * recusa enquanto o titulo nao for estornado.
 */
class CaixaItemContaService
{
    // origem da linha do extrato
    const ORIGEM_BORDERO = 'B';

    private static function usuario(): ?int
    {
        return Auth::user()->codusuario ?? null;
    }

    public static function exigirMaquineta(CaixaItem $item): void
    {
        if (!$item->ehMaquineta()) {
            abort(422, "{$item->item} não é maquineta de parceiro: não tem conta corrente.");
        }
    }

    // trava o item: as escritas na conta corrente da maquineta sao em fila
    private static function travar(CaixaItem $item): void
    {
        CaixaItem::whereKey($item->codcaixaitem)->lockForUpdate()->first();
    }

    // o que devemos ao parceiro (antes de $antes; sem ele, tudo): borderos que
    // valem + acertos nao cancelados
    public static function saldo(CaixaItem $item, ?Carbon $antes = null): float
    {
        $antes = $antes ?? Carbon::create(9999);
        $r = DB::selectOne("
            select
                coalesce((
                    select sum(m.valor) from tblportadormovimento m
                    where m.codcaixaitem = :item1 and m.tipo = 'M' and m.estado <> 'C'
                    and m.transacao < :antes1
                ), 0)
                + coalesce((
                    select sum(a.valor) from tblcaixaitemacerto a
                    where a.codcaixaitem = :item2 and a.cancelamento is null
                    and a.transacao < :antes2
                ), 0) as saldo
        ", [
            'item1' => $item->codcaixaitem,
            'item2' => $item->codcaixaitem,
            'antes1' => $antes,
            'antes2' => $antes,
        ]);
        return round((float) $r->saldo, 2);
    }

    // o extrato de $de a $ate (dias inteiros; sem eles, os ultimos 60 dias):
    // saldo anterior, as linhas (canceladas tambem, sem saldo) com o saldo
    // corrente, o saldo no fim e o de hoje
    public static function extrato(CaixaItem $item, ?Carbon $de = null, ?Carbon $ate = null): array
    {
        $ate = ($ate ?? Carbon::today())->copy()->endOfDay();
        $de = ($de ?? $ate->copy()->subDays(60))->copy()->startOfDay();
        $regs = DB::select("
            select * from (
                select
                    'B' as origem,
                    m.codportadormovimento as codigo,
                    m.transacao,
                    m.valor,
                    m.estado = 'C' as cancelado,
                    m.observacoes,
                    m.justificativa,
                    m.codportador,
                    po.portador,
                    m.codportadorperiodo,
                    null::bigint as codtitulo,
                    null::varchar as numero,
                    null::numeric as titulosaldo,
                    null::timestamp as tituloestornado,
                    uc.usuario as usuariocriacao,
                    ucan.usuario as usuariocancelamento
                from tblportadormovimento m
                join tblportador po on po.codportador = m.codportador
                left join tblusuario uc on uc.codusuario = m.codusuariocriacao
                left join tblusuario ucan on ucan.codusuario = m.codusuariocancelamento
                where m.codcaixaitem = :item1 and m.tipo = 'M'
                and m.transacao between :de1 and :ate1
                union all
                select
                    a.tipo,
                    a.codcaixaitemacerto,
                    a.transacao,
                    a.valor,
                    a.cancelamento is not null,
                    a.observacoes,
                    a.justificativa,
                    null, null, null,
                    a.codtitulo,
                    t.numero,
                    t.saldo,
                    t.estornado,
                    uc.usuario,
                    ucan.usuario
                from tblcaixaitemacerto a
                left join tbltitulo t on t.codtitulo = a.codtitulo
                left join tblusuario uc on uc.codusuario = a.codusuariocriacao
                left join tblusuario ucan on ucan.codusuario = a.codusuariocancelamento
                where a.codcaixaitem = :item2
                and a.transacao between :de2 and :ate2
            ) x
            order by transacao, origem, codigo
        ", [
            'item1' => $item->codcaixaitem,
            'item2' => $item->codcaixaitem,
            'de1' => $de,
            'ate1' => $ate,
            'de2' => $de,
            'ate2' => $ate,
        ]);
        $anterior = static::saldo($item, $de);
        $saldo = $anterior;
        $linhas = array_map(function ($r) use (&$saldo) {
            $cancelado = (bool) $r->cancelado;
            if (!$cancelado) {
                $saldo = round($saldo + (float) $r->valor, 2);
            }
            $bordero = $r->origem == static::ORIGEM_BORDERO;
            $fotos = $bordero ? PortadorLancamentoService::fotos((int) $r->codigo) : [];
            return [
                'origem' => $r->origem,
                'texto' => static::texto($r),
                'codigo' => (int) $r->codigo,
                'transacao' => Carbon::parse($r->transacao)->toIso8601String(),
                'valor' => (float) $r->valor,
                'saldo' => $cancelado ? null : $saldo,
                'cancelado' => $cancelado,
                'observacoes' => $r->observacoes,
                'justificativa' => $r->justificativa,
                'codportador' => $r->codportador,
                'portador' => $r->portador,
                'codportadorperiodo' => $r->codportadorperiodo,
                'fotos' => $fotos,
                'semBordero' => $bordero && !$cancelado && empty($fotos),
                'codtitulo' => $r->codtitulo,
                'numero' => $r->numero,
                'titulosaldo' => $r->titulosaldo === null ? null : (float) $r->titulosaldo,
                'tituloestornado' => $r->tituloestornado ? Carbon::parse($r->tituloestornado)->toIso8601String() : null,
                'usuariocriacao' => $r->usuariocriacao,
                'usuariocancelamento' => $r->usuariocancelamento,
                // o bordero se cancela na tela do caixa
                'podeCancelar' => !$bordero && !$cancelado,
            ];
        }, $regs);
        return [
            'de' => $de->toDateString(),
            'ate' => $ate->toDateString(),
            'saldoanterior' => $anterior,
            'linhas' => $linhas,
            'saldofinal' => $saldo,
            'saldo' => static::saldo($item),
        ];
    }

    // "Borderô: Caixa 1", "Devolução: Caixa 1", "Título 2026-10-06", "Ajuste"
    private static function texto(object $r): string
    {
        switch ($r->origem) {
            case static::ORIGEM_BORDERO:
                return ($r->valor < 0 ? 'Devolução: ' : 'Borderô: ') . $r->portador;
            case CaixaItemAcerto::TIPO_TITULO:
                return "Título {$r->numero}";
        }
        return 'Ajuste';
    }

    // o titulo a pagar ao parceiro (pessoa, filial e conta do item), em aberto
    // e sem portador, e o debito ligado a ele
    public static function gerarTitulo(CaixaItem $item, float $valor, Carbon $vencimento, ?string $observacoes = null): CaixaItemAcerto
    {
        static::exigirMaquineta($item);
        static::travar($item);
        $valor = round($valor, 2);
        if ($valor <= 0) {
            abort(422, 'O valor do título precisa ser maior que zero.');
        }
        $agora = Carbon::now()->startOfSecond();
        if ($vencimento->copy()->startOfDay()->lt($agora->copy()->startOfDay())) {
            abort(422, 'O vencimento não pode ser no passado.');
        }
        $observacoes = trim($observacoes ?? '');
        $titulo = TituloService::criar([
            'codtipotitulo' => TituloService::TIPO_DUPLICATA_PAGAR,
            'codfilial' => $item->codfilial,
            'codportador' => null,
            'codpessoa' => $item->codpessoa,
            'codcontacontabil' => $item->codcontacontabil,
            'valor' => $valor,
            'transacao' => $agora->toDateString(),
            'emissao' => $agora->toDateString(),
            'vencimento' => $vencimento->toDateString(),
            'observacao' => mb_substr("Repasse da maquineta {$item->item}" . ($observacoes ? " - {$observacoes}" : ''), 0, 255),
        ]);
        return CaixaItemAcerto::create([
            'codcaixaitem' => $item->codcaixaitem,
            'tipo' => CaixaItemAcerto::TIPO_TITULO,
            'valor' => -$valor,
            'codtitulo' => $titulo->codtitulo,
            'transacao' => $agora,
            'observacoes' => $observacoes === '' ? null : mb_substr($observacoes, 0, 300),
        ]);
    }

    // valor com sinal: positivo aumenta o que devemos
    public static function ajustar(CaixaItem $item, float $valor, string $observacoes, ?Carbon $transacao = null): CaixaItemAcerto
    {
        static::exigirMaquineta($item);
        static::travar($item);
        $valor = round($valor, 2);
        if ($valor == 0) {
            abort(422, 'Informe o valor do ajuste.');
        }
        $observacoes = trim($observacoes);
        if (mb_strlen($observacoes) < 3) {
            abort(422, 'Diga o motivo do ajuste na observação.');
        }
        $agora = Carbon::now()->startOfSecond();
        $transacao = $transacao ?? $agora;
        if ($transacao->gt($agora)) {
            abort(422, 'A data não pode ser no futuro.');
        }
        return CaixaItemAcerto::create([
            'codcaixaitem' => $item->codcaixaitem,
            'tipo' => CaixaItemAcerto::TIPO_AJUSTE,
            'valor' => $valor,
            'transacao' => $transacao,
            'observacoes' => mb_substr($observacoes, 0, 300),
        ]);
    }

    // ajuste: so' cancela; titulo: so' depois de estornado (estornar e' no
    // titulo, nao aqui)
    public static function cancelar(CaixaItemAcerto $acerto, string $justificativa): CaixaItemAcerto
    {
        static::travar($acerto->CaixaItem);
        $acerto->refresh();
        if ($acerto->cancelamento) {
            abort(422, 'Lançamento já cancelado.');
        }
        if ($acerto->tipo == CaixaItemAcerto::TIPO_TITULO) {
            $titulo = $acerto->Titulo;
            if ($titulo && empty($titulo->estornado)) {
                abort(422, "Estorne o título {$titulo->numero} antes de cancelar o débito.");
            }
        }
        $justificativa = trim($justificativa);
        if (mb_strlen($justificativa) < 5) {
            abort(422, 'Diga por que está cancelando.');
        }
        $acerto->fill([
            'cancelamento' => Carbon::now(),
            'codusuariocancelamento' => static::usuario(),
            'justificativa' => mb_substr($justificativa, 0, 300),
        ]);
        $acerto->save();
        return $acerto;
    }
}
