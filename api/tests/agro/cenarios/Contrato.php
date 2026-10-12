<?php

namespace AgroBateria\Cenarios;

use AgroBateria\Cenario;
use AgroBateria\Massa;
use AgroBateria\Patio;
use Illuminate\Support\Facades\DB;

/**
 * C — contrato: entregue e saldo a entregar. Soja a 60 kg/sc: 200 sc = 12.000 kg.
 * Cada cenário tem contrato e silo próprios; o silo nasce com estoque de sobra.
 *
 * Regra aceita em 29/09/2026: o contrato PODE ser carregado além do saldo (o
 * caminhão completa a carga para aproveitar o frete e o comprador aceita). O
 * servidor não bloqueia; o pátio avisa quanto passa e a tela mostra o excesso.
 */
final class Contrato extends Cenario
{
    public function rodar(): void
    {
        $this->r->camada('C — Contrato: entregue e saldo a entregar');
        $this->caso('C1', 'Carga além do saldo do contrato é aceita (aceitar e avisar)', fn () => $this->c1());
        $this->caso('C2', 'Ajuste manual entra no saldo a entregar da tela', fn () => $this->c2());
        $this->caso('C3', 'Ajuste estornado sai do saldo a entregar da tela', fn () => $this->c3());
        $this->caso('C4', 'Reativar carga cancelada é aceito mesmo passando do contratado', fn () => $this->c4());
        $this->caso('C5', 'Cancelar pelo pátio nunca é barrado', fn () => $this->c5());
        $this->r->info('C6', 'O aviso de excesso do pátio usa o saldo a entregar da tela',
            'o servidor não barra (aceitar e avisar); o aviso é do pátio — conferir na tela (Fase 4, TASK-170)');
        $this->caso('C7', 'Contrato com volume em aberto aceita qualquer quantidade', fn () => $this->c7());
        $this->caso('C8', 'Entregue acima do contratado aparece, não vira saldo zero', fn () => $this->c8());
        $this->caso('C9', 'Entregue da safra não soma contrato de compra', fn () => $this->c9());
    }

    /** Silo com estoque e contrato de venda de soja de $sacas (null = em aberto). */
    private function cenario(string $nome, ?float $sacas): array
    {
        $m = $this->m();
        $silo = $m->silo("{$nome} silo");
        $m->estoque($silo, 'soja', 500000);
        return [$silo, $m->contrato('soja', 'VENDA', $sacas)];
    }

    /** Expedição silo→contrato de $kg (tara 15.000), percorrendo o fluxo inteiro. */
    private function expedir(int $silo, int $ct, int $kg, ?array &$c = null)
    {
        $c = $this->p->nova('SAIDA', 'soja', [['UNIDADE', $silo]], [['CONTRATO', $ct]]);
        return $this->p->percorrer($c, 15000 + $kg, 15000);
    }

    private function manual(int $ct, float $kg): object
    {
        $resp = $this->api()->post('v1/movimento-grao', [
            'data' => Massa::DIA, 'papel' => 'DESTINO', 'contatipo' => 'CONTRATO', 'codcontrato' => $ct,
            'bruto' => $kg, 'desconto' => 0, 'liquido' => $kg, 'observacao' => Massa::PREFIXO,
        ]);
        if (!$resp->ok()) {
            throw new \RuntimeException('ajuste manual recusado: ' . $resp->resumo());
        }
        return (object) $resp->dado();
    }

    private function saldoTela(int $ct): ?float
    {
        $s = $this->api()->get("v1/contrato/{$ct}")->dado()['saldokg'] ?? null;
        return $s === null ? null : (float) $s;
    }

    private function c1(): void
    {
        [$silo, $ct] = $this->cenario('C1', 200);
        $dif = [];
        foreach ([12000 => 'no saldo de 12.000', 2 => '2 kg além do saldo', 3000 => '3.000 kg além do saldo'] as $kg => $o_que) {
            $r = $this->expedir($silo, $ct, $kg);
            if (!$r->ok()) {
                $dif[] = "{$o_que} recusado: " . $r->resumo();
            }
        }
        $this->resultado('C1', 'Carga além do saldo do contrato é aceita (aceitar e avisar)', $dif,
            'contrato com ' . $this->kg(Patio::saldo('CONTRATO', $ct)) . ' kg de 12.000');
    }

    private function c2(): void
    {
        [$silo, $ct] = $this->cenario('C2', 200);
        $this->manual($ct, 10000);
        $tela = $this->saldoTela($ct);
        $r = $this->expedir($silo, $ct, 3000);
        $dif = [];
        if ($tela === null || abs($tela - 2000) > 0.5) {
            $dif[] = 'com 10.000 kg lançados no manual a tela mostra saldo ' . $this->kg($tela) . ' (regra: 2.000)';
        }
        if (!$r->ok()) {
            $dif[] = 'a carga de 3.000 kg foi recusada: ' . $r->resumo();
        }
        $this->resultado('C2', 'Ajuste manual entra no saldo a entregar da tela', $dif, 'saldo 2.000 e a carga de 3.000 aceita');
    }

