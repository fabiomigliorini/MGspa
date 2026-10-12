<?php

namespace AgroBateria\Cenarios;

use AgroBateria\Cenario;
use AgroBateria\Patio;
use AgroBateria\Referencia;

/**
 * T — silos e transferências. Regra decidida em 28/09: silo como ORIGEM baixa
 * o BRUTO (o que saiu fisicamente); o destino recebe o LÍQUIDO; a diferença é
 * quebra. Silo sem saldo: o pátio avisa, o servidor não bloqueia (D1).
 */
final class Silos extends Cenario
{
    public function rodar(): void
    {
        $this->r->camada('T — Silos e transferências');
        $this->caso('T1', '50 transferências aleatórias conservam o grão', fn () => $this->t1());
        $this->caso('T2', 'Saída de silo com desconto: bruto sai, líquido chega', fn () => $this->t2());
        $this->caso('T3', 'Transferência para o próprio silo é recusada', fn () => $this->t3());
        $this->caso('T4', 'Saída maior que o saldo passa e o silo fica negativo (D1)', fn () => $this->t4());
        $this->caso('T5', 'Saldo do silo separado por safra (milho e soja no mesmo silo)', fn () => $this->t5());
        $this->caso('T6', 'A→B e B→A ao mesmo tempo, sem deadlock e sem perder grão', fn () => $this->t6());
        $this->caso('T7', 'Silo inativo não recebe carga nova; carga antiga dele segue editável', fn () => $this->t7());
    }

    private function t1(): void
    {
        $m = $this->m();
        $silos = [$m->silo('T1 silo A'), $m->silo('T1 silo B'), $m->silo('T1 silo C', 'TERCEIRO')];
        foreach ($silos as $s) {
            $m->estoque($s, 'soja', 1000000);
        }
        $esperado = array_fill_keys($silos, 1000000.0);
        mt_srand(2809);
        $recusas = 0;
        for ($i = 0; $i < 50; $i++) {
            [$de, $para] = (array) array_rand(array_flip($silos), 2);
            if (mt_rand(0, 1)) {
                [$de, $para] = [$para, $de];
            }
            $kg = mt_rand(5, 40) * 1000;
            $c = $this->p->nova('TRANSFERENCIA', 'soja', [['UNIDADE', $de]], [['UNIDADE', $para]]);
            if (!$this->p->percorrer($c, 15000 + $kg, 15000)->ok()) {
                $recusas++;
                continue;
            }
            $esperado[$de] -= $kg;
            $esperado[$para] += $kg;
        }
        $dif = [];
        foreach ($silos as $s) {
            $saldo = Patio::saldo('UNIDADE', $s);
            if (abs($saldo - $esperado[$s]) > 0.0005) {
                $dif[] = "silo {$s} " . $this->kg($saldo) . ' (esperado ' . $this->kg($esperado[$s]) . ')';
            }
        }
        $soma = array_sum(array_map(fn ($s) => Patio::saldo('UNIDADE', $s), $silos));
        if (abs($soma - 3000000) > 0.0005) {
            $dif[] = 'soma dos silos ' . $this->kg($soma) . ' (era 3.000.000)';
        }
        if ($recusas) {
            $dif[] = "{$recusas} transferências recusadas";
        }
        $this->resultado('T1', '50 transferências aleatórias conservam o grão', $dif, 'soma dos 3 silos = 3.000.000 kg');
    }

