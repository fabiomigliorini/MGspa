<?php

namespace AgroBateria\Estresse;

use AgroBateria\Cenario;
use AgroBateria\Invariantes;
use AgroBateria\Motor;
use AgroBateria\Patio;
use Illuminate\Support\Facades\DB;

/**
 * E — estresse do pátio com aparelhos virtuais (ver Aparelho).
 *
 *   E1 dia de colheita realista (3 aparelhos, o pico real informado em 28/09)
 *   E2 rampa (--rampa=3,6,12,24): até onde o servidor aguenta — lento pode,
 *      número errado não: as invariantes valem em todos os níveis
 *   E3 ponto quente: todo mundo no mesmo contrato perto do saldo e A↔B juntos
 *   E4 dois aparelhos gravando o mesmo caminhão ao mesmo tempo
 *
 * Mix: 60 % recebimento, 30 % expedição, 10 % transferência; 70 % soja, 30 %
 * milho. Pesos inteiros de 30 a 75 t; leituras em volta das tolerâncias.
 */
final class Estresse extends Cenario
{
    private const P95_MAX_MS = 1000;
    private const MAX_MS = 5000;
    private const TIMEOUT_PATIO_MS = 15000;

    private array $params = [];
    private array $silos = ['soja' => [], 'milho' => []];
    private array $vendas = [];
    private array $cota = [];
    private float $prazo = 0;
    private string $dia = '';

    public function rodar(): void
    {
        $this->r->camada('E — Estresse do pátio');
        $this->preparar();
        $n = max(1, (int) ($this->opc['aparelhos'] ?? 3));
        $cam = max($n, (int) ($this->opc['caminhoes'] ?? 150));
        $this->caso('E1', "Dia de colheita: {$n} aparelhos, {$cam} caminhões", fn () => $this->dia($n, $cam));
        foreach ($this->opc['rampa'] ?? [] as $nivel) {
            $this->caso("E2.{$nivel}", "Rampa: {$nivel} aparelhos", fn () => $this->nivel((int) $nivel, (float) ($this->opc['minutos'] ?? 3)));
        }
        $this->caso('E3', 'Ponto quente: mesmo contrato e mesmos silos', fn () => $this->pontoQuente($n));
        $this->caso('E4', 'Dois aparelhos gravando o mesmo caminhão ao mesmo tempo', fn () => $this->conflito(20));
        $this->r->camada('E — Conferência da massa depois do estresse');
        Invariantes::rodar($this->r, 'zz', 'E/');
    }

    private function preparar(): void
    {
        $m = $this->m();
        foreach (['soja' => 3, 'milho' => 2] as $cultura => $qtd) {
            $this->params[$cultura] = $m->parametros($cultura);
            for ($i = 1; $i <= $qtd; $i++) {
                $s = $m->silo("E {$cultura} silo {$i}", $i === 3 ? 'TERCEIRO' : 'PROPRIO');
                $m->estoque($s, $cultura, 20000000);
                $this->silos[$cultura][] = $s;
            }
            $this->vendas[$cultura] = $m->contrato($cultura, 'VENDA', null);
        }
        $this->dia = substr($m->data(), 0, 10);
    }

    // ----------------------------------------------- chamado pelo Aparelho

    public function podeComecar(Aparelho $a): bool
    {
        return isset($this->cota[$a->n]) ? $a->caminhoes < $this->cota[$a->n] : microtime(true) < $this->prazo;
    }