    private function c3(): void
    {
        [, $ct] = $this->cenario('C3', 200);
        $mov = $this->manual($ct, 10000);
        $this->api()->post("v1/movimento-grao/{$mov->codmovimentograo}/inativo");
        $tela = $this->saldoTela($ct);
        $this->r->checar('C3', 'Ajuste estornado sai do saldo a entregar da tela', $tela !== null && abs($tela - 12000) < 0.5,
            'depois do estorno a tela mostra saldo ' . $this->kg($tela) . ' (regra: 12.000)');
    }

    private function c4(): void
    {
        [$silo, $ct] = $this->cenario('C4', 200);
        $x = null;
        $r1 = $this->expedir($silo, $ct, 10000, $x);
        $bx = Patio::banco($x['uuid']);
        $this->api()->post("v1/carga/{$bx->codcarga}/inativo");
        $r2 = $this->expedir($silo, $ct, 10000);
        $r3 = $this->api()->delete("v1/carga/{$bx->codcarga}/inativo");
        if (!$r1->ok() || !$r2->ok()) {
            $this->r->erro('C4', 'Reativar carga cancelada é aceito mesmo passando do contratado', 'preparo recusado: ' . $r1->status . '/' . $r2->status);
            return;
        }
        $entregue = Patio::saldo('CONTRATO', $ct);
        $this->r->checar('C4', 'Reativar carga cancelada é aceito mesmo passando do contratado', $r3->ok() && abs($entregue - 20000) < 0.5,
            'reativação ' . ($r3->ok() ? 'aceita' : 'recusada: ' . $r3->resumo()) . '; contrato com ' . $this->kg($entregue) . ' kg de 12.000');
    }

    private function c5(): void
    {
        [$silo, $ct] = $this->cenario('C5', 200);
        $x = null;
        $r1 = $this->expedir($silo, $ct, 10000, $x);
        if (!$r1->ok()) {
            $this->r->erro('C5', 'Cancelar pelo pátio nunca é barrado', 'preparo recusado: ' . $r1->resumo());
            return;
        }
        DB::table('tblcontrato')->where('codcontrato', $ct)->update(['quantidade' => 100]); // contratado cai para 6.000
        $x['inativo'] = date('Y-m-d H:i:s');
        $r = $this->p->enviar($x);
        $b = Patio::banco($x['uuid']);
        $this->r->checar('C5', 'Cancelar pelo pátio nunca é barrado', $r->ok() && $b->inativo !== null,
            $r->ok() ? 'cancelada' : 'recusou o cancelamento: ' . $r->resumo() . ' — o aparelho mostra cancelada e o servidor segue contando');
    }

    private function c7(): void
    {
        [$silo, $ct] = $this->cenario('C7', null);
        $r = $this->expedir($silo, $ct, 50000);
        $this->r->checar('C7', 'Contrato com volume em aberto aceita qualquer quantidade', $r->ok(), $r->ok() ? '50.000 kg aceitos' : $r->resumo());
    }

    private function c8(): void
    {
        [$silo, $ct] = $this->cenario('C8', 200);
        $r = $this->expedir($silo, $ct, 10000);
        if (!$r->ok()) {
            $this->r->erro('C8', 'Entregue acima do contratado aparece, não vira saldo zero', 'preparo recusado: ' . $r->resumo());
            return;
        }
        DB::table('tblcontrato')->where('codcontrato', $ct)->update(['quantidade' => 100]); // 6.000 kg < 10.000 entregues
        $saldo = $this->api()->get("v1/contrato/{$ct}")->dado()['saldokg'] ?? null;
        $this->r->checar('C8', 'Entregue acima do contratado aparece, não vira saldo zero', $saldo !== null && (float) $saldo <= -3999,
            'contratado 6.000, entregue 10.000: a tela mostra saldo ' . $this->kg($saldo) . ' (regra: -4.000)');
    }

    private function c9(): void
    {
        $m = $this->m();
        $silo = $m->silo('C9 silo');
        $compra = $m->contrato('soja', 'COMPRA', 500);
        $c = $this->p->nova('ENTRADA', 'soja', [['CONTRATO', $compra]], [['UNIDADE', $silo]]);
        $r = $this->p->percorrer($c, 20000, 15000);
        if (!$r->ok()) {
            $this->r->erro('C9', 'Entregue da safra não soma contrato de compra', 'preparo recusado: ' . $r->resumo());
            return;
        }
        $venda = (float) DB::table('tblmovimentograo as m')->join('tblcontrato as k', 'k.codcontrato', '=', 'm.codcontrato')
            ->where('m.contatipo', 'CONTRATO')->where('m.codsafra', $m->safra['soja'])->whereNull('m.inativo')
            ->where('k.operacao', 'VENDA')->sum('m.liquido');
        $tela = (float) ($this->api()->get("v1/safra/{$m->safra['soja']}/comercial")->dado()['entreguekg'] ?? -1);
        $this->r->checar('C9', 'Entregue da safra não soma contrato de compra', abs($tela - round($venda)) < 1,
            'tela ' . $this->kg($tela) . ' kg; vendas ' . $this->kg(round($venda)) . ' kg (a compra de 5.000 kg não entra)');
    }
}
