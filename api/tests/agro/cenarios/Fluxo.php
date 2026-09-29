<?php

namespace AgroBateria\Cenarios;

use AgroBateria\Cenario;
use AgroBateria\Massa;
use AgroBateria\Patio;
use AgroBateria\Referencia;
use Illuminate\Support\Facades\DB;

/** A — o romaneio de ponta a ponta e os números que ele grava. */
final class Fluxo extends Cenario
{
    public function rodar(): void
    {
        $this->r->camada('A — Fluxo e valores do romaneio');
        $this->caso('A1', 'Recebimento talhão→silo, ciclo completo', fn () => $this->a1());
        $this->caso('A2', 'Expedição silo→contrato sem desconto', fn () => $this->a2());
        $this->caso('A3', 'Transferência entre silos sem desconto', fn () => $this->a3());
        $this->caso('A4', 'Rateio em 3 origens (33,3/33,3/33,4) com desconto', fn () => $this->a4());
        $this->caso('A5', 'Cancelar e reativar devolvem o extrato igual', fn () => $this->a5());
        $this->caso('A6', 'Ajuste manual de entrada e de retirada no silo', fn () => $this->a6());
        $this->caso('A7', 'Mudar a tabela não muda romaneio já finalizado', fn () => $this->a7());
        $this->a8();
        $this->caso('A9', 'Estoque & Extrato com mais de 50 lançamentos', fn () => $this->a9());
    }