    private function t2(): void
    {
        $m = $this->m();
        [$a, $b] = [$m->silo('T2 silo A'), $m->silo('T2 silo B')];
        $m->estoque($a, 'soja', 200000);
        $ct = $m->contrato('soja', 'VENDA', null);
        $leit = $m->leituras('soja', ['Umidade' => 18]);
        $ref = Referencia::desconto(45000, 15000, $leit, $m->parametros('soja'));
        $dif = [];

        $t = $this->p->nova('TRANSFERENCIA', 'soja', [['UNIDADE', $a]], [['UNIDADE', $b]]);
        $rt = $this->p->percorrer($t, 45000, 15000, ['leituras' => $leit]);
        $sa = Patio::saldo('UNIDADE', $a);
        $sb = Patio::saldo('UNIDADE', $b);
        if (!$rt->ok()) {
            $dif[] = 'transferência recusada: ' . $rt->resumo();
        } else {
            if (abs($sa - (200000 - $ref['bruto'])) > 0.0005) {
                $dif[] = 'transferência: silo A baixou ' . $this->kg(200000 - $sa) . ' (regra: bruto ' . $this->kg($ref['bruto']) . ')';
            }
            if (abs($sb - $ref['liquido']) > 0.0005) {
                $dif[] = 'silo B recebeu ' . $this->kg($sb) . ' (regra: líquido ' . $this->kg($ref['liquido']) . ')';
            }
        }

        $s = $this->p->nova('SAIDA', 'soja', [['UNIDADE', $a]], [['CONTRATO', $ct]]);
        $rs = $this->p->percorrer($s, 45000, 15000, ['leituras' => $leit]);
        $sa2 = Patio::saldo('UNIDADE', $a);
        if (!$rs->ok()) {
            $dif[] = 'expedição recusada: ' . $rs->resumo();
        } elseif (abs(($sa - $sa2) - $ref['bruto']) > 0.0005) {
            $dif[] = 'expedição: silo baixou ' . $this->kg($sa - $sa2) . ' (regra: bruto ' . $this->kg($ref['bruto']) . ')';
        }
        $this->resultado('T2', 'Saída de silo com desconto: bruto sai, líquido chega', $dif,
            'quebra de ' . $this->kg($ref['desconto']) . ' kg por carga');
    }

    private function t3(): void
    {
        $m = $this->m();
        $a = $m->silo('T3 silo');
        $m->estoque($a, 'soja', 50000);
        $c = $this->p->nova('TRANSFERENCIA', 'soja', [['UNIDADE', $a]], [['UNIDADE', $a]]);
        $r = $this->p->percorrer($c, 25000, 15000);
        $this->r->checar('T3', 'Transferência para o próprio silo é recusada', $r->status === 422, 'veio ' . $r->resumo());
    }

    private function t4(): void
    {
        $m = $this->m();
        $a = $m->silo('T4 silo');
        $m->estoque($a, 'soja', 10000);
        $ct = $m->contrato('soja', 'VENDA', null);
        $c = $this->p->nova('SAIDA', 'soja', [['UNIDADE', $a]], [['CONTRATO', $ct]]);
        $r = $this->p->percorrer($c, 45000, 15000);
        $saldo = Patio::saldo('UNIDADE', $a);
        if (!$r->ok()) {
            $this->r->falha('T4', 'Saída maior que o saldo passa e o silo fica negativo (D1)', 'o servidor bloqueou, e a decisão D1 é só avisar: ' . $r->resumo());
            return;
        }
        $this->r->info('T4', 'Saída maior que o saldo passa e o silo fica negativo (D1)',
            'silo com 10.000 expediu 30.000 e ficou em ' . $this->kg($saldo) . ' kg — o aviso é no pátio (Fase 6)');
    }

