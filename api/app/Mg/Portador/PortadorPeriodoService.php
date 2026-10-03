<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaService;

/**
 * Periodos do portador para o razao (M10 doc-3). A sessao da gaveta e' do
 * M9 (CaixaService: abre e fecha no PDV); cofre, banco e adquirente ganham
 * o periodo corrente sozinhos no primeiro lancamento (decisao 18). Fechar
 * com corte e reabrir em cadeia ficam no M12.
 */
class PortadorPeriodoService
{
    // corrente (fim nulo) de quem nao e' gaveta; nasce no go-live com
    // saldo 0 (o saldo de implantacao entra no M12)
    public static function corrente(Portador $portador): PortadorPeriodo
    {
        $periodo = PortadorPeriodo::where('codportador', $portador->codportador)->whereNull('fim')->first();
        if ($periodo) {
            return $periodo;
        }
        Portador::where('codportador', $portador->codportador)->lockForUpdate()->first();
        $periodo = PortadorPeriodo::where('codportador', $portador->codportador)->whereNull('fim')->first();
        if ($periodo) {
            return $periodo;
        }
        $inicio = ConferenciaService::inicio();
        $ultimoFim = PortadorPeriodo::where('codportador', $portador->codportador)->max('fim');
        if ($ultimoFim && Carbon::parse($ultimoFim)->gt($inicio)) {
            $inicio = Carbon::parse($ultimoFim)->addSecond();
        }
        return PortadorPeriodo::create([
            'codportador' => $portador->codportador,
            'inicio' => $inicio,
            'saldoinicial' => 0,
        ]);
    }

    // o razao nao mexe mais no periodo: gaveta depois que o gerente
    // conferiu (antes disso a correcao do M9 ainda acerta o razao); os
    // demais depois de fechados (M12)
    public static function imutavel(PortadorPeriodo $periodo): bool
    {
        if (!empty($periodo->conferencia)) {
            return true;
        }
        return !empty($periodo->fechamento) && !$periodo->Portador->ehGaveta();
    }

    public static function descricao(PortadorPeriodo $periodo): string
    {
        if ($periodo->Portador->ehGaveta()) {
            return 'sessão de ' . $periodo->inicio->format('d/m/Y H:i');
        }
        return empty($periodo->fim)
            ? 'período corrente'
            : 'período de ' . $periodo->inicio->format('d/m/Y') . ' a ' . $periodo->fim->format('d/m/Y');
    }

    // decisao 19: saldoinicial do aberto mais antigo + linhas ativas dos
    // abertos ate' o fim do dia; sem aberto, o saldofinal do ultimo fechado
    public static function saldo(Portador $portador, ?Carbon $ate = null): float
    {
        $ate = ($ate ?? Carbon::today())->copy()->endOfDay();
        $abertos = PortadorPeriodo::where('codportador', $portador->codportador)
            ->whereNull('fechamento')
            ->orderBy('inicio')
            ->get();
        if ($abertos->isEmpty()) {
            $ultimo = PortadorPeriodo::where('codportador', $portador->codportador)
                ->orderBy('inicio', 'desc')
                ->first();
            return round((float) ($ultimo->saldofinal ?? 0), 2);
        }
        $soma = DB::table('tblportadormovimento')
            ->whereIn('codportadorperiodo', $abertos->pluck('codportadorperiodo'))
            ->whereNull('inativo')
            ->where('transacao', '<=', $ate->format('Y-m-d H:i:s'))
            ->sum('valor');
        return round((float) $abertos->first()->saldoinicial + (float) $soma, 2);
    }
}
