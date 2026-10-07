<?php

namespace Mg\Maquineta;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mg\Anexo\FotoService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;

/**
 * Periodo da maquineta (TASK-188 M9.8; no banco, lote), no padrao do
 * portador e seus periodos (doc-4). O cartao com maquineta cai no periodo
 * aberto (sem fim); o cancelamento de verdade cai no aberto no momento do
 * cancelamento, como no extrato da maquineta. Registro indevido sai do
 * periodo. O gerente confere: aberto -> pendente (bordero digitado que nao
 * bateu) -> conferido. O lancamento pertence ao periodo pelo vinculo, nao
 * pela hora: mover deixa fora do inicio e fim, e tudo bem.
 */
class MaquinetaLoteService
{
    private static function travar(int $codmaquineta): void
    {
        Maquineta::where('codmaquineta', $codmaquineta)->lockForUpdate()->first();
    }

    // periodo que recebe o cartao agora; trava a maquineta para dois PDVs nao
    // abrirem dois periodos ao mesmo tempo
    public static function corrente(int $codmaquineta): MaquinetaLote
    {
        static::travar($codmaquineta);
        $aberto = MaquinetaLote::where('codmaquineta', $codmaquineta)
            ->whereNull('fim')
            ->orderBy('abertura', 'desc')
            ->first();
        if ($aberto) {
            return $aberto;
        }
        $ultimo = MaquinetaLote::where('codmaquineta', $codmaquineta)
            ->orderBy('fim', 'desc')
            ->first();
        return MaquinetaLote::create([
            'codmaquineta' => $codmaquineta,
            'abertura' => $ultimo ? $ultimo->fim->copy()->addSecond() : Carbon::now(),
        ]);
    }

