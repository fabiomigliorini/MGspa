<?php

namespace AgroBateria\Cenarios;

use AgroBateria\Cenario;
use AgroBateria\Patio;
use Illuminate\Support\Facades\DB;

/**
 * R — corrida entre aparelhos. Os pedidos saem AO MESMO TEMPO (curl_multi),
 * como dois aparelhos sincronizando juntos ou o mesmo aparelho reenviando
 * depois de um timeout.
 */
final class Corrida extends Cenario
{
    public function rodar(): void
    {
        $this->r->camada('R — Corrida entre aparelhos');
        $this->caso('R1', 'Mesma carga nova enviada 5× ao mesmo tempo', fn () => $this->r1());
        $this->caso('R2', 'Mesma carga existente enviada 5× ao mesmo tempo', fn () => $this->r2());
        $this->caso('R3', 'Cópia velha de outro aparelho depois de finalizar', fn () => $this->r3());
        $this->caso('R4', 'Cópia velha de outro aparelho depois de cancelar', fn () => $this->r4());
        $this->caso('R5', '3 caminhões fechando juntos no mesmo contrato perto do saldo', fn () => $this->r5());
        $this->caso('R6', 'Reativar enquanto a carga sincroniza', fn () => $this->r6());
        $this->caso('R7', 'Reenvio da mesma carga finalizada (timeout)', fn () => $this->r7());
        $this->caso('R8', 'Tabela muda com a carga no pátio e o aparelho com cache velho', fn () => $this->r8());
    }

