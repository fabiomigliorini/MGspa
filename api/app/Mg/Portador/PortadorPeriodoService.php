<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Caixa\CaixaItemService;
use Mg\Caixa\CaixaService;
use Mg\Conferencia\ConferenciaService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;

/**
 * Periodos do portador (doc-4, redefinicao do dinheiro).
 *
 * Especie (gaveta, cofre, troco, Caixa Financeiro): abre, movimenta, conta e
 * fecha. Estados: aberto (sem fim), pendente (com fim, sem fechamento: so'
 * correcao) e fechado. O saldo inicial e' a contagem final do anterior
 * (cedulas e moedas e os itens do caixa, que contam como cedula); a
 * contagem inicial so' confere. Fechar grava a
 * diferenca (contagem final - saldo final): dentro da tolerancia do portador
 * fecha, acima fica pendente. Reabrir (do mais novo para o mais antigo) deixa
 * corrigir a contagem final. Dividir e unificar.
 *
 * Banco, adquirente, cartao: o periodo corrente nasce sozinho (decisao 18); o
 * gestor fecha com corte e reabre (M12); taxa, tarifa e rendimento sao
 * pagamento avulso (lancar).
 *
 * Papel no portador (PortadorAutorizador): abrir, contar e fechar = operador;
 * reabrir, datas, dividir, unificar = gestor. `$livre` = a gaveta do PDV que
 * esta' pedindo: o PDV nao valida o papel nela.
 */
class PortadorPeriodoService
{
    private static function autorizar(Portador $portador, string $papel, string $acao, ?int $livre): void
    {
        if ($portador->codportador != $livre) {
            PortadorAutorizador::autorizar($portador, $papel, $acao);
        }
    }

    private static function travar(int $codportador): void
    {
        Portador::where('codportador', $codportador)->lockForUpdate()->first();
    }

    public static function descricao(PortadorPeriodo $periodo): string
    {
        if ($periodo->Portador->ehCaixa()) {
            return 'período de ' . $periodo->inicio->format('d/m/Y H:i');
        }
        return empty($periodo->fim)
            ? 'período corrente'
            : 'período de ' . $periodo->inicio->format('d/m/Y') . ' a ' . $periodo->fim->format('d/m/Y');
    }

    // o razao nao mexe mais no periodo fechado
    public static function imutavel(PortadorPeriodo $periodo): bool
    {
        return $periodo->fechado();
    }

