<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Mg\Portador\PortadorMovimento;
use Mg\Portador\PortadorPeriodo;

/**
 * Cadastro dos itens do caixa (contas -> Cadastros -> Itens do Caixa) e as
 * linhas do item, que contam como cedula (doc-4, "Itens do caixa"):
 * [{preco, quantidade, descricao}], uma por preco, com descricao opcional.
 */
class CaixaItemService
{
    public static function listar(array $filtros)
    {
        $q = CaixaItem::query();
        if (!empty($filtros['item'])) {
            $q->where('item', 'ilike', '%' . $filtros['item'] . '%');
        }
        $inativo = $filtros['inativo'] ?? null;
        if (in_array($inativo, [true, 'true', 1, '1'], true)) {
            $q->whereNotNull('inativo');
        } elseif (in_array($inativo, [false, 'false', 0, '0'], true)) {
            $q->whereNull('inativo');
        }
        return $q->orderBy('item')->get();
    }

    // itens ativos: os que se lancam em qualquer portador em especie
    public static function ativos()
    {
        return CaixaItem::whereNull('inativo')->orderBy('item')->get();
    }

    // quanto tem do item em cada caixa que ja' mexeu com ele (o item so'
    // entra no caixa por entrada): a contagem final do ultimo periodo fechado
    // (com fim) mais as entradas e saidas depois dele (o periodo aberto), como
    // o saldo do dinheiro; vender nao lanca, so' aparece na contagem. Tudo por
    // indice: o item pelo codcaixaitem, o ultimo fechado por (codportador,
    // inicio)
    public static function saldos(CaixaItem $item): array
    {
        $sql = "
            SELECT p.codportador, p.portador, u.codportadorperiodo, u.fim,
                u.contagemitensfinal -> :cod AS linhas, e.lancamentos
            FROM tblportador p
            LEFT JOIN LATERAL (
                SELECT pp.codportadorperiodo, pp.inicio, pp.fim, pp.contagemitensfinal
                FROM tblportadorperiodo pp
                WHERE pp.codportador = p.codportador
                AND pp.fim IS NOT NULL
                ORDER BY pp.inicio DESC, pp.codportadorperiodo DESC
                LIMIT 1
            ) u ON TRUE
            LEFT JOIN LATERAL (
                SELECT jsonb_agg(jsonb_build_object('valor', m.valor, 'itens', m.itens)) AS lancamentos
                FROM tblportadormovimento m
                JOIN tblportadorperiodo pm ON pm.codportadorperiodo = m.codportadorperiodo
                WHERE m.codcaixaitem = :codcaixaitem
                AND m.estado <> 'C'
                AND m.codportador = p.codportador
                AND (u.inicio IS NULL OR pm.inicio > u.inicio)
            ) e ON TRUE
            WHERE p.codportador IN (
                SELECT codportador FROM tblportadormovimento
                WHERE codcaixaitem = :codcaixaitem2 AND estado <> 'C'
            )
            ORDER BY p.portador
        ";
        $rows = DB::select($sql, [
            'cod' => (string) $item->codcaixaitem,
            'codcaixaitem' => $item->codcaixaitem,
            'codcaixaitem2' => $item->codcaixaitem,
        ]);
        return array_map(function ($r) {
            $contado = json_decode($r->linhas ?? 'null', true) ?? [];
            $lancamentos = json_decode($r->lancamentos ?? 'null', true) ?? [];
            return array_merge([
                'codportador' => $r->codportador,
                'portador' => $r->portador,
                'codportadorperiodo' => $r->codportadorperiodo,
                'fim' => $r->fim ? Carbon::parse($r->fim)->toIso8601String() : null,
                'contado' => !empty($contado),
                'entradas' => !empty($lancamentos),
            ], static::resumo(static::somarLinhas($contado, $lancamentos)));
        }, $rows);
    }