    private function t5(): void
    {
        $m = $this->m();
        $a = $m->silo('T5 silo misto');
        $rs = $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $a]]);
        $rm = $this->p->nova('ENTRADA', 'milho', [['PLANTIO', $m->plantio['milho'][0]]], [['UNIDADE', $a]]);
        $ok = $this->p->percorrer($rs, 35000, 15000)->ok() && $this->p->percorrer($rm, 20000, 15000)->ok();
        if (!$ok) {
            $this->r->erro('T5', 'Saldo do silo separado por safra (milho e soja no mesmo silo)', 'preparo recusado');
            return;
        }
        $porSafra = fn ($codsafra) => collect($this->api()->get('v1/movimento-grao/saldos-unidades', $codsafra ? ['codsafra' => $codsafra] : [])->json)
            ->firstWhere('codunidadearmazenadora', $a)['saldokg'] ?? null;
        $soja = $porSafra($m->safra['soja']);
        $milho = $porSafra($m->safra['milho']);
        $semSafra = $porSafra(null);
        $apiOk = abs((float) $soja - 20000) < 0.001 && abs((float) $milho - 5000) < 0.001;
        $this->r->info('T5', 'Saldo do silo separado por safra (milho e soja no mesmo silo)',
            ($apiOk ? 'a API separa por safra (soja 20.000, milho 5.000)' : 'a API por safra deu soja ' . $this->kg($soja) . ', milho ' . $this->kg($milho))
            . '; sem safra ela soma ' . $this->kg($semSafra) . ' kg de "grão", que é o que o pátio pede hoje — conferir no pátio (Fase 6)');
    }

    private function t6(): void
    {
        $m = $this->m();
        [$a, $b] = [$m->silo('T6 silo A'), $m->silo('T6 silo B')];
        $m->estoque($a, 'soja', 500000);
        $m->estoque($b, 'soja', 500000);
        $finais = [];
        for ($i = 0; $i < 10; $i++) {
            foreach ([[$a, $b], [$b, $a]] as [$de, $para]) {
                $c = $this->p->nova('TRANSFERENCIA', 'soja', [['UNIDADE', $de]], [['UNIDADE', $para]]);
                $erro = $this->p->preparar($c, 15000 + 1000 * ($i + 1), 15000);
                if ($erro) {
                    $this->r->erro('T6', 'A→B e B→A ao mesmo tempo, sem deadlock e sem perder grão', 'preparo recusado: ' . $erro->resumo());
                    return;
                }
                $finais[] = ['POST', 'v1/carga/sincronizar', $this->p->payload($c)];
            }
        }
        $resps = $this->api()->lote($finais);
        $codigos = array_count_values(array_map(fn ($r) => $r->status, $resps));
        ksort($codigos);
        $soma = Patio::saldo('UNIDADE', $a) + Patio::saldo('UNIDADE', $b);
        $dif = [];
        if (array_filter($resps, fn ($r) => $r->erroServidor())) {
            $dif[] = 'respostas ' . json_encode($codigos);
        }
        if (abs($soma - 1000000) > 0.0005) {
            $dif[] = 'soma dos silos ' . $this->kg($soma) . ' (era 1.000.000)';
        }
        $this->resultado('T6', 'A→B e B→A ao mesmo tempo, sem deadlock e sem perder grão', $dif, '20 fechamentos simultâneos, ' . json_encode($codigos));
    }

    private function t7(): void
    {
        $m = $this->m();
        $inativo = $m->silo('T7 silo inativo', 'SILOBAG', null, true);
        $c = $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $inativo]]);
        $novo = $this->p->percorrer($c, 30000, 15000);

        $vivo = $m->silo('T7 silo desativado depois');
        $antigo = $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $vivo]]);
        $ok = $this->p->percorrer($antigo, 30000, 15000)->ok();
        $this->api()->post("v1/unidade-armazenadora/{$vivo}/inativo");
        $antigo['placa'] = Patio::placa();
        $edita = $this->p->enviar($antigo);
        $this->api()->delete("v1/unidade-armazenadora/{$vivo}/inativo");

        $dif = [];
        if ($novo->status !== 422) {
            $dif[] = 'carga nova no silo inativo: ' . $novo->resumo() . ' (regra: 422)';
        }
        if (!$ok || !$edita->ok()) {
            $dif[] = 'carga antiga do silo desativado não salvou: ' . $edita->resumo();
        }
        $this->resultado('T7', 'Silo inativo não recebe carga nova; carga antiga dele segue editável', $dif);
    }
}