    private function a1(): void
    {
        $m = $this->m();
        $silo = $m->silo('A1 silo');
        $leit = $m->leituras('soja', ['Impureza' => 2, 'Umidade' => 18, 'Avariados' => 10, 'Esverdeados' => 5, 'Quebrados' => 20]);
        $c = $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $silo]]);
        $resp = $this->p->percorrer($c, 45000, 15000, ['leituras' => $leit]);
        if (!$resp->ok()) {
            $this->r->falha('A1', 'Recebimento talhão→silo, ciclo completo', 'servidor recusou: ' . $resp->resumo());
            return;
        }
        $b = Patio::banco($c['uuid']);
        $params = $m->parametros('soja');
        $ref = Referencia::desconto(45000, 15000, $leit, $params);
        $refG = Referencia::desconto(45000, 15000, $leit, $params, 1000);
        $dif = $this->difPesos($b, $ref, $refG);
        foreach ($b->classificacao as $cc) {
            $esp = $ref['itens'][(int) $cc->codparametroclassificacao] ?? 0;
            if (abs((float) $cc->desconto - $esp) > 0.0005) {
                $dif[] = 'item #' . $cc->codparametroclassificacao . ' ' . $this->kg($cc->desconto) . ' (regra: ' . $this->kg($esp) . ')';
            }
        }
        $dif = array_merge($dif, $this->difExtrato($b, Referencia::movimentos($ref['bruto'], $ref['liquido'], Patio::pontosReferencia($b))));
        $this->resultado('A1', 'Recebimento talhão→silo, ciclo completo', $dif,
            'líquido ' . $this->kg($b->liquido) . ' kg, ' . number_format($b->liquido / 60, 1, ',', '.') . ' sc');
    }

    private function a2(): void
    {
        $m = $this->m();
        $silo = $m->silo('A2 silo');
        $m->estoque($silo, 'soja', 100000);
        $ct = $m->contrato('soja', 'VENDA', 1000);
        $c = $this->p->nova('SAIDA', 'soja', [['UNIDADE', $silo]], [['CONTRATO', $ct]]);
        $resp = $this->p->percorrer($c, 35000, 15000);
        if (!$resp->ok()) {
            $this->r->falha('A2', 'Expedição silo→contrato sem desconto', 'servidor recusou: ' . $resp->resumo());
            return;
        }
        $dif = [];
        $silo1 = Patio::saldo('UNIDADE', $silo);
        if (abs($silo1 - 80000) > 0.0005) {
            $dif[] = 'silo ' . $this->kg($silo1) . ' (regra: 80.000)';
        }
        $tela = $this->api()->get("v1/contrato/{$ct}")->dado();
        if (abs((float) ($tela['saldokg'] ?? -1) - 40000) > 0.0005) {
            $dif[] = 'saldo a entregar na tela ' . $this->kg($tela['saldokg'] ?? null) . ' (regra: 40.000)';
        }
        $this->resultado('A2', 'Expedição silo→contrato sem desconto', $dif, 'silo 80.000, saldo a entregar 40.000');
    }

    private function a3(): void
    {
        $m = $this->m();
        [$a, $b] = [$m->silo('A3 silo A'), $m->silo('A3 silo B')];
        $m->estoque($a, 'soja', 50000);
        $c = $this->p->nova('TRANSFERENCIA', 'soja', [['UNIDADE', $a]], [['UNIDADE', $b]]);
        $resp = $this->p->percorrer($c, 25000, 15000);
        if (!$resp->ok()) {
            $this->r->falha('A3', 'Transferência entre silos sem desconto', 'servidor recusou: ' . $resp->resumo());
            return;
        }
        $sa = Patio::saldo('UNIDADE', $a);
        $sb = Patio::saldo('UNIDADE', $b);
        $dif = [];
        if (abs($sa - 40000) > 0.0005 || abs($sb - 10000) > 0.0005) {
            $dif[] = 'silos ' . $this->kg($sa) . ' / ' . $this->kg($sb) . ' (regra: 40.000 / 10.000)';
        }
        $this->resultado('A3', 'Transferência entre silos sem desconto', $dif, 'A 40.000, B 10.000, soma 50.000');
    }

    private function a4(): void
    {
        $m = $this->m();
        $silo = $m->silo('A4 silo');
        $leit = $m->leituras('soja', ['Umidade' => 15]);
        $origens = [['PLANTIO', $m->plantio['soja'][0], 33.3], ['PLANTIO', $m->plantio['soja'][1], 33.3], ['PLANTIO', $m->plantio['soja'][2], 33.4]];
        $c = $this->p->nova('ENTRADA', 'soja', $origens, [['UNIDADE', $silo]]);
        $resp = $this->p->percorrer($c, 16001, 15000, ['leituras' => $leit]);
        if (!$resp->ok()) {
            $this->r->falha('A4', 'Rateio em 3 origens (33,3/33,3/33,4) com desconto', 'servidor recusou: ' . $resp->resumo());
            return;
        }
        $b = Patio::banco($c['uuid']);
        $dif = [];
        foreach (['ORIGEM', 'DESTINO'] as $papel) {
            $soma = array_sum(array_map(fn ($p) => (float) $p->liquido, array_filter($b->pontos, fn ($p) => $p->papel === $papel)));
            if (abs($soma - (float) $b->liquido) > 0.0005) {
                $dif[] = strtolower($papel) . ' soma ' . $this->kg($soma) . ' ≠ líquido ' . $this->kg($b->liquido);
            }
        }
        foreach ($b->pontos as $p) {
            $conta = $p->codplantio ?? $p->codunidadearmazenadora ?? $p->codcontrato;
            $mv = array_values(array_filter($b->movimentos, fn ($x) => $x->papel === $p->papel
                && ($x->codplantio ?? $x->codunidadearmazenadora ?? $x->codcontrato) == $conta));
            $ext = $mv ? abs((float) $mv[0]->liquido) : 0.0;
            if (abs($ext - (float) $p->liquido) > 0.0005) {
                $dif[] = "ponto {$conta} " . $this->kg($p->liquido) . ' ≠ extrato ' . $this->kg($ext);
            }
        }
        $ref = Referencia::desconto(16001, 15000, $leit, $m->parametros('soja'));
        $dif = array_merge($dif, $this->difPesos($b, $ref));
        $this->resultado('A4', 'Rateio em 3 origens (33,3/33,3/33,4) com desconto', $dif, 'pontos = extrato = líquido');
    }

    private function a5(): void
    {
        $m = $this->m();
        $silo = $m->silo('A5 silo');
        $c = $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $silo]]);
        $resp = $this->p->percorrer($c, 40000, 15000, ['leituras' => $m->leituras('soja', ['Umidade' => 16])]);
        if (!$resp->ok()) {
            $this->r->falha('A5', 'Cancelar e reativar devolvem o extrato igual', 'servidor recusou: ' . $resp->resumo());
            return;
        }
        $antes = Patio::banco($c['uuid']);
        $foto = fn ($b) => array_map(fn ($x) => [$x->papel, $x->contatipo, (float) $x->liquido], $b->movimentos);
        $dif = [];

        $r1 = $this->api()->post("v1/carga/{$antes->codcarga}/inativo");
        $cancelada = Patio::banco($c['uuid']);
        if (!$r1->ok() || count($cancelada->movimentos) !== 0 || abs(Patio::saldo('UNIDADE', $silo)) > 0.0005) {
            $dif[] = 'cancelar pelo botão: ' . $r1->status . ', ' . count($cancelada->movimentos) . ' linhas no extrato';
        }
        $r2 = $this->api()->delete("v1/carga/{$antes->codcarga}/inativo");
        $reativada = Patio::banco($c['uuid']);
        if (!$r2->ok() || $foto($reativada) != $foto($antes)) {
            $dif[] = 'reativar: ' . $r2->status . ', extrato diferente do original';
        }
        $c['inativo'] = date('Y-m-d H:i:s');
        $r3 = $this->p->enviar($c);
        $pelo = Patio::banco($c['uuid']);
        if (!$r3->ok() || $pelo->inativo === null || count($pelo->movimentos) !== 0) {
            $dif[] = 'cancelar pelo pátio: ' . $r3->resumo();
        }
        $this->resultado('A5', 'Cancelar e reativar devolvem o extrato igual', $dif, 'botão, reativação e pátio');
    }

    private function a6(): void
    {
        $m = $this->m();
        $silo = $m->silo('A6 silo');
        $base = ['data' => Massa::DIA, 'codsafra' => $m->safra['soja'], 'contatipo' => 'UNIDADE', 'codunidadearmazenadora' => $silo, 'observacao' => Massa::PREFIXO];
        $dif = [];
        $e = $this->api()->post('v1/movimento-grao', $base + ['papel' => 'DESTINO', 'bruto' => 1000, 'desconto' => 0, 'liquido' => 1000]);
        $s1 = Patio::saldo('UNIDADE', $silo);
        $s = $this->api()->post('v1/movimento-grao', $base + ['papel' => 'ORIGEM', 'bruto' => 1000, 'desconto' => 0, 'liquido' => 1000]);
        $s2 = Patio::saldo('UNIDADE', $silo);
        if (!$e->ok() || !$s->ok()) {
            $dif[] = 'lançamento recusado: ' . $e->status . '/' . $s->status;
        }
        if (abs($s1 - 1000) > 0.0005 || abs($s2) > 0.0005) {
            $dif[] = 'entrada de 1.000 e retirada de 1.000 deixaram o silo em ' . $this->kg($s2) . ' (regra: 0)';
        }
        $so = $this->api()->post('v1/movimento-grao', $base + ['papel' => 'DESTINO', 'liquido' => 500]);
        $s3 = Patio::saldo('UNIDADE', $silo);
        if (!$so->ok() || abs(($s3 - $s2) - 500) > 0.0005) {
            $dif[] = 'entrada só com "líquido 500" gravou ' . $this->kg($s3 - $s2) . ' (regra: 500)';
        }
        $this->resultado('A6', 'Ajuste manual de entrada e de retirada no silo', $dif, 'entrada soma, retirada baixa');
    }

    private function a7(): void
    {
        $m = $this->m();
        $silo = $m->silo('A7 silo');
        $umidade = $m->codParametro('soja', 'Umidade');
        $c = $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $silo]]);
        $resp = $this->p->percorrer($c, 45000, 15000, ['leituras' => [$umidade => 18]]);
        if (!$resp->ok()) {
            $this->r->falha('A7', 'Mudar a tabela não muda romaneio já finalizado', 'servidor recusou: ' . $resp->resumo());
            return;
        }
        $antes = (float) Patio::banco($c['uuid'])->liquido;
        DB::table('tblparametroclassificacao')->where('codparametroclassificacao', $umidade)->update(['tolerancia' => 13]);
        try {
            $c['placa'] = Patio::placa(); // qualquer edição regrava a carga
            $r2 = $this->p->enviar($c);
            $depois = (float) Patio::banco($c['uuid'])->liquido;
        } finally {
            DB::table('tblparametroclassificacao')->where('codparametroclassificacao', $umidade)->update(['tolerancia' => 14]);
        }
        $dif = [];
        if (!$r2->ok()) {
            $dif[] = 'regravar a placa foi recusado: ' . $r2->resumo();
        } elseif (abs($depois - $antes) > 0.0005) {
            $dif[] = 'líquido mudou de ' . $this->kg($antes) . ' para ' . $this->kg($depois) . ' só por trocar a placa';
        }
        $this->resultado('A7', 'Mudar a tabela não muda romaneio já finalizado', $dif, 'líquido ' . $this->kg($antes) . ' mantido');
    }

    /** Entrada ruim tem que voltar 422 com mensagem — nunca 500, nunca gravar. */
    private function a8(): void
    {
        $m = $this->m();
        $silo = $m->silo('A8 silo');
        $umidade = $m->codParametro('soja', 'Umidade');
        $nova = fn (array $extra = []) => $this->p->payload(array_replace(
            $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $silo]]),
            ['etapa' => 'CLASSIFICACAO', 'pbt' => 45000],
            $extra
        ));
        $casos = [
            'A8a' => ['uuid malformado', $nova(['uuid' => 'nao-e-uuid'])],
            'A8b' => ['PBT com fração de kg (45000,5)', $nova(['pbt' => 45000.5])],
            'A8c' => ['PBT acima de 150.000 kg', $nova(['pbt' => 200000])],
            'A8d' => ['PBT gigante (1e11)', $nova(['pbt' => 100000000000])],
            'A8e' => ['leitura 1000 %', $nova(['classificacao' => [['codparametroclassificacao' => $umidade, 'leitura' => 1000]]])],
            'A8f' => ['mesmo parâmetro duas vezes', $nova(['classificacao' => [
                ['codparametroclassificacao' => $umidade, 'leitura' => 15],
                ['codparametroclassificacao' => $umidade, 'leitura' => 16],
            ]])],
            'A8g' => ['tara maior que o PBT', $nova(['etapa' => 'TARA', 'tara' => 50000])],
        ];
        foreach ($casos as $id => [$titulo, $payload]) {
            $this->caso($id, $titulo, function () use ($id, $titulo, $payload) {
                $resp = $this->api()->post('v1/carga/sincronizar', $payload);
                $this->r->checar($id, "Recusa {$titulo} com 422", $resp->status === 422, 'veio ' . $resp->resumo());
            });
        }
    }

    private function a9(): void
    {
        $m = $this->m();
        $silo = $m->silo('A9 silo');
        for ($i = 0; $i < 60; $i++) {
            $m->estoque($silo, 'soja', 10);
        }
        $resp = $this->api()->get('v1/movimento-grao', ['codsafra' => $m->safra['soja'], 'codunidadearmazenadora' => $silo]);
        $meta = $resp->json['meta'] ?? [];
        $this->r->info('A9', 'Estoque & Extrato com mais de 50 lançamentos',
            'a API pagina (' . ($meta['total'] ?? '?') . ' lançamentos em ' . ($meta['last_page'] ?? '?') . ' páginas); '
            . 'a tela precisa rolar até o fim — conferir no #/extrato (Fase 4, TASK-170)');
    }
}