    // o contado mais as entradas (saida com o sinal do valor), somando as
    // quantidades de mesmo preco e descricao; tira as zeradas
    private static function somarLinhas(array $contado, array $lancamentos): array
    {
        $juntas = [];
        $somar = function (array $l, int $sinal) use (&$juntas) {
            $chave = number_format((float) $l['preco'], 2, '.', '') . '|' . mb_strtolower($l['descricao'] ?? '');
            $juntas[$chave] ??= ['preco' => (float) $l['preco'], 'descricao' => $l['descricao'] ?? null, 'quantidade' => 0];
            $juntas[$chave]['quantidade'] += $sinal * (int) $l['quantidade'];
        };
        foreach ($contado as $l) {
            $somar($l, 1);
        }
        foreach ($lancamentos as $m) {
            foreach ($m['itens'] ?? [] as $l) {
                $somar($l, $m['valor'] < 0 ? -1 : 1);
            }
        }
        ksort($juntas);
        return array_values(array_filter($juntas, fn ($l) => $l['quantidade'] != 0));
    }

    // os periodos de um caixa em que o item mexeu (abertura diferente do
    // fechamento, ou entrada ou saida do item), do mais novo (o aberto, se
    // mexeu) para o mais antigo, paginado: abertura, entradas (com as
    // linhas), fechamento e diferenca (fechamento - abertura - entradas,
    // como a da contagem: negativa e' o que saiu sem lancamento, vendido ou
    // levado para outro caixa); o aberto ainda sem fechamento nem diferenca
    public static function fechamentos(CaixaItem $item, int $codportador)
    {
        $cod = (string) $item->codcaixaitem;
        $pagina = static::periodosDoItem($item, $codportador)
            ->orderBy('inicio', 'desc')
            ->orderBy('codportadorperiodo', 'desc')
            ->paginate(30, ['codportadorperiodo', 'codportador', 'inicio', 'fim', 'contagemitensinicial', 'contagemitensfinal']);
        $movimentos = PortadorMovimento::whereIn('codportadorperiodo', $pagina->pluck('codportadorperiodo'))
            ->where('codcaixaitem', $item->codcaixaitem)
            ->where('estado', '<>', PortadorMovimento::ESTADO_CANCELADO)
            ->with('UsuarioCriacao:codusuario,usuario')
            ->orderBy('transacao')
            ->get()
            ->groupBy('codportadorperiodo');
        return $pagina->through(function (PortadorPeriodo $p) use ($cod, $movimentos) {
            $abertura = static::resumo($p->contagemitensinicial[$cod] ?? []);
            $fechamento = $p->fim ? static::resumo($p->contagemitensfinal[$cod] ?? []) : null;
            $lancamentos = ($movimentos[$p->codportadorperiodo] ?? collect())->map(fn (PortadorMovimento $m) => [
                'codportadormovimento' => $m->codportadormovimento,
                'transacao' => $m->transacao->toIso8601String(),
                'valor' => (float) $m->valor,
                'quantidade' => ($m->valor < 0 ? -1 : 1) * array_sum(array_column($m->itens ?? [], 'quantidade')),
                'linhas' => $m->itens ?? [],
                'observacoes' => $m->observacoes,
                'usuariocriacao' => $m->usuariocriacao,
            ])->values()->all();
            $entradas = [
                'quantidade' => array_sum(array_column($lancamentos, 'quantidade')),
                'total' => round(array_sum(array_column($lancamentos, 'valor')), 2),
            ];
            return [
                'codportadorperiodo' => $p->codportadorperiodo,
                'codportador' => $p->codportador,
                'inicio' => $p->inicio->toIso8601String(),
                'fim' => optional($p->fim)->toIso8601String(),
                'abertura' => $abertura,
                'entradas' => $entradas,
                'lancamentos' => $lancamentos,
                'fechamento' => $fechamento,
                'diferenca' => $fechamento ? [
                    'quantidade' => $fechamento['quantidade'] - $abertura['quantidade'] - $entradas['quantidade'],
                    'total' => round($fechamento['total'] - $abertura['total'] - $entradas['total'], 2),
                ] : null,
            ];
        });
    }