    // chamado no saving do Pagamento (via ConferenciaService::vincular)
    public static function vincular(Pagamento $pag): void
    {
        $cartao = in_array($pag->meio, PagamentoService::MEIOS_CARTAO) && !empty($pag->codmaquineta);
        $cancelado = $pag->estado == PagamentoService::ESTADO_CANCELADO;
        // fora de periodo: indevido, nao e' cartao de maquineta, ou pendente
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

    // credito e debito do sistema no periodo, no total e por PDV (nulo =
    // contas). Contrario (codpagamentoorigem) entra negativo; cancelamento
    // entra com o sinal invertido no periodo em que aconteceu.
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

    // total do sistema (credito + debito) de cada periodo da maquineta, numa
    // consulta so' (as abas da tela): [codmaquinetalote => total]
    public static function totais(int $codmaquineta): array
    {
        $regs = DB::select("
            select x.codmaquinetalote, sum(x.valor) as total
            from (
                select p.codmaquinetalote,
                    case when p.codpagamentoorigem is null then p.total else -p.total end as valor
                from tblpagamento p
                inner join tblmaquinetalote l on (l.codmaquinetalote = p.codmaquinetalote)
                where l.codmaquineta = :maq1
                union all
                select p.codmaquinetalotecancelamento,
                    case when p.codpagamentoorigem is null then -p.total else p.total end as valor
                from tblpagamento p
                inner join tblmaquinetalote l on (l.codmaquinetalote = p.codmaquinetalotecancelamento)
                where l.codmaquineta = :maq2
            ) x
            group by x.codmaquinetalote
        ", ['maq1' => $codmaquineta, 'maq2' => $codmaquineta]);
        $ret = [];
        foreach ($regs as $r) {
            $ret[(int) $r->codmaquinetalote] = round((float) $r->total, 2);
        }
        return $ret;
    }

    // pagamentos do periodo e os cancelados nele
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

    public static function descricao(MaquinetaLote $lote): string
    {
        return 'período de ' . $lote->abertura->format('d/m/Y H:i');
    }

    // corrigir, mover, dividir e unificar: so' no periodo nao conferido
    public static function exigirNaoConferido(MaquinetaLote $lote): void
    {
        if ($lote->conferido()) {
            abort(422, 'O ' . static::descricao($lote) . ' já foi conferido: reabra antes de mexer nele.');
        }
    }

    // o periodo anterior da mesma maquineta
    public static function anterior(MaquinetaLote $lote): ?MaquinetaLote
    {
        return MaquinetaLote::where('codmaquineta', $lote->codmaquineta)
            ->where('abertura', '<', $lote->abertura)
            ->orderBy('abertura', 'desc')
            ->first();
    }

    // o gerente digita o bordero. Aberto: termina agora e abre o seguinte.
    // Bateu no centavo (credito e debito), conferido; senao, pendente: corrige
    // os lancamentos ou o valor digitado e confere de novo
    public static function conferir(MaquinetaLote $lote, float $credito, float $debito, ?string $observacoes): MaquinetaLote
    {
        static::travar($lote->codmaquineta);
        $lote->refresh();
        static::exigirNaoConferido($lote);
        if ($lote->aberto()) {
            $lote->fim = Carbon::now()->startOfSecond();
            $lote->save();
            MaquinetaLote::create([
                'codmaquineta' => $lote->codmaquineta,
                'abertura' => $lote->fim->copy()->addSecond(),
            ]);
        }
        $sistema = static::sistema($lote->codmaquinetalote);
        $credito = round($credito, 2);
        $debito = round($debito, 2);
        $bateu = abs($credito - $sistema['credito']) < 0.005 && abs($debito - $sistema['debito']) < 0.005;
        $lote->fill([
            'creditoinformado' => $credito,
            'debitoinformado' => $debito,
            'creditosistema' => $sistema['credito'],
            'debitosistema' => $sistema['debito'],
            'observacoes' => trim($observacoes ?? '') ?: null,
            'fechamento' => $bateu ? Carbon::now() : null,
            'codusuariofechamento' => $bateu ? Auth::user()->codusuario : null,
        ]);
        $lote->save();
        return $lote;
    }

    // conferido volta a pendente (fim e bordero digitado ficam); qualquer um,
    // em qualquer ordem: cartao nao tem saldo encadeado
    public static function reabrir(MaquinetaLote $lote): MaquinetaLote
    {
        if (!$lote->conferido()) {
            return $lote;
        }
        $lote->fechamento = null;
        $lote->codusuariofechamento = null;
        $lote->save();
        return $lote;
    }

    // inicio, fim (so' quem ja' tem) e observacoes do nao conferido. Nao
    // recusa lancamento fora: o vinculo manda, nao a hora
    public static function editarDatas(MaquinetaLote $lote, Carbon $inicio, ?Carbon $fim, ?string $observacoes): MaquinetaLote
    {
        static::travar($lote->codmaquineta);
        $lote->refresh();
        static::exigirNaoConferido($lote);
        $fim = $lote->aberto() ? null : ($fim ?? $lote->fim);
        static::exigirDatas($lote, $inicio, $fim);
        $lote->abertura = $inicio;
        $lote->fim = $fim;
        $lote->observacoes = trim($observacoes ?? '') ?: null;
        $lote->save();
        return $lote;
    }

    // nada no futuro, fim depois do inicio e sem invadir outro periodo da
    // maquineta (o fim de um pode ser o inicio do seguinte)
    private static function exigirDatas(MaquinetaLote $lote, Carbon $inicio, ?Carbon $fim): void
    {
        $agora = Carbon::now();
        if ($inicio->gt($agora) || ($fim && $fim->gt($agora))) {
            abort(422, 'Início e fim não podem ser no futuro.');
        }
        if ($fim && $fim->lt($inicio)) {
            abort(422, 'O fim não pode ser antes do início.');
        }
        $outro = MaquinetaLote::where('codmaquineta', $lote->codmaquineta)
            ->where('codmaquinetalote', '!=', $lote->codmaquinetalote)
            ->where(fn ($q) => $q->whereNull('fim')->orWhere('fim', '>', $inicio))
            ->when($fim, fn ($q) => $q->where('abertura', '<', $fim))
            ->orderBy('abertura')
            ->first();
        if ($outro) {
            abort(422, 'Invade o ' . static::descricao($outro)
                . ($outro->fim ? ' (até ' . $outro->fim->format('d/m/Y H:i') . ')' : ', que está aberto') . '.');
        }
    }

    // corta o periodo nao conferido: a primeira parte termina no corte, sem
    // bordero digitado (pendente); a segunda fica com o fim, o bordero
    // digitado e as fotos de antes. Vai para a segunda o cartao depois do
    // corte e o cancelamento feito depois dele. Devolve a segunda
    public static function dividir(MaquinetaLote $lote, Carbon $corte): MaquinetaLote
    {
        static::travar($lote->codmaquineta);
        $lote->refresh();
        static::exigirNaoConferido($lote);
        $corte = $corte->copy()->startOfSecond();
        $ate = $lote->fim ?? Carbon::now();
        if ($corte->lte($lote->abertura) || $corte->gte($ate)) {
            abort(422, 'O corte precisa ficar entre o início (' . $lote->abertura->format('d/m/Y H:i')
                . ') e o fim (' . $ate->format('d/m/Y H:i') . ').');
        }
        $antes = $lote->only(['fim', 'creditoinformado', 'debitoinformado', 'creditosistema', 'debitosistema', 'observacoes']);
        // a primeira termina antes de a segunda nascer (so' um aberto)
        $lote->fill([
            'fim' => $corte,
            'creditoinformado' => null,
            'debitoinformado' => null,
            'creditosistema' => null,
            'debitosistema' => null,
            'observacoes' => null,
        ]);
        $lote->save();
        $segunda = MaquinetaLote::create(array_merge($antes, [
            'codmaquineta' => $lote->codmaquineta,
            'abertura' => $corte->copy()->addSecond(),
        ]));
        Pagamento::where('codmaquinetalote', $lote->codmaquinetalote)
            ->where('transacao', '>', $corte)
            ->update(['codmaquinetalote' => $segunda->codmaquinetalote]);
        Pagamento::where('codmaquinetalotecancelamento', $lote->codmaquinetalote)
            ->whereRaw('coalesce(cancelamento, alteracao) > ?', [$corte])
            ->update(['codmaquinetalotecancelamento' => $segunda->codmaquinetalote]);
        static::moverFotos($lote, $segunda);
        return $segunda;
    }

    // junta o periodo ao anterior (os dois nao conferidos). Fica o anterior,
    // com o fim deste e o bordero digitado deste (sem ele, o do anterior).
    // Devolve o anterior
    public static function unificar(MaquinetaLote $lote): MaquinetaLote
    {
        static::travar($lote->codmaquineta);
        $lote->refresh();
        $anterior = static::anterior($lote);
        if (!$anterior) {
            abort(422, 'Não há período anterior para unificar.');
        }
        if ($lote->conferido() || $anterior->conferido()) {
            abort(422, 'Os dois períodos precisam estar não conferidos: reabra antes.');
        }
        Pagamento::where('codmaquinetalote', $lote->codmaquinetalote)
            ->update(['codmaquinetalote' => $anterior->codmaquinetalote]);
        Pagamento::where('codmaquinetalotecancelamento', $lote->codmaquinetalote)
            ->update(['codmaquinetalotecancelamento' => $anterior->codmaquinetalote]);
        $bordero = $lote->creditoinformado !== null || $lote->debitoinformado !== null ? $lote : $anterior;
        $observacoes = trim(implode("\n", array_filter([$anterior->observacoes, $lote->observacoes])));
        $anterior->fill([
            'fim' => $lote->fim,
            'creditoinformado' => $bordero->creditoinformado,
            'debitoinformado' => $bordero->debitoinformado,
            'creditosistema' => $bordero->creditosistema,
            'debitosistema' => $bordero->debitosistema,
            'observacoes' => $observacoes ? mb_substr($observacoes, 0, 500) : null,
        ]);
        static::moverFotos($lote, $anterior);
        $lote->delete();
        $anterior->save();
        return $anterior;
    }

    // situacao dos periodos de cada maquineta (a lista de Maquinetas):
    // [codmaquineta => {aberto: inicio do aberto, pendentes, semBordero}]
    public static function resumo(array $codmaquinetas): array
    {
        if (empty($codmaquinetas)) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($codmaquinetas), '?'));
        $regs = DB::select("
            select l.codmaquinetalote, l.codmaquineta, l.abertura, l.fim, l.fechamento
            from tblmaquinetalote l
            where l.codmaquineta in ({$marcas})
            and (l.fim is null or l.fechamento is null or l.fechamento >= now() - interval '60 days')
        ", array_values($codmaquinetas));
        $comFoto = static::comFoto();
        $ret = [];
        foreach ($regs as $r) {
            $m = $ret[$r->codmaquineta] ?? ['aberto' => null, 'pendentes' => 0, 'semBordero' => 0];
            if ($r->fim === null) {
                $m['aberto'] = $r->abertura;
            } elseif ($r->fechamento === null) {
                $m['pendentes']++;
            }
            if ($r->fim !== null && !isset($comFoto[$r->codmaquinetalote])) {
                $m['semBordero']++;
            }
            $ret[$r->codmaquineta] = $m;
        }
        return $ret;
    }

    // ---- foto do bordero (disco maquineta-anexo, pasta = codmaquinetalote) ----

    const DISCO_FOTO = 'maquineta-anexo';

    public static function diretorioFoto(MaquinetaLote $lote): string
    {
        return (string) $lote->codmaquinetalote;
    }

    public static function fotos(MaquinetaLote $lote): array
    {
        return FotoService::fotos(static::DISCO_FOTO, static::diretorioFoto($lote));
    }

    public static function anexarFoto(MaquinetaLote $lote, string $anexoBase64): string
    {
        return FotoService::gravar(static::DISCO_FOTO, static::diretorioFoto($lote), $anexoBase64);
    }

    public static function mostrarFoto(MaquinetaLote $lote, string $arquivo)
    {
        return FotoService::mostrar(static::DISCO_FOTO, static::diretorioFoto($lote), $arquivo);
    }

    public static function excluirFoto(MaquinetaLote $lote, string $arquivo): void
    {
        FotoService::excluir(static::DISCO_FOTO, static::diretorioFoto($lote), $arquivo);
    }

    private static function moverFotos(MaquinetaLote $de, MaquinetaLote $para): void
    {
        $disco = Storage::disk(static::DISCO_FOTO);
        foreach (static::fotos($de) as $arquivo) {
            $disco->move(static::diretorioFoto($de) . "/{$arquivo}", static::diretorioFoto($para) . "/{$arquivo}");
        }
    }

    // periodos com foto (os que tem arquivo na pasta), de todas as maquinetas
    public static function comFoto(): array
    {
        $ret = [];
        foreach (Storage::disk(static::DISCO_FOTO)->allFiles() as $arquivo) {
            $partes = explode('/', $arquivo);
            if (isset($partes[1])) {
                $ret[(int) $partes[0]] = true;
            }
        }
        return $ret;
    }
}