    /** Os passos de um caminhão (+ o pull do ciclo a cada 5 caminhões). */
    public function caminhao(Aparelho $a, \Random\Randomizer $rng): array
    {
        $m = $this->m();
        $cultura = $rng->getInt(1, 10) <= 7 ? 'soja' : 'milho';
        $silos = $this->silos[$cultura];
        $tipo = $rng->getInt(1, 10);
        $leituras = [];
        if ($tipo <= 6) {
            $destino = $rng->getInt(1, 10) === 1 ? ['CONTRATO', $this->vendas[$cultura]] : ['UNIDADE', $silos[$rng->getInt(0, count($silos) - 1)]];
            $c = $this->p->nova('ENTRADA', $cultura, [['PLANTIO', $m->plantio[$cultura][$rng->getInt(0, count($m->plantio[$cultura]) - 1)]]], [$destino]);
            foreach ($this->params[$cultura] as $p) {
                if ($rng->getInt(1, 10) <= 7) {
                    $leituras[$p['cod']] = max(0, round($p['tolerancia'] + $rng->getInt(-20, 60) / 10, 1));
                }
            }
        } elseif ($tipo <= 9) {
            $c = $this->p->nova('SAIDA', $cultura, [['UNIDADE', $silos[$rng->getInt(0, count($silos) - 1)]]], [['CONTRATO', $this->vendas[$cultura]]]);
        } else {
            $i = $rng->getInt(0, count($silos) - 1);
            $j = ($i + $rng->getInt(1, count($silos) - 1)) % count($silos);
            $c = $this->p->nova('TRANSFERENCIA', $cultura, [['UNIDADE', $silos[$i]]], [['UNIDADE', $silos[$j]]]);
        }
        $c['data'] = $this->dia . ' ' . gmdate('H:i:s', $rng->getInt(6 * 3600, 22 * 3600));
        $tara = $rng->getInt(12000, 18000);
        $pbt = $tara + $rng->getInt(18000, 57000);
        $passos = array_map(fn ($e) => ['POST', $e], Patio::passos($c, $pbt, $tara, ['leituras' => $leituras]));
        if ($a->caminhoes % 5 === 4) {
            array_push($passos,
                ['GET', 'v1/carga?' . http_build_query(['data' => $this->dia, 'page' => 1]), 'GET v1/carga (pull)'],
                ['GET', 'v1/movimento-grao/saldos-unidades', 'GET v1/movimento-grao/saldos-unidades'],
                ['GET', 'v1/contrato?page=1', 'GET v1/contrato (pull)'],
                ['GET', 'v1/parametro-classificacao?page=1', 'GET v1/parametro-classificacao (pull)'],
            );
        }
        return $passos;
    }

    public function payload(array $estado): array
    {
        return $this->p->payload($estado, $this->params[$estado['_cultura']]);
    }

    // ---------------------------------------------------------- cenários

    /** @return Aparelho[] */
    private function rodarAparelhos(int $n, ?int $total, float $minutos = 0): array
    {
        $this->api()->zerarMetricas();
        $this->cota = [];
        if ($total !== null) {
            for ($i = 0; $i < $n; $i++) {
                $this->cota[$i] = intdiv($total, $n) + ($i < $total % $n ? 1 : 0);
            }
        } else {
            $this->prazo = microtime(true) + $minutos * 60;
        }
        $aparelhos = [];
        $semente = (int) ($this->opc['semente'] ?? 2809);
        for ($i = 0; $i < $n; $i++) {
            $aparelhos[] = new Aparelho($i, $this, $semente * 100 + $i);
        }
        $motor = new Motor($this->api());
        foreach ($aparelhos as $a) {
            $a->avancar($motor);
        }
        while ($motor->voando() > 0) {
            $motor->girar();
        }
        return $aparelhos;
    }

    private function dia(int $n, int $total): void
    {
        $t0 = microtime(true);
        $aparelhos = $this->rodarAparelhos($n, $total);
        $seg = microtime(true) - $t0;
        $dif = $this->conferirAparelhos($aparelhos);
        [$p95, $max, $erros5xx, , $pedidos] = $this->metricas("E1 — {$n} aparelhos");
        if ($erros5xx) {
            $dif[] = "{$erros5xx} erros 5xx/504";
        }
        if ($p95 > static::P95_MAX_MS) {
            $dif[] = "p95 do sincronizar {$p95} ms (critério ≤ " . static::P95_MAX_MS . ')';
        }
        if ($max > static::MAX_MS) {
            $dif[] = "máximo do sincronizar {$max} ms (critério ≤ " . static::MAX_MS . ')';
        }
        $fin = array_sum(array_map(fn ($a) => count($a->finalizadas), $aparelhos));
        $this->resultado('E1', "Dia de colheita: {$n} aparelhos, {$total} caminhões", $dif,
            "{$fin} finalizados, {$pedidos} pedidos em " . round($seg) . " s; sincronizar p95 {$p95} ms, máx {$max} ms");
    }

    private function nivel(int $n, float $minutos): void
    {
        $t0 = microtime(true);
        $aparelhos = $this->rodarAparelhos($n, null, $minutos);
        $seg = microtime(true) - $t0;
        $dif = $this->conferirAparelhos($aparelhos);
        [$p95, $max, $erros5xx, $lentos, $pedidos] = $this->metricas("E2 — {$n} aparelhos");
        $fin = array_sum(array_map(fn ($a) => count($a->finalizadas), $aparelhos));
        $txt = "{$fin} finalizados, " . round($pedidos / max(1, $seg), 1) . " pedidos/s; sincronizar p95 {$p95} ms, máx {$max} ms; "
            . "{$lentos} acima de 15 s (timeout do pátio); {$erros5xx} erros 5xx/504";
        $dif ? $this->r->falha("E2.{$n}", "Rampa: {$n} aparelhos", $txt . ' — ' . implode(' · ', $dif)) : $this->r->info("E2.{$n}", "Rampa: {$n} aparelhos", $txt);
    }