    private function entrada(string $nome): array
    {
        $m = $this->m();
        $silo = $m->silo("{$nome} silo");
        return $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $silo]]);
    }

    private function codigos(array $resps): string
    {
        $c = array_count_values(array_map(fn ($r) => $r->status, $resps));
        ksort($c);
        return implode(', ', array_map(fn ($k, $v) => "{$v}× {$k}", array_keys($c), $c));
    }

    private function r1(): void
    {
        $c = $this->entrada('R1');
        $payload = $this->p->payload($c);
        $resps = $this->api()->lote(array_fill(0, 5, ['POST', 'v1/carga/sincronizar', $payload]));
        $n = Patio::contarCargas($c['uuid']);
        $erros = array_filter($resps, fn ($r) => $r->erroServidor());
        $this->r->checar('R1', 'Mesma carga nova enviada 5× ao mesmo tempo', $n === 1 && !$erros,
            "{$n} carga(s) gravada(s); respostas: " . $this->codigos($resps));
    }

    private function r2(): void
    {
        $m = $this->m();
        $c = $this->entrada('R2');
        $c['classificacao'] = [['codparametroclassificacao' => $m->codParametro('soja', 'Umidade'), 'leitura' => 16]];
        $c['pbt'] = 45000;
        $c['etapa'] = 'CLASSIFICACAO';
        if (!$this->p->enviar($c)->ok()) {
            $this->r->erro('R2', 'Mesma carga existente enviada 5× ao mesmo tempo', 'preparo recusado');
            return;
        }
        $payload = $this->p->payload($c);
        $resps = $this->api()->lote(array_fill(0, 5, ['POST', 'v1/carga/sincronizar', $payload]));
        $b = Patio::banco($c['uuid']);
        $pontos = count($b->pontos);
        $leituras = count($b->classificacao);
        $ok = $pontos === 2 && $leituras === 1 && !array_filter($resps, fn ($r) => $r->erroServidor());
        $this->r->checar('R2', 'Mesma carga existente enviada 5× ao mesmo tempo', $ok,
            "{$pontos} pontos (eram 2), {$leituras} leitura(s) (era 1); respostas: " . $this->codigos($resps));
    }

    private function r3(): void
    {
        $c = $this->entrada('R3');
        $erro = $this->p->preparar($c, 45000, 15000);
        if ($erro) {
            $this->r->erro('R3', 'Cópia velha de outro aparelho depois de finalizar', 'preparo recusado: ' . $erro->resumo());
            return;
        }
        $velha = $c;              // o outro aparelho puxou antes do fechamento
        $velha['etapa'] = 'TARA';
        $fecha = $this->p->enviar($c);
        $velha['placa'] = Patio::placa();
        $resp = $this->p->enviar($velha);
        $b = Patio::banco($c['uuid']);
        $ok = $fecha->ok() && $b->etapa === 'FINALIZADO' && count($b->movimentos) > 0;
        $this->r->checar('R3', 'Cópia velha de outro aparelho depois de finalizar', $ok,
            $ok ? 'a cópia velha levou ' . $resp->status . ' e a carga seguiu finalizada'
                : 'a cópia velha levou ' . $resp->status . ': a carga voltou para ' . $b->etapa . ' e o extrato ficou com ' . count($b->movimentos) . ' linhas');
    }

    private function r4(): void
    {
        $c = $this->entrada('R4');
        if (!$this->p->percorrer($c, 45000, 15000)->ok()) {
            $this->r->erro('R4', 'Cópia velha de outro aparelho depois de cancelar', 'preparo recusado');
            return;
        }
        $velha = $c;
        $c['inativo'] = date('Y-m-d H:i:s');
        $cancela = $this->p->enviar($c);
        $velha['placa'] = Patio::placa();
        $resp = $this->p->enviar($velha);
        $b = Patio::banco($c['uuid']);
        $ok = $cancela->ok() && $b->inativo !== null && count($b->movimentos) === 0;
        $this->r->checar('R4', 'Cópia velha de outro aparelho depois de cancelar', $ok,
            $ok ? 'a cópia velha levou ' . $resp->status . ' e a carga seguiu cancelada'
                : 'a cópia velha levou ' . $resp->status . ': a carga voltou a valer e o grão voltou para o silo');
    }

    private function r5(): void
    {
        $m = $this->m();
        $silo = $m->silo('R5 silo');
        $m->estoque($silo, 'soja', 200000);
        // 60.000 kg de saldo e 75 t chegando juntas: a regra aceita o excesso (o
        // pátio avisa); o que não pode é erro, grão perdido ou lançado em dobro.
        $ct = $m->contrato('soja', 'VENDA', 1000);
        $finais = [];
        for ($i = 0; $i < 3; $i++) {
            $c = $this->p->nova('SAIDA', 'soja', [['UNIDADE', $silo]], [['CONTRATO', $ct]]);
            $erro = $this->p->preparar($c, 40000, 15000);
            if ($erro) {
                $this->r->erro('R5', '3 caminhões fechando juntos no mesmo contrato perto do saldo', 'preparo recusado: ' . $erro->resumo());
                return;
            }
            $finais[] = ['POST', 'v1/carga/sincronizar', $this->p->payload($c)];
        }
        $resps = $this->api()->lote($finais);
        $aceitos = count(array_filter($resps, fn ($r) => $r->ok()));
        $entregue = Patio::saldo('CONTRATO', $ct);
        $ok = $aceitos === 3 && abs($entregue - 75000) < 0.5;
        $this->r->checar('R5', '3 caminhões fechando juntos no mesmo contrato perto do saldo', $ok,
            "{$aceitos} de 3 aceitos; contrato com " . $this->kg($entregue) . ' kg (regra: 75.000, 15.000 além do saldo, com aviso); respostas: ' . $this->codigos($resps));
    }

    private function r6(): void
    {
        $c = $this->entrada('R6');
        if (!$this->p->percorrer($c, 45000, 15000)->ok()) {
            $this->r->erro('R6', 'Reativar enquanto a carga sincroniza', 'preparo recusado');
            return;
        }
        $cod = Patio::banco($c['uuid'])->codcarga;
        $payload = $this->p->payload($c);
        $duplicadas = 0;
        $erros = [];
        for ($i = 0; $i < 10; $i++) {
            $this->api()->post("v1/carga/{$cod}/inativo");
            $resps = $this->api()->lote([['DELETE', "v1/carga/{$cod}/inativo", null], ['POST', 'v1/carga/sincronizar', $payload]]);
            foreach ($resps as $r) {
                if ($r->erroServidor()) {
                    $erros[] = $r->status;
                }
            }
            $linhas = DB::table('tblmovimentograo')->where('codcarga', $cod)->where('manual', false)->whereNull('inativo')->count();
            if ($linhas > 2) {
                $duplicadas++;
            }
        }
        $this->r->checar('R6', 'Reativar enquanto a carga sincroniza', $duplicadas === 0 && !$erros,
            "{$duplicadas} de 10 rodadas com extrato duplicado" . ($erros ? '; erros ' . implode(',', $erros) : ''));
    }

    private function r7(): void
    {
        $c = $this->entrada('R7');
        if (!$this->p->percorrer($c, 45000, 15000)->ok()) {
            $this->r->erro('R7', 'Reenvio da mesma carga finalizada (timeout)', 'preparo recusado');
            return;
        }
        $payload = $this->p->payload($c);
        $resps = $this->api()->lote(array_fill(0, 3, ['POST', 'v1/carga/sincronizar', $payload]));
        $b = Patio::banco($c['uuid']);
        $ok = Patio::contarCargas($c['uuid']) === 1 && count($b->movimentos) === 2 && !array_filter($resps, fn ($r) => !$r->ok());
        $this->r->checar('R7', 'Reenvio da mesma carga finalizada (timeout)', $ok,
            count($b->movimentos) . ' linhas no extrato (eram 2); respostas: ' . $this->codigos($resps));
    }

    private function r8(): void
    {
        $m = $this->m();
        $umidade = $m->codParametro('soja', 'Umidade');
        $cache = $m->parametros('soja'); // o que o aparelho tem no Dexie
        $c = $this->entrada('R8');
        $erro = $this->p->preparar($c, 45000, 15000, ['leituras' => [$umidade => 18]], $cache);
        if ($erro) {
            $this->r->erro('R8', 'Tabela muda com a carga no pátio e o aparelho com cache velho', 'preparo recusado: ' . $erro->resumo());
            return;
        }
        DB::table('tblparametroclassificacao')->where('codparametroclassificacao', $umidade)->update(['tolerancia' => 12]);
        try {
            $resp = $this->p->enviar($c, $cache);
        } finally {
            DB::table('tblparametroclassificacao')->where('codparametroclassificacao', $umidade)->update(['tolerancia' => 14]);
        }
        $this->r->checar('R8', 'Tabela muda com a carga no pátio e o aparelho com cache velho', $resp->ok(),
            $resp->ok() ? 'finalizou' : 'a carga ficou presa: ' . $resp->resumo());
    }
}
