<?php

namespace Mg\Maquineta;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\NegocioAnexoService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;

/**
 * Lote da maquineta = o bordero (M9 doc-3). O cartao com maquineta cai no
 * lote corrente (o mais novo da maquineta, se aberto; senao nasce um). O
 * cancelamento de verdade cai no lote corrente do momento do cancelamento,
 * como no extrato da maquineta. Registro indevido sai do lote.
 */
class MaquinetaLoteService
{
    // lote que recebe o cartao agora; trava a maquineta para dois PDVs nao
    // abrirem dois lotes ao mesmo tempo
    public static function corrente(int $codmaquineta): MaquinetaLote
    {
        Maquineta::where('codmaquineta', $codmaquineta)->lockForUpdate()->first();
        $ultimo = MaquinetaLote::where('codmaquineta', $codmaquineta)
            ->orderBy('codmaquinetalote', 'desc')
            ->first();
        if ($ultimo && $ultimo->aberto()) {
            return $ultimo;
        }
        return MaquinetaLote::create([
            'codmaquineta' => $codmaquineta,
            'abertura' => Carbon::now(),
        ]);
    }

    // chamado no saving do Pagamento (via ConferenciaService::vincular)
    public static function vincular(Pagamento $pag): void
    {
        $cartao = in_array($pag->meio, PagamentoService::MEIOS_CARTAO) && !empty($pag->codmaquineta);
        $cancelado = $pag->estado == PagamentoService::ESTADO_CANCELADO;
        // fora de lote: indevido, nao e' cartao de maquineta, ou pendente
        // cancelado sem nunca ter sido efetivado
        if ($pag->indevido || !$cartao || ($cancelado && empty($pag->efetivacao) && empty($pag->codmaquinetalote))) {
            $pag->codmaquinetalote = null;
            $pag->codmaquinetalotecancelamento = null;
            return;
        }
        if (empty($pag->codmaquinetalote) || $pag->isDirty('codmaquineta')) {
            $pag->codmaquinetalote = static::corrente($pag->codmaquineta)->codmaquinetalote;
        }
        if ($cancelado && empty($pag->codmaquinetalotecancelamento)) {
            $pag->codmaquinetalotecancelamento = static::corrente($pag->codmaquineta)->codmaquinetalote;
        }
    }

    // credito e debito do sistema no lote, no total e por PDV (nulo =
    // contas). Contrario (codpagamentoorigem) entra negativo; cancelamento
    // entra com o sinal invertido no lote em que aconteceu.
    public static function sistema(int $codmaquinetalote): array
    {
        $regs = DB::select("
            select x.meio, x.codpdv, pdv.apelido as pdv, sum(x.valor) as valor, count(*) as quantidade
            from (
                select p.meio, p.codpdv,
                    case when p.codpagamentoorigem is null then p.total else -p.total end as valor
                from tblpagamento p
                where p.codmaquinetalote = :lote1
                union all
                select p.meio, p.codpdv,
                    case when p.codpagamentoorigem is null then -p.total else p.total end as valor
                from tblpagamento p
                where p.codmaquinetalotecancelamento = :lote2
            ) x
            left join tblpdv pdv on (pdv.codpdv = x.codpdv)
            group by x.meio, x.codpdv, pdv.apelido
            order by pdv.apelido nulls first
        ", ['lote1' => $codmaquinetalote, 'lote2' => $codmaquinetalote]);

        $ret = ['credito' => 0.0, 'debito' => 0.0, 'quantidade' => 0, 'pdvs' => []];
        foreach ($regs as $r) {
            $col = $r->meio == PagamentoService::MEIO_CREDITO ? 'credito' : 'debito';
            $chave = $r->codpdv ?? 0;
            if (!isset($ret['pdvs'][$chave])) {
                $ret['pdvs'][$chave] = [
                    'codpdv' => $r->codpdv,
                    'pdv' => $r->pdv ?? 'Escritório',
                    'credito' => 0.0,
                    'debito' => 0.0,
                ];
            }
            $ret['pdvs'][$chave][$col] = round($ret['pdvs'][$chave][$col] + $r->valor, 2);
            $ret[$col] = round($ret[$col] + $r->valor, 2);
            $ret['quantidade'] += $r->quantidade;
        }
        $ret['pdvs'] = array_values($ret['pdvs']);
        return $ret;
    }

    // pagamentos do lote e os cancelados nele (para a lista do lote)
    public static function pagamentos(int $codmaquinetalote)
    {
        return Pagamento::query()
            ->where(function ($q) use ($codmaquinetalote) {
                $q->where('codmaquinetalote', $codmaquinetalote)
                    ->orWhere('codmaquinetalotecancelamento', $codmaquinetalote);
            })
            ->orderBy('transacao')
            ->orderBy('codpagamento')
            ->get();
    }

    public static function exigirAberto(MaquinetaLote $lote): void
    {
        if (!$lote->aberto()) {
            abort(422, "O lote {$lote->codmaquinetalote} da maquineta já foi conferido: reabra o lote antes de mexer nele.");
        }
    }

    // fecha o lote com o que o gerente digitou do bordero (as cegas: so'
    // depois de gravado ele ve o sistema)
    public static function fechar(MaquinetaLote $lote, float $credito, float $debito, ?string $observacoes): MaquinetaLote
    {
        static::exigirAberto($lote);
        $sistema = static::sistema($lote->codmaquinetalote);
        $lote->fill([
            'fechamento' => Carbon::now(),
            'codusuariofechamento' => Auth::user()->codusuario,
            'creditoinformado' => round($credito, 2),
            'debitoinformado' => round($debito, 2),
            'creditosistema' => $sistema['credito'],
            'debitosistema' => $sistema['debito'],
            'observacoes' => $observacoes,
        ]);
        $lote->save();
        return $lote;
    }

    public static function reabrir(MaquinetaLote $lote): MaquinetaLote
    {
        if ($lote->aberto()) {
            return $lote;
        }
        $lote->fechamento = null;
        $lote->codusuariofechamento = null;
        $lote->creditosistema = null;
        $lote->debitosistema = null;
        $lote->save();
        return $lote;
    }

    // ---- foto do bordero (no disco dos anexos do negocio) ----

    public static function diretorioFoto(MaquinetaLote $lote): string
    {
        return "maquineta-lote/{$lote->codmaquinetalote}";
    }

    public static function fotos(MaquinetaLote $lote): array
    {
        return NegocioAnexoService::fotos(static::diretorioFoto($lote));
    }

    public static function anexarFoto(MaquinetaLote $lote, string $anexoBase64): string
    {
        return NegocioAnexoService::gravarFoto(static::diretorioFoto($lote), $anexoBase64);
    }
}