    /** O que cada aparelho finalizou está gravado igual (nada perdido, nada trocado). */
    private function conferirAparelhos(array $aparelhos): array
    {
        $dif = [];
        $esperado = [];
        foreach ($aparelhos as $a) {
            $esperado += $a->finalizadas;
        }
        $gravadas = [];
        foreach (array_chunk(array_keys($esperado), 500) as $lote) {
            foreach (DB::table('tblcarga')->whereIn('uuid', $lote)->get(['uuid', 'etapa', 'pbt', 'tara', 'inativo']) as $c) {
                $gravadas[$c->uuid] = $c;
            }
        }
        $perdidas = 0;
        $trocadas = 0;
        foreach ($esperado as $uuid => $e) {
            $g = $gravadas[$uuid] ?? null;
            if (!$g || $g->etapa !== 'FINALIZADO' || $g->inativo !== null) {
                $perdidas++;
            } elseif (abs((float) $g->pbt - $e['pbt']) > 0.0005 || abs((float) $g->tara - $e['tara']) > 0.0005) {
                $trocadas++;
            }
        }
        if ($perdidas) {
            $dif[] = "{$perdidas} cargas finalizadas pelo aparelho não estão finalizadas no servidor";
        }
        if ($trocadas) {
            $dif[] = "{$trocadas} cargas com peso diferente do que o aparelho gravou";
        }
        $recusas = [];
        $msgs = [];
        foreach ($aparelhos as $a) {
            foreach ($a->recusas as $st => $q) {
                $recusas[$st] = ($recusas[$st] ?? 0) + $q;
            }
            $msgs = array_merge($msgs, $a->mensagens);
        }
        if ($recusas) {
            ksort($recusas);
            $this->r->info('E-recusa', 'Gravações recusadas pelo servidor', json_encode($recusas) . ' — ' . implode(' | ', array_slice(array_unique($msgs), 0, 3)));
        }
        return $dif;
    }

    /** Tabela por endpoint; devolve [p95 e máx do sincronizar, erros 5xx, >15 s, pedidos]. */
    private function metricas(string $titulo): array
    {
        $linhas = [];
        $p95s = 0;
        $maxs = 0;
        $e5 = 0;
        $lentos = 0;
        $total = 0;
        foreach ($this->api()->metricas as $rotulo => $m) {
            $ms = $m['ms'];
            sort($ms);
            $n = count($ms);
            $pct = fn (float $q) => $ms[max(0, (int) ceil($q * $n) - 1)];
            $err = array_sum(array_filter($m['codigos'], fn ($k) => $k === 0 || $k >= 500, ARRAY_FILTER_USE_KEY));
            $slow = count(array_filter($ms, fn ($x) => $x > static::TIMEOUT_PATIO_MS));
            ksort($m['codigos']);
            $linhas[] = [$rotulo, $n, round($pct(0.5)), round($pct(0.95)), round($pct(0.99)), round(end($ms)), $slow, json_encode($m['codigos'])];
            $e5 += $err;
            $lentos += $slow;
            $total += $n;
            if ($rotulo === 'POST v1/carga/sincronizar') {
                $p95s = (int) round($pct(0.95));
                $maxs = (int) round(end($ms));
            }
        }
        $this->r->tabela($titulo, ['Endpoint', 'n', 'p50 ms', 'p95 ms', 'p99 ms', 'máx ms', '>15 s', 'códigos'], $linhas);
        return [$p95s, $maxs, $e5, $lentos, $total];
    }