    // as descricoes distintas ja' gravadas no item: nas entradas e saidas e
    // nas contagens (ate' 20, em ordem alfabetica)
    public static function descricoes(CaixaItem $item, ?string $busca): array
    {
        $regs = DB::select("
            select distinct d from (
                select e->>'descricao' as d
                from tblportadormovimento m, jsonb_array_elements(m.itens) e
                where m.codcaixaitem = :item1
                union
                select e->>'descricao'
                from tblportadorperiodo p, jsonb_array_elements(coalesce(p.contagemitensfinal -> :cod1, '[]')) e
                union
                select e->>'descricao'
                from tblportadorperiodo p, jsonb_array_elements(coalesce(p.contagemitensinicial -> :cod2, '[]')) e
            ) x
            where d is not null and d ilike :busca
            order by d
            limit 20
        ", [
            'item1' => $item->codcaixaitem,
            'cod1' => (string) $item->codcaixaitem,
            'cod2' => (string) $item->codcaixaitem,
            'busca' => '%' . trim($busca ?? '') . '%',
        ]);
        return array_map(fn ($r) => $r->d, $regs);
    }

    // os tipos do item (descricao + preco distintos), pelas entradas e
    // saidas: o item so' entra no caixa por entrada, entao toda linha de
    // contagem nasceu de uma (indice em codcaixaitem, sem varrer os periodos)
    public static function tipos(CaixaItem $item): array
    {
        $regs = DB::select("
            select distinct on (lower(coalesce(e->>'descricao', '')), (e->>'preco')::numeric)
                e->>'descricao' as descricao, (e->>'preco')::numeric as preco
            from tblportadormovimento m, jsonb_array_elements(m.itens) e
            where m.codcaixaitem = :item
            order by lower(coalesce(e->>'descricao', '')), (e->>'preco')::numeric, e->>'descricao'
        ", ['item' => $item->codcaixaitem]);
        return array_map(fn ($r) => ['descricao' => $r->descricao, 'preco' => (float) $r->preco], $regs);
    }

    // troca a descricao de um tipo (descricao + preco) em tudo: entradas e
    // saidas (as canceladas tambem) e contagens de abertura e fechamento de
    // todos os caixas, fechados inclusive. So' o texto: preco, quantidade e
    // valores nao mudam. Se ja' existe o tipo com a descricao nova, as linhas
    // se juntam (CaixaItemService::linhas soma as de mesmo preco e descricao)
    public static function renomearTipo(CaixaItem $item, float $preco, ?string $descricao, string $nova): int
    {
        $nova = trim($nova);
        if ($nova === '') {
            abort(422, 'Informe a descrição.');
        }
        $nova = mb_substr($nova, 0, 50);
        $preco = round($preco, 2);
        $chave = mb_strtolower(trim($descricao ?? ''));
        $alterou = false;
        $trocar = function (?array $linhas) use ($preco, $chave, $nova, &$alterou): ?array {
            if ($linhas === null) {
                return null;
            }
            $mudou = false;
            foreach ($linhas as &$l) {
                if (round((float) $l['preco'], 2) == $preco && mb_strtolower(trim($l['descricao'] ?? '')) === $chave) {
                    $l['descricao'] = $nova;
                    $mudou = true;
                }
            }
            unset($l);
            if (!$mudou) {
                return null;
            }
            $alterou = true;
            return static::linhas($linhas);
        };
        $total = 0;
        $movimentos = DB::select('select codportadormovimento, itens from tblportadormovimento where codcaixaitem = :item', ['item' => $item->codcaixaitem]);
        foreach ($movimentos as $m) {
            if (($itens = $trocar(json_decode($m->itens, true))) !== null) {
                DB::update('update tblportadormovimento set itens = :itens where codportadormovimento = :cod', [
                    'itens' => json_encode($itens),
                    'cod' => $m->codportadormovimento,
                ]);
                $total++;
            }
        }
        $cod = (string) $item->codcaixaitem;
        $periodos = DB::select('
            select codportadorperiodo, contagemitensinicial -> :cod1 as inicial, contagemitensfinal -> :cod2 as final
            from tblportadorperiodo
            where contagemitensinicial -> :cod3 is not null or contagemitensfinal -> :cod4 is not null
        ', ['cod1' => $cod, 'cod2' => $cod, 'cod3' => $cod, 'cod4' => $cod]);
        foreach ($periodos as $p) {
            foreach (['inicial' => 'contagemitensinicial', 'final' => 'contagemitensfinal'] as $campo => $coluna) {
                if (($linhas = $trocar(json_decode($p->$campo ?? 'null', true))) !== null) {
                    DB::update("update tblportadorperiodo set {$coluna} = jsonb_set({$coluna}, CAST(:caminho AS text[]), CAST(:linhas AS jsonb)) where codportadorperiodo = :cod", [
                        'caminho' => '{' . $cod . '}',
                        'linhas' => json_encode($linhas),
                        'cod' => $p->codportadorperiodo,
                    ]);
                    $total++;
                }
            }
        }
        if (!$alterou) {
            abort(404, 'Tipo não encontrado no item.');
        }
        return $total;
    }

    // os periodos do caixa em que o item mexeu: a contagem mudou ou teve
    // entrada ou saida
    private static function periodosDoItem(CaixaItem $item, int $codportador)
    {
        $cod = (string) $item->codcaixaitem;
        return PortadorPeriodo::where('codportador', $codportador)
            ->where(fn ($q) => $q
                ->whereRaw('(contagemitensinicial -> ?) IS DISTINCT FROM (contagemitensfinal -> ?)', [$cod, $cod])
                ->orWhereExists(fn ($q) => $q->selectRaw('1')
                    ->from('tblportadormovimento as m')
                    ->whereColumn('m.codportadorperiodo', 'tblportadorperiodo.codportadorperiodo')
                    ->where('m.codcaixaitem', $item->codcaixaitem)
                    ->where('m.estado', '<>', PortadorMovimento::ESTADO_CANCELADO)));
    }

    // os totais do item no caixa, de todas as paginas: as entradas (de todos
    // os periodos, o aberto tambem), o saldo (o mesmo de saldos(): o contado
    // no ultimo fechamento mais as entradas depois dele) e a diferenca (so'
    // dos periodos fechados, o aberto ainda nao foi contado); entradas +
    // diferenca = saldo. null se o item nunca mexeu no caixa
    public static function totaisFechamentos(CaixaItem $item, int $codportador): ?array
    {
        $cod = (string) $item->codcaixaitem;
        $periodos = static::periodosDoItem($item, $codportador)
            ->get(['codportadorperiodo', 'fim', 'contagemitensinicial', 'contagemitensfinal']);
        if ($periodos->isEmpty()) {
            return null;
        }
        $movimentos = PortadorMovimento::whereIn('codportadorperiodo', $periodos->pluck('codportadorperiodo'))
            ->where('codcaixaitem', $item->codcaixaitem)
            ->where('estado', '<>', PortadorMovimento::ESTADO_CANCELADO)
            ->get(['codportadorperiodo', 'valor', 'itens']);
        $somaEntradas = fn ($ms) => [
            'quantidade' => $ms->sum(fn ($m) => ($m->valor < 0 ? -1 : 1) * array_sum(array_column($m->itens ?? [], 'quantidade'))),
            'total' => round((float) $ms->sum('valor'), 2),
        ];
        $fechados = $periodos->whereNotNull('fim');
        $entradasFechados = $somaEntradas($movimentos->whereIn('codportadorperiodo', $fechados->pluck('codportadorperiodo')->all()));
        $soma = fn (string $coluna) => [
            'quantidade' => $fechados->sum(fn ($p) => static::resumo($p->$coluna[$cod] ?? [])['quantidade']),
            'total' => round($fechados->sum(fn ($p) => static::resumo($p->$coluna[$cod] ?? [])['total']), 2),
        ];
        $aberturas = $soma('contagemitensinicial');
        $fechamentos = $soma('contagemitensfinal');
        $saldo = collect(static::saldos($item))->firstWhere('codportador', $codportador);
        return [
            'entradas' => $somaEntradas($movimentos),
            'saldo' => [
                'quantidade' => $saldo['quantidade'] ?? 0,
                'total' => $saldo['total'] ?? 0.0,
            ],
            // a soma da diferenca de cada periodo fechado: fechamento - abertura - entradas
            'diferenca' => [
                'quantidade' => $fechamentos['quantidade'] - $aberturas['quantidade'] - $entradasFechados['quantidade'],
                'total' => round($fechamentos['total'] - $aberturas['total'] - $entradasFechados['total'], 2),
            ],
        ];
    }

    // as linhas do item com a quantidade e o total
    private static function resumo(array $linhas): array
    {
        return [
            'linhas' => $linhas,
            'quantidade' => array_sum(array_column($linhas, 'quantidade')),
            'total' => static::totalLinhas($linhas),
        ];
    }

    public static function salvar(CaixaItem $item, array $dados): CaixaItem
    {
        $item->fill($dados);
        $item->save();
        return $item->fresh();
    }

    public static function inativar(CaixaItem $item): CaixaItem
    {
        $item->inativo = Carbon::now();
        $item->save();
        return $item->fresh();
    }

    public static function ativar(CaixaItem $item): CaixaItem
    {
        $item->inativo = null;
        $item->save();
        return $item->fresh();
    }

    public static function excluir(CaixaItem $item): void
    {
        try {
            $item->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                abort(409, 'Item já usado em caixa, não pode ser excluído. Inative ao invés de excluir.');
            }
            throw $e;
        }
    }

    // ==== as linhas do item (contam como cedula) ====

    // limpa as linhas: preco positivo, quantidade inteira nao negativa,
    // descricao opcional; junta as de mesmo preco e descricao e tira as
    // zeradas. Ordem: preco, descricao
    public static function linhas(?array $linhas): array
    {
        $juntas = [];
        foreach ($linhas ?? [] as $l) {
            $preco = round((float) ($l['preco'] ?? 0), 2);
            $quantidade = (int) ($l['quantidade'] ?? 0);
            $descricao = trim((string) ($l['descricao'] ?? ''));
            $descricao = $descricao === '' ? null : mb_substr($descricao, 0, 50);
            if ($quantidade < 0) {
                abort(422, 'A quantidade do item não pode ser negativa.');
            }
            if ($quantidade == 0) {
                continue;
            }
            if ($preco <= 0) {
                abort(422, 'Informe o preço de cada linha do item.');
            }
            $chave = number_format($preco, 2, '.', '') . '|' . mb_strtolower($descricao ?? '');
            if (isset($juntas[$chave])) {
                $juntas[$chave]['quantidade'] += $quantidade;
            } else {
                $juntas[$chave] = ['preco' => $preco, 'quantidade' => $quantidade, 'descricao' => $descricao];
            }
        }
        ksort($juntas);
        return array_values($juntas);
    }

    // as linhas da entrada ou saida, mesma regra da tela (ItemCaixaDialog):
    // linha toda vazia e' ignorada; com qualquer campo preenchido, exige
    // descricao, preco >= 0,01 e quantidade inteira >= 1
    public static function validarEntrada(array $linhas): array
    {
        $ret = [];
        foreach (array_values($linhas) as $i => $l) {
            $descricao = trim((string) ($l['descricao'] ?? ''));
            $preco = $l['preco'] ?? null;
            $quantidade = $l['quantidade'] ?? null;
            if ($descricao === '' && in_array($preco, [null, ''], true) && in_array($quantidade, [null, ''], true)) {
                continue;
            }
            $n = $i + 1;
            if ($descricao === '') {
                abort(422, "Linha {$n}: informe a descrição.");
            }
            if (!is_numeric($preco) || round((float) $preco, 2) < 0.01) {
                abort(422, "Linha {$n}: o preço tem que ser de pelo menos 0,01.");
            }
            if (!is_numeric($quantidade) || (float) $quantidade != (int) $quantidade || (int) $quantidade < 1) {
                abort(422, "Linha {$n}: a quantidade tem que ser inteira, de pelo menos 1.");
            }
            $ret[] = ['preco' => (float) $preco, 'quantidade' => (int) $quantidade, 'descricao' => $descricao];
        }
        if (empty($ret)) {
            abort(422, 'Informe pelo menos uma linha.');
        }
        return static::linhas($ret);
    }

    public static function totalLinhas(?array $linhas): float
    {
        $total = 0.0;
        foreach ($linhas ?? [] as $l) {
            $total += (int) $l['quantidade'] * (float) $l['preco'];
        }
        return round($total, 2);
    }

    // a contagem dos itens {codcaixaitem: linhas} limpa (sem os zerados); o
    // item precisa existir
    public static function contagem(?array $itens): array
    {
        $ret = [];
        foreach ($itens ?? [] as $cod => $linhas) {
            $linhas = static::linhas(is_array($linhas) ? $linhas : []);
            if (empty($linhas)) {
                continue;
            }
            if (!CaixaItem::whereKey((int) $cod)->exists()) {
                abort(422, "Item do caixa {$cod} não existe.");
            }
            $ret[(int) $cod] = $linhas;
        }
        ksort($ret);
        return $ret;
    }

    public static function totalContagem(?array $itens): float
    {
        $total = 0.0;
        foreach ($itens ?? [] as $linhas) {
            $total += static::totalLinhas($linhas);
        }
        return round($total, 2);
    }
}