    public static function anterior(PortadorPeriodo $periodo): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $periodo->codportador)
            ->where(fn ($q) => $q->where('inicio', '<', $periodo->inicio)
                ->orWhere(fn ($q) => $q->where('inicio', $periodo->inicio)
                    ->where('codportadorperiodo', '<', $periodo->codportadorperiodo)))
            ->orderBy('inicio', 'desc')
            ->orderBy('codportadorperiodo', 'desc')
            ->first();
    }

    public static function seguinte(PortadorPeriodo $periodo): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $periodo->codportador)
            ->where(fn ($q) => $q->where('inicio', '>', $periodo->inicio)
                ->orWhere(fn ($q) => $q->where('inicio', $periodo->inicio)
                    ->where('codportadorperiodo', '>', $periodo->codportadorperiodo)))
            ->orderBy('inicio')
            ->orderBy('codportadorperiodo')
            ->first();
    }

    // soma das linhas que valem do periodo (ate' uma data, se informada): a
    // do pagamento nao trocada; ajuste e transferencia nao cancelados
    public static function movimento(PortadorPeriodo $periodo, ?Carbon $ate = null): float
    {
        $q = DB::table('tblportadormovimento')
            ->where('codportadorperiodo', $periodo->codportadorperiodo)
            ->whereNull('inativo')
            ->whereRaw("coalesce(estado, 'E') <> 'C'");
        if ($ate) {
            $q->where('transacao', '<=', $ate->format('Y-m-d H:i:s'));
        }
        return round((float) $q->sum('valor'), 2);
    }

    // ==== saldos ====

    // o contado no momento: cedulas e moedas mais os itens (contam como
    // cedula); null sem contagem
    public static function contado(PortadorPeriodo $periodo, string $momento): ?float
    {
        $contagem = $periodo->{"contagem{$momento}"};
        $itens = $periodo->{"contagemitens{$momento}"};
        if ($contagem === null && $itens === null) {
            return null;
        }
        return round(CaixaService::totalContagem($contagem) + CaixaItemService::totalContagem($itens), 2);
    }

    // o saldo inicial do periodo seguinte: a contagem final (especie; sem
    // contagem, como no corte do dividir, o saldo final)
    public static function saldoInicialSeguinte(PortadorPeriodo $periodo): float
    {
        if ($periodo->Portador->ehCaixa()) {
            $contado = static::contado($periodo, 'final');
            if ($contado !== null) {
                return $contado;
            }
        }
        return round((float) $periodo->saldofinal, 2);
    }

    // saldo final = inicial + movimento; na especie, a diferenca da contagem
    // final. O fechado fica congelado. Do periodo em diante: o saldo inicial de
    // cada um vem do anterior. Grava o do ultimo em tblportador.saldo. Devolve
    // os periodos do portador do informado em diante.
    public static function recalcular(PortadorPeriodo $periodo): Collection
    {
        static::travar($periodo->codportador);
        $caixa = $periodo->Portador->ehCaixa();
        $periodos = static::desde($periodo);
        $anterior = static::anterior($periodo);
        foreach ($periodos as $i => $p) {
            $p->setRelation('Portador', $periodo->Portador);
            if (!$caixa && $i > 0 && $p->fechado()) {
                // banco: os fechados sao um prefixo
                break;
            }
            if (!$p->fechado()) {
                if ($anterior) {
                    $anterior->setRelation('Portador', $periodo->Portador);
                    $p->saldoinicial = static::saldoInicialSeguinte($anterior);
                }
                $p->saldofinal = round((float) $p->saldoinicial + static::movimento($p), 2);
                if ($caixa) {
                    $contado = static::contado($p, 'final');
                    $p->diferenca = $contado === null || $p->aberto() ? null : round($contado - $p->saldofinal, 2);
                }
                if ($p->isDirty(['saldoinicial', 'saldofinal', 'diferenca'])) {
                    $p->save();
                }
            }
            $anterior = $p;
        }
        $ultimo = PortadorPeriodo::where('codportador', $periodo->codportador)
            ->orderBy('inicio', 'desc')
            ->orderBy('codportadorperiodo', 'desc')
            ->first();
        DB::table('tblportador')
            ->where('codportador', $periodo->codportador)
            ->update(['saldo' => round((float) ($ultimo->saldofinal ?? 0), 2)]);
        return $periodos;
    }

    // o periodo e os seguintes do mesmo portador
    public static function desde(PortadorPeriodo $periodo): Collection
    {
        return PortadorPeriodo::where('codportador', $periodo->codportador)
            ->where(fn ($q) => $q->where('inicio', '>', $periodo->inicio)
                ->orWhere(fn ($q) => $q->where('inicio', $periodo->inicio)
                    ->where('codportadorperiodo', '>=', $periodo->codportadorperiodo)))
            ->orderBy('inicio')
            ->orderBy('codportadorperiodo')
            ->get();
    }

    // R14: os periodos que um pagamento mexe (o de cada linha do razao, ativa
    // ou nao) e os seguintes de cada portador, atualizados
    public static function afetados(int $codpagamento): Collection
    {
        $periodos = PortadorPeriodo::whereIn('codportadorperiodo', PortadorMovimento::where('codpagamento', $codpagamento)
            ->select('codportadorperiodo'))
            ->get();
        return static::comSeguintes($periodos);
    }

    // os periodos das linhas (ajuste, transferencia) e os seguintes
    public static function afetadosPor(Collection $linhas): Collection
    {
        return static::comSeguintes(PortadorPeriodo::whereIn('codportadorperiodo', $linhas->pluck('codportadorperiodo')->unique())->get());
    }

    public static function comSeguintes(Collection $periodos): Collection
    {
        return $periodos
            ->groupBy('codportador')
            ->flatMap(fn ($doPortador) => static::desde($doPortador->sortBy('inicio')->first()))
            ->values();
    }

    // ==== especie: abrir, contar, fechar, reabrir, datas, dividir, unificar ====

    private static function exigirCaixa(Portador $portador): void
    {
        if (!$portador->ehCaixa()) {
            abort(422, "{$portador->portador} não é portador em espécie.");
        }
    }

    // abre; o saldo inicial vem da contagem final do anterior (no primeiro
    // periodo do portador, da contagem inicial). So' um aberto por portador
    public static function abrir(Portador $portador, ?Carbon $inicio = null, ?array $contagem = null, ?array $itens = null, ?string $observacoes = null, ?int $livre = null): PortadorPeriodo
    {
        static::exigirCaixa($portador);
        static::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Abrir', $livre);
        static::travar($portador->codportador);
        if (CaixaService::sessaoAberta($portador->codportador)) {
            abort(422, "{$portador->portador} já está aberto.");
        }
        $anterior = CaixaService::ultimaSessao($portador->codportador);
        $inicio = $inicio ?? Carbon::now()->startOfSecond();
        static::exigirDatas($portador->codportador, null, $inicio, null);
        if ($anterior) {
            $anterior->setRelation('Portador', $portador);
            $saldo = static::saldoInicialSeguinte($anterior);
        } else {
            $saldo = $contagem === null ? 0.0 : round(CaixaService::totalContagem(CaixaService::contagem($contagem)[0])
                + CaixaItemService::totalContagem(CaixaItemService::contagem($itens)), 2);
        }
        $periodo = PortadorPeriodo::create([
            'codportador' => $portador->codportador,
            'inicio' => $inicio,
            'codusuarioabertura' => Auth::user()->codusuario ?? null,
            'saldoinicial' => $saldo,
            'saldofinal' => $saldo,
            'observacoes' => empty(trim($observacoes ?? '')) ? null : trim($observacoes),
        ]);
        $periodo->setRelation('Portador', $portador);
        // a contagem inicial nasce com a final do anterior (quem abre confere)
        if ($contagem === null && $anterior && $anterior->contagemfinal !== null) {
            $contagem = $anterior->contagemfinal;
            $itens = $anterior->contagemitensfinal;
        }
        if ($contagem !== null) {
            static::gravarContagem($periodo, 'inicial', $contagem, $itens);
        }
        static::recalcular($periodo);
        return $periodo->fresh();
    }

    // o inicio de um periodo novo quando ninguem informa: o segundo seguinte
    // ao fim do anterior, nunca depois de agora (fechou neste segundo: comeca
    // no proprio fim; o lancamento desse segundo cai no novo, o de inicio mais
    // recente); sem periodo, o comeco de hoje
    public static function inicioDoNovo(Portador $portador): Carbon
    {
        $anterior = CaixaService::ultimaSessao($portador->codportador);
        if ($anterior && $anterior->fim) {
            return $anterior->fim->copy()->addSecond()->min(Carbon::now()->startOfSecond());
        }
        return Carbon::today();
    }

    // contagem inicial (so' confere) ou final (da' a diferenca e o saldo
    // inicial do seguinte) do periodo nao fechado; sem contagem, limpa
    public static function contar(PortadorPeriodo $periodo, string $momento, ?array $contagem, ?array $itens = null, ?int $livre = null): PortadorPeriodo
    {
        $portador = $periodo->Portador;
        static::exigirCaixa($portador);
        static::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Contar', $livre);
        static::travar($portador->codportador);
        $periodo->refresh();
        if ($periodo->fechado()) {
            abort(422, 'Período fechado: reabra para mudar a contagem.');
        }
        $seguinte = null;
        if ($momento == 'final') {
            $seguinte = static::seguinte($periodo);
            if ($seguinte && $seguinte->fechado()) {
                abort(422, 'O ' . static::descricao($seguinte) . ' já foi fechado e começa com esta contagem: reabra ele antes.');
            }
        }
        $finalAntes = $periodo->contagemfinal;
        $finalItensAntes = $periodo->contagemitensfinal;
        static::gravarContagem($periodo, $momento, $contagem, $itens);
        // a contagem inicial do seguinte acompanha a final deste, a menos que
        // as duas ja' fossem diferentes (quem abriu contou outra coisa)
        if ($seguinte && static::mesmaContagem(
            $seguinte->contageminicial,
            $seguinte->contagemitensinicial,
            $finalAntes,
            $finalItensAntes
        )) {
            $seguinte->contageminicial = $periodo->contagemfinal;
            $seguinte->contagemitensinicial = $periodo->contagemitensfinal;
            $seguinte->save();
        }
        // primeiro periodo do portador: a contagem inicial e' o saldo inicial
        if ($momento == 'inicial' && !static::anterior($periodo)) {
            $periodo->saldoinicial = static::contado($periodo, 'inicial') ?? 0;
            $periodo->save();
        }
        static::recalcular($periodo);
        CaixaItemService::recalcularSaldos($portador->codportador);
        return $periodo->fresh();
    }

    // a mesma quantidade de cada cedula e moeda e as mesmas linhas de cada
    // item
    private static function mesmaContagem(?array $a, ?array $itensA, ?array $b, ?array $itensB): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }
        return CaixaService::contagem($a)[0] == CaixaService::contagem($b)[0]
            && CaixaItemService::contagem($itensA) == CaixaItemService::contagem($itensB);
    }

    // grava a contagem do momento: as cedulas e moedas e os itens. Sem
    // contagem, limpa as duas; itens null = mantem os de antes (a tela do caixa
    // do PDV ainda nao conta os itens)
    private static function gravarContagem(PortadorPeriodo $periodo, string $momento, ?array $contagem, ?array $itens): void
    {
        [$limpa] = CaixaService::contagem($contagem);
        $periodo->{"contagem{$momento}"} = $contagem === null ? null : $limpa;
        if ($contagem === null) {
            $periodo->{"contagemitens{$momento}"} = null;
        } elseif ($itens !== null) {
            $periodo->{"contagemitens{$momento}"} = CaixaItemService::contagem($itens);
        }
        $periodo->save();
    }

    // fecha: grava o fim (sem fim, agora) e a diferenca da contagem final.
    // Dentro da tolerancia, fecha; acima, fica pendente (o mesmo fechar tenta
    // de novo depois da correcao). Recusa, sem mexer em nada, com periodo
    // anterior por fechar ou transferencia a confirmar.
    public static function fecharCaixa(PortadorPeriodo $periodo, ?array $contagem = null, ?array $itens = null, ?string $observacoes = null, ?Carbon $fim = null, ?int $livre = null): PortadorPeriodo
    {
        $portador = $periodo->Portador;
        static::exigirCaixa($portador);
        static::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Fechar', $livre);
        static::travar($portador->codportador);
        $periodo->refresh();
        if ($periodo->fechado()) {
            abort(422, 'Período já fechado.');
        }
        // fecha-se do mais antigo para o mais novo: recusa sem mexer em nada
        $anterior = PortadorPeriodo::where('codportador', $portador->codportador)
            ->where('inicio', '<', $periodo->inicio)
            ->whereNull('fechamento')
            ->orderBy('inicio')
            ->first();
        if ($anterior) {
            abort(422, 'Feche antes o ' . static::descricao($anterior) . ' (fecha-se do mais antigo para o mais novo).');
        }
        $pendentes = PortadorLancamentoService::pendentes($periodo);
        if ($pendentes) {
            abort(422, "Há {$pendentes} transferência(s) a confirmar neste período: confirme ou cancele antes de fechar.");
        }
        if ($contagem !== null) {
            static::gravarContagem($periodo, 'final', $contagem, $itens);
        }
        $agora = Carbon::now()->startOfSecond();
        $fim = $periodo->fim ?? $fim ?? $agora;
        static::exigirDatas($portador->codportador, $periodo->codportadorperiodo, $periodo->inicio, $fim);
        $saldo = round((float) $periodo->saldoinicial + static::movimento($periodo, $fim), 2);
        $contado = static::contado($periodo, 'final');
        if ($contado === null) {
            if ($saldo != 0) {
                abort(422, 'Conte o dinheiro antes de fechar (saldo final R$ ' . number_format($saldo, 2, ',', '.') . ').');
            }
            $contado = 0.0;
            $periodo->contagemfinal = [];
        }
        $diferenca = round($contado - $saldo, 2);
        $periodo->fim = $fim;
        $periodo->saldofinal = $saldo;
        $periodo->diferenca = $diferenca;
        if ($observacoes !== null) {
            $periodo->observacoes = trim($observacoes) ?: null;
        }
        if (abs($diferenca) <= round((float) $portador->tolerancia, 2)) {
            $periodo->fechamento = $agora;
            $periodo->codusuariofechamento = Auth::user()->codusuario ?? null;
        }
        $periodo->save();
        static::recalcular($periodo);
        CaixaItemService::recalcularSaldos($portador->codportador);
        return $periodo->fresh();
    }

    // gestor; do mais novo para o mais antigo. O ultimo volta a ficar aberto;
    // um anterior fica pendente (mantem o fim). Deixa corrigir a contagem final
    public static function reabrirCaixa(PortadorPeriodo $periodo): PortadorPeriodo
    {
        $portador = $periodo->Portador;
        static::exigirCaixa($portador);
        PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_GESTOR, 'Reabrir');
        static::travar($portador->codportador);
        $periodo->refresh();
        if (!$periodo->fechado()) {
            abort(422, 'O período não está fechado.');
        }
        $posterior = PortadorPeriodo::where('codportador', $periodo->codportador)
            ->where('inicio', '>', $periodo->inicio)
            ->whereNotNull('fechamento')
            ->orderBy('inicio', 'desc')
            ->first();
        if ($posterior) {
            abort(422, 'Reabra antes o ' . static::descricao($posterior) . ' (reabre-se do mais novo para o mais antigo).');
        }
        $ultimo = !static::seguinte($periodo);
        $periodo->fill([
            'fim' => $ultimo ? null : $periodo->fim,
            'fechamento' => null,
            'codusuariofechamento' => null,
        ]);
        $periodo->save();
        static::recalcular($periodo);
        CaixaItemService::recalcularSaldos($portador->codportador);
        return $periodo->fresh();
    }

    // corrige inicio, fim (so' quem ja' tem) e observacoes do nao fechado
    public static function editarDatas(PortadorPeriodo $periodo, Carbon $inicio, ?Carbon $fim, ?string $observacoes = null): PortadorPeriodo
    {
        $portador = $periodo->Portador;
        static::exigirCaixa($portador);
        PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_GESTOR, 'Mudar início e fim');
        static::travar($portador->codportador);
        $periodo->refresh();
        if ($periodo->fechado()) {
            abort(422, 'Período fechado: reabra para mudar o início e o fim.');
        }
        $fim = $periodo->aberto() ? null : ($fim ?? $periodo->fim);
        static::exigirDatas($periodo->codportador, $periodo->codportadorperiodo, $inicio, $fim);
        $periodo->inicio = $inicio;
        $periodo->fim = $fim;
        $periodo->observacoes = trim($observacoes ?? '') ?: null;
        $periodo->save();
        CaixaItemService::recalcularSaldos($portador->codportador);
        return $periodo->fresh();
    }

    // corta o periodo nao fechado na data: a primeira parte termina no corte e
    // fica pendente, sem contagem final (a diferenca so' existe quando contar);
    // a segunda fica com o fim, a contagem final e o estado de antes, e comeca
    // com a contagem final da primeira (sem ela, o saldo final). Devolve a
    // segunda parte
    public static function dividir(PortadorPeriodo $periodo, Carbon $corte): PortadorPeriodo
    {
        $portador = $periodo->Portador;
        static::exigirCaixa($portador);
        PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_GESTOR, 'Dividir');
        static::travar($portador->codportador);
        $periodo->refresh();
        if ($periodo->fechado()) {
            abort(422, 'Período fechado: reabra para dividir.');
        }
        $corte = $corte->copy()->startOfSecond();
        $ate = $periodo->fim ?? Carbon::now();
        if ($corte->lte($periodo->inicio) || $corte->gte($ate)) {
            abort(422, 'O corte precisa ficar entre o início (' . $periodo->inicio->format('d/m/Y H:i')
                . ') e o fim (' . $ate->format('d/m/Y H:i') . ').');
        }
        // a primeira parte termina no corte antes de a segunda nascer (so'
        // um periodo sem fim por portador) e fica pendente
        $fim = $periodo->fim;
        $contagemfinal = $periodo->contagemfinal;
        $contagemitensfinal = $periodo->contagemitensfinal;
        $periodo->fill([
            'fim' => $corte,
            'contagemfinal' => null,
            'contagemitensfinal' => null,
            'diferenca' => null,
        ]);
        $periodo->save();
        $segunda = PortadorPeriodo::create([
            'codportador' => $periodo->codportador,
            'inicio' => $corte->copy()->addSecond(),
            'fim' => $fim,
            'codusuarioabertura' => Auth::user()->codusuario ?? null,
            'saldoinicial' => 0,
            'saldofinal' => 0,
            'contagemfinal' => $contagemfinal,
            'contagemitensfinal' => $contagemitensfinal,
        ]);
        static::moverLinhas($periodo, $segunda, $corte);
        $periodo->saldofinal = round((float) $periodo->saldoinicial + static::movimento($periodo), 2);
        $periodo->save();
        static::recalcular($segunda);
        CaixaItemService::recalcularSaldos($portador->codportador);
        return $segunda->fresh();
    }

    // junta o periodo ao anterior (os dois nao fechados; o anterior sem
    // diferenca, para nenhuma sumir). Fica o anterior, com o fim, a contagem
    // final e a diferenca deste. Devolve o anterior
    public static function unificar(PortadorPeriodo $periodo): PortadorPeriodo
    {
        $portador = $periodo->Portador;
        static::exigirCaixa($portador);
        PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_GESTOR, 'Unificar');
        static::travar($portador->codportador);
        $periodo->refresh();
        $anterior = static::anterior($periodo);
        if (!$anterior) {
            abort(422, 'Não há período anterior para unificar.');
        }
        if ($periodo->fechado() || $anterior->fechado()) {
            abort(422, 'Os dois períodos precisam estar não fechados: reabra antes.');
        }
        if (round((float) $anterior->diferenca, 2) != 0) {
            abort(422, 'O ' . static::descricao($anterior) . ' tem diferença de contagem: unificar a faria sumir.');
        }
        static::moverLinhas($periodo, $anterior, null);
        $observacoes = trim(implode("\n", array_filter([$anterior->observacoes, $periodo->observacoes])));
        $anterior->fill([
            'fim' => $periodo->fim,
            'contagemfinal' => $periodo->contagemfinal,
            'contagemitensfinal' => $periodo->contagemitensfinal,
            'diferenca' => $periodo->diferenca,
            'observacoes' => $observacoes ? mb_substr($observacoes, 0, 500) : null,
        ]);
        $periodo->delete();
        $anterior->save();
        static::recalcular($anterior);
        CaixaItemService::recalcularSaldos($portador->codportador);
        return $anterior->fresh();
    }

    // as linhas (e o periodo gravado no pagamento) de um periodo para outro;
    // com corte, so' as depois dele
    private static function moverLinhas(PortadorPeriodo $de, PortadorPeriodo $para, ?Carbon $depois): void
    {
        $linhas = PortadorMovimento::where('codportadorperiodo', $de->codportadorperiodo)
            ->when($depois, fn ($q) => $q->where('transacao', '>', $depois));
        $pagamentos = (clone $linhas)->whereNotNull('codpagamento')->pluck('codpagamento')->unique();
        $linhas->update(['codportadorperiodo' => $para->codportadorperiodo]);
        Pagamento::where('codportadorperiodo', $de->codportadorperiodo)
            ->when($depois, fn ($q) => $q->where(fn ($w) => $w->whereIn('codpagamento', $pagamentos)
                ->orWhere('transacao', '>', $depois)))
            ->update(['codportadorperiodo' => $para->codportadorperiodo]);
    }

    // inicio e fim: nada no futuro, sem invadir outro periodo do portador (o
    // fim de um pode ser o inicio do seguinte) e sem deixar lancamento de fora
    public static function exigirDatas(int $codportador, ?int $codportadorperiodo, Carbon $inicio, ?Carbon $fim): void
    {
        $f = fn (Carbon $d) => $d->format('d/m/Y H:i');
        $agora = Carbon::now();
        if ($inicio->gt($agora) || ($fim && $fim->gt($agora))) {
            abort(422, 'Início e fim não podem ser no futuro.');
        }
        if ($fim && $fim->lt($inicio)) {
            abort(422, 'O fim não pode ser antes do início.');
        }
        $outro = PortadorPeriodo::where('codportador', $codportador)
            ->when($codportadorperiodo, fn ($q) => $q->where('codportadorperiodo', '!=', $codportadorperiodo))
            ->where(fn ($q) => $q->whereNull('fim')->orWhere('fim', '>', $inicio))
            ->when($fim, fn ($q) => $q->where('inicio', '<', $fim))
            ->orderBy('inicio')
            ->first();
        if ($outro) {
            abort(422, 'Invade o ' . static::descricao($outro)
                . ($outro->fim ? ' (até ' . $f($outro->fim) . ')' : ', que está aberto') . '.');
        }
        if (!$codportadorperiodo) {
            return;
        }
        $fora = PortadorMovimento::where('codportadorperiodo', $codportadorperiodo)
            ->whereNull('inativo')
            ->whereRaw("coalesce(estado, 'E') <> 'C'")
            ->where(fn ($q) => $q->where('transacao', '<', $inicio)
                ->when($fim, fn ($q) => $q->orWhere('transacao', '>', $fim)))
            ->orderBy('transacao')
            ->first();
        if ($fora) {
            abort(422, "Há lançamento em {$f($fora->transacao)}, fora do início e do fim.");
        }
    }

    // ==== banco, adquirente, cartao (M12) ====

    // corrente (fim nulo) de quem nao e' especie; nasce no go-live com saldo 0
    // ou logo depois do ultimo corte, com o saldo final dele
    public static function corrente(Portador $portador): PortadorPeriodo
    {
        $periodo = PortadorPeriodo::where('codportador', $portador->codportador)->whereNull('fim')->first();
        if ($periodo) {
            return $periodo;
        }
        static::travar($portador->codportador);
        $periodo = PortadorPeriodo::where('codportador', $portador->codportador)->whereNull('fim')->first();
        if ($periodo) {
            return $periodo;
        }
        $inicio = ConferenciaService::inicio();
        $ultimo = PortadorPeriodo::where('codportador', $portador->codportador)
            ->whereNotNull('fim')
            ->orderBy('fim', 'desc')
            ->first();
        if ($ultimo && $ultimo->fim->gt($inicio)) {
            $inicio = $ultimo->fim->copy()->addSecond();
        }
        $saldo = round((float) ($ultimo->saldofinal ?? 0), 2);
        return PortadorPeriodo::create([
            'codportador' => $portador->codportador,
            'inicio' => $inicio,
            'saldoinicial' => $saldo,
            'saldofinal' => $saldo,
        ]);
    }

    // decisao 18: o lancamento cai no periodo cuja faixa contem a sua data
    // (fechado = 422 no razao); depois do ultimo corte, no corrente
    public static function doMomento(Portador $portador, Carbon $transacao): PortadorPeriodo
    {
        $periodo = PortadorPeriodo::where('codportador', $portador->codportador)
            ->where('inicio', '<=', $transacao)
            ->whereNotNull('fim')
            ->where('fim', '>=', $transacao)
            ->orderBy('inicio', 'desc')
            ->first();
        return $periodo ?? static::corrente($portador);
    }

    private static function exigirNaoCaixa(PortadorPeriodo $periodo): void
    {
        if ($periodo->Portador->ehCaixa()) {
            abort(422, 'Portador em espécie abre, conta e fecha pelo período.');
        }
    }

    // fecha do mais antigo para o mais novo. O corrente fecha no fim do dia
    // do corte e o que ficou depois vai para o corrente novo; o reaberto
    // (ja' tem fim) fecha no mesmo fim. Saldo final = inicial + linhas.
    public static function fechar(PortadorPeriodo $periodo, ?Carbon $corte): PortadorPeriodo
    {
        static::exigirNaoCaixa($periodo);
        $portador = $periodo->Portador;
        PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_GESTOR, 'Fechar');
        static::travar($portador->codportador);
        $periodo->refresh();
        if ($periodo->fechado()) {
            abort(422, 'Período já fechado.');
        }
        $anterior = PortadorPeriodo::where('codportador', $portador->codportador)
            ->where('inicio', '<', $periodo->inicio)
            ->whereNull('fechamento')
            ->orderBy('inicio')
            ->first();
        if ($anterior) {
            abort(422, 'Feche antes o ' . static::descricao($anterior) . ' (fecha-se do mais antigo para o mais novo).');
        }
        $pendentes = PortadorLancamentoService::pendentes($periodo);
        if ($pendentes) {
            abort(422, "Há {$pendentes} transferência(s) a confirmar neste período: confirme ou cancele antes de fechar.");
        }
        if ($periodo->aberto()) {
            if (!$corte) {
                abort(422, 'Informe a data de corte.');
            }
            $fim = $corte->copy()->endOfDay();
            if ($fim->lt($periodo->inicio)) {
                abort(422, 'O corte não pode ser antes do início do período (' . $periodo->inicio->format('d/m/Y') . ').');
            }
            if ($fim->gte(Carbon::today()->endOfDay())) {
                abort(422, 'O corte precisa ser um dia que já terminou (até ontem).');
            }
            $periodo->fim = $fim;
        }
        // o que ficou depois do corte vai para o periodo seguinte (o
        // corrente; nasce se preciso)
        $depois = PortadorMovimento::where('codportadorperiodo', $periodo->codportadorperiodo)
            ->where('transacao', '>', $periodo->fim)
            ->get();
        $periodo->saldofinal = round((float) $periodo->saldoinicial + static::movimento($periodo, $periodo->fim), 2);
        $periodo->fechamento = Carbon::now();
        $periodo->codusuariofechamento = Auth::user()->codusuario ?? null;
        $periodo->save();
        $seguinte = static::seguinte($periodo);
        if (!$seguinte && $depois->isNotEmpty()) {
            $seguinte = static::corrente($portador);
        }
        foreach ($depois as $mov) {
            $mov->codportadorperiodo = $seguinte->codportadorperiodo;
            $mov->save();
        }
        static::recalcular($periodo);
        return $periodo;
    }

    // reabre do mais novo para o mais antigo
    public static function reabrir(PortadorPeriodo $periodo): PortadorPeriodo
    {
        static::exigirNaoCaixa($periodo);
        PortadorAutorizador::autorizar($periodo->Portador, PortadorUsuario::PAPEL_GESTOR, 'Reabrir');
        static::travar($periodo->codportador);
        $periodo->refresh();
        if (!$periodo->fechado()) {
            abort(422, 'Período já está aberto.');
        }
        $posterior = PortadorPeriodo::where('codportador', $periodo->codportador)
            ->where('inicio', '>', $periodo->inicio)
            ->whereNotNull('fechamento')
            ->orderBy('inicio', 'desc')
            ->first();
        if ($posterior) {
            abort(422, 'Reabra antes o ' . static::descricao($posterior) . ' (reabre-se do mais novo para o mais antigo).');
        }
        $periodo->fechamento = null;
        $periodo->codusuariofechamento = null;
        $periodo->save();
        static::recalcular($periodo);
        return $periodo;
    }

    // taxa, tarifa ou rendimento de quem nao e' especie (fora do escopo do
    // dinheiro: continua pagamento sem pessoa). Valor com sinal: positivo
    // entrou. Cai no periodo da data
    public static function lancar(Portador $portador, string $motivo, float $valor, ?Carbon $transacao, ?string $observacoes): Pagamento
    {
        if ($portador->ehCaixa()) {
            abort(422, 'Em espécie não há taxa, tarifa nem rendimento: lance um ajuste.');
        }
        PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Lançar');
        $valor = round($valor, 2);
        if ($valor == 0) {
            abort(422, 'Informe o valor (positivo entrou, negativo saiu).');
        }
        $transacao = $transacao ?? Carbon::now();
        if ($transacao->lt(ConferenciaService::inicio())) {
            abort(422, 'O razão começa em ' . ConferenciaService::inicio()->format('d/m/Y') . '.');
        }
        $lado = $valor > 0 ? 'codportadordestino' : 'codportadororigem';
        return PagamentoService::criar([
            $lado => $portador->codportador,
            'meio' => PagamentoService::MEIO_TRANSFERENCIA,
            'motivo' => $motivo,
            'principal' => abs($valor),
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'efetivacao' => Carbon::now(),
            'codusuarioefetivacao' => Auth::user()->codusuario ?? null,
            'transacao' => $transacao,
            'codfilial' => $portador->codfilial,
            'observacoes' => $observacoes,
        ]);
    }

    // cancela a taxa, tarifa ou rendimento; periodo fechado = 422 pelo razao
    public static function cancelarLancamento(Pagamento $pag, string $justificativa): Pagamento
    {
        $portador = $pag->PortadorDestino ?? $pag->PortadorOrigem;
        if (empty($pag->motivo)
            || !empty($pag->codnegocio)
            || $pag->MovimentoTituloS()->exists()
            || !$portador
        ) {
            abort(422, 'Só taxa, tarifa ou rendimento se cancela por aqui.');
        }
        PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Cancelar');
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            abort(422, 'Lançamento já cancelado.');
        }
        return PagamentoService::cancelar($pag, $justificativa);
    }
}