    private function pontoQuente(int $n): void
    {
        $m = $this->m();
        $silo = $m->silo('E3 silo');
        $m->estoque($silo, 'soja', 1000000);
        // 60.000 kg de saldo e 25 t por caminhão: a regra aceita passar do saldo
        // (o pátio avisa); o que não pode é erro, grão perdido ou lançado em dobro.
        $ct = $m->contrato('soja', 'VENDA', 1000);
        $contrato = [];
        for ($i = 0; $i < max(3, 2 * $n); $i++) {
            $c = $this->p->nova('SAIDA', 'soja', [['UNIDADE', $silo]], [['CONTRATO', $ct]]);
            if ($erro = $this->p->preparar($c, 40000, 15000, [], $this->params['soja'])) {
                throw new \RuntimeException('preparo recusado: ' . $erro->resumo());
            }
            $contrato[] = ['POST', 'v1/carga/sincronizar', $this->payload($c)];
        }
        [$a, $b] = [$m->silo('E3 silo A'), $m->silo('E3 silo B')];
        $m->estoque($a, 'soja', 1000000);
        $m->estoque($b, 'soja', 1000000);
        $cruzadas = [];
        for ($i = 0; $i < 10 * $n; $i++) {
            [$de, $para] = $i % 2 ? [$a, $b] : [$b, $a];
            $c = $this->p->nova('TRANSFERENCIA', 'soja', [['UNIDADE', $de]], [['UNIDADE', $para]]);
            if ($erro = $this->p->preparar($c, 15000 + 1000 * ($i % 20 + 1), 15000, [], $this->params['soja'])) {
                throw new \RuntimeException('preparo recusado: ' . $erro->resumo());
            }
            $cruzadas[] = ['POST', 'v1/carga/sincronizar', $this->payload($c)];
        }
        // Dois disparos: os fechamentos do contrato juntos (a corrida pela trava) e
        // depois as transferências cruzadas juntas (a corrida pelos silos).
        $noContrato = $this->api()->lote($contrato);
        $resps = array_merge($noContrato, $this->api()->lote($cruzadas));
        $finais = array_merge($contrato, $cruzadas);
        $entregue = Patio::saldo('CONTRATO', $ct);
        $soma = Patio::saldo('UNIDADE', $a) + Patio::saldo('UNIDADE', $b);
        $e5 = count(array_filter($resps, fn ($r) => $r->erroServidor()));
        $aceitos = count(array_filter($noContrato, fn ($r) => $r->ok()));
        $dif = [];
        if ($aceitos !== count($contrato)) {
            $dif[] = "{$aceitos} de " . count($contrato) . ' fechamentos no contrato aceitos (regra: todos, com aviso)';
        }
        if (abs($entregue - 25000 * $aceitos) > 0.5) {
            $dif[] = 'contrato com ' . $this->kg($entregue) . ' kg para ' . $aceitos . ' caminhões de 25.000';
        }
        if (abs($soma - 2000000) > 0.0005) {
            $dif[] = 'silos A+B com ' . $this->kg($soma) . ' (eram 2.000.000)';
        }
        if ($e5) {
            $dif[] = "{$e5} erros 5xx/504";
        }
        $this->resultado('E3', 'Ponto quente: mesmo contrato e mesmos silos', $dif,
            count($finais) . ' fechamentos simultâneos; contrato com ' . $this->kg($entregue) . ' kg (saldo era 60.000; o excesso é aceito com aviso)');
    }

    private function conflito(int $rodadas): void
    {
        $m = $this->m();
        $silo = $m->silo('E4 silo');
        $incoerentes = 0;
        $ambas = 0;
        for ($i = 0; $i < $rodadas; $i++) {
            $c = $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $silo]]);
            if ($erro = $this->p->preparar($c, 45000, 15000, [], $this->params['soja'])) {
                throw new \RuntimeException('preparo recusado: ' . $erro->resumo());
            }
            // As duas partem da MESMA versão: um aparelho fecha, o outro edita a placa.
            // Certo é uma passar e a outra levar 409 (TASK-180); hoje o último vence.
            $outro = $c;
            $outro['etapa'] = 'TARA';
            $outro['placa'] = Patio::placa();
            $resps = $this->api()->lote([
                ['POST', 'v1/carga/sincronizar', $this->payload($c)],
                ['POST', 'v1/carga/sincronizar', $this->payload($outro)],
            ]);
            if ($resps[0]->ok() && $resps[1]->ok()) {
                $ambas++;
            }
            $b = Patio::banco($c['uuid']);
            $finalizada = $b->etapa === 'FINALIZADO' && $b->inativo === null;
            $comExtrato = count($b->movimentos) > 0 && count($b->movimentos) === count($b->pontos);
            if ($finalizada !== $comExtrato || (!$finalizada && count($b->movimentos) > 0)) {
                $incoerentes++;
            }
        }
        $this->r->checar('E4', 'Dois aparelhos gravando o mesmo caminhão ao mesmo tempo', $incoerentes === 0 && $ambas === 0,
            "{$rodadas} rodadas: em {$ambas} as duas gravações passaram (o último a chegar vence); {$incoerentes} com etapa e extrato desencontrados");
    }
}
