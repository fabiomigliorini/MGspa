<?php

namespace AgroBateria\Cenarios;

use AgroBateria\Cenario;
use Illuminate\Support\Facades\DB;

/**
 * L — listagem de romaneios e relatório PDF. Regra (plano de 28/09): totais
 * separados por tipo (recebido, expedido, transferido), canceladas fora da
 * soma e contadas à parte; o total do PDF é o da tela.
 *
 * O relatório é lido pelo ?html=1 (o mesmo HTML que vira PDF). Quando a Fase 8
 * mudar o layout da blade, o leitor de tabela daqui muda junto.
 */
final class Listagem extends Cenario
{
    private const AGRUPAMENTOS = ['nenhum', 'dia', 'mes', 'sentido', 'etapa', 'safra', 'cultura', 'unidade', 'plantio', 'contrato', 'pessoa', 'motorista', 'placa'];
    private int $safra = 0;

    public function rodar(): void
    {
        $this->r->camada('L — Listagem e relatório de romaneios');
        $this->caso('L0', 'Recorte com os três tipos e uma cancelada', fn () => $this->recorte());
        $this->caso('L1', 'Totais da tela separados por tipo', fn () => $this->l1());
        $this->caso('L2', 'Canceladas fora dos totais', fn () => $this->l2());
        $this->caso('L3', 'Relatório: subtotais somam o total, carga em um grupo só', fn () => $this->l3());
        $this->caso('L4', 'Mesmo filtro: total da tela = linhas do relatório', fn () => $this->l4());
        $this->caso('L5', 'Relatório sem período nem safra é recusado com mensagem', fn () => $this->l5());
    }

    /** Garante no recorte (safra ZZTESTE Milho) os três sentidos e uma cancelada. */
    private function recorte(): void
    {
        $m = $this->m();
        $this->safra = $m->safra['milho'];
        [$a, $b] = [$m->silo('L silo A'), $m->silo('L silo B')];
        $m->estoque($a, 'milho', 300000);
        $ct = $m->contrato('milho', 'VENDA', null);
        $planos = [
            ['ENTRADA', [['PLANTIO', $m->plantio['milho'][0]]], [['UNIDADE', $a]], 40000],
            ['ENTRADA', [['PLANTIO', $m->plantio['milho'][0]]], [['UNIDADE', $a]], 38000],
            ['SAIDA', [['UNIDADE', $a]], [['CONTRATO', $ct]], 42000],
            ['TRANSFERENCIA', [['UNIDADE', $a]], [['UNIDADE', $b]], 30000],
        ];
        $ultima = null;
        foreach ($planos as [$sentido, $o, $d, $pbt]) {
            $c = $this->p->nova($sentido, 'milho', $o, $d);
            if (!$this->p->percorrer($c, $pbt, 15000, ['leituras' => $sentido === 'ENTRADA' ? $m->leituras('milho', ['Umidade' => 16]) : []])->ok()) {
                throw new \RuntimeException("preparo {$sentido} recusado");
            }
            $ultima = $c;
        }
        $ultima['inativo'] = date('Y-m-d H:i:s');
        $this->p->enviar($ultima); // a transferência vira a cancelada do recorte
        $this->r->ok('L0', 'Recorte com os três tipos e uma cancelada', 'safra ' . $this->safra);
    }

    /** O que a tela tem que mostrar, direto do banco. */
    private function esperado(): array
    {
        $porSentido = [];
        foreach (DB::table('tblcarga')->where('codsafra', $this->safra)->whereNull('inativo')
            ->selectRaw('sentido, count(*) qtd, coalesce(sum(bruto),0) bruto, coalesce(sum(desconto),0) desconto, coalesce(sum(liquido),0) liquido')
            ->groupBy('sentido')->get() as $l) {
            $porSentido[$l->sentido] = ['qtd' => (int) $l->qtd, 'bruto' => (float) $l->bruto, 'desconto' => (float) $l->desconto, 'liquido' => (float) $l->liquido];
        }
        $canceladas = DB::table('tblcarga')->where('codsafra', $this->safra)->whereNotNull('inativo')->count();
        return [$porSentido, $canceladas];
    }

    private function totais(array $filtro): array
    {
        $resp = $this->api()->get('v1/carga/listagem', $filtro + ['codsafra' => $this->safra, 'per_page' => 1]);
        if (!$resp->ok()) {
            throw new \RuntimeException('listagem recusada: ' . $resp->resumo());
        }
        return [$resp->json['totais'] ?? [], $resp->json['meta']['total'] ?? null];
    }

    private function l1(): void
    {
        [$esp] = $this->esperado();
        [$tot] = $this->totais(['inativo' => 9]);
        $dif = [];
        foreach ($esp as $sentido => $v) {
            if (!isset($tot[$sentido])) {
                $dif[] = "sem o total de {$sentido}";
                continue;
            }
            if (abs((float) ($tot[$sentido]['liquido'] ?? -1) - $v['liquido']) > 0.0005) {
                $dif[] = "{$sentido} " . $this->kg($tot[$sentido]['liquido'] ?? null) . ' (banco: ' . $this->kg($v['liquido']) . ')';
            }
        }
        if ($dif && isset($tot['liquido'])) {
            $dif[] = 'hoje é um total só: ' . $this->kg($tot['liquido']) . ' kg somando recebido, expedido e transferido';
        }
        $this->resultado('L1', 'Totais da tela separados por tipo', $dif);
    }

    private function l2(): void
    {
        [$esp, $canceladas] = $this->esperado();
        [$tot] = $this->totais(['inativo' => 9]);
        $ativas = array_sum(array_column($esp, 'liquido'));
        $dif = [];
        if (isset($tot['liquido']) && abs((float) $tot['liquido'] - $ativas) > 0.0005) {
            $dif[] = 'o total ' . $this->kg($tot['liquido']) . ' inclui canceladas (ativas: ' . $this->kg($ativas) . ')';
        }
        if ((int) ($tot['canceladas'] ?? -1) !== $canceladas) {
            $dif[] = 'canceladas contadas à parte: ' . ($tot['canceladas'] ?? 'não vem') . " (banco: {$canceladas})";
        }
        $this->resultado('L2', 'Canceladas fora dos totais', $dif);
    }

    /** Linhas do relatório: cargas, subtotais e total geral (Líquido kg). */
    private function relatorio(array $filtro): ?array
    {
        $resp = $this->api()->get('v1/carga/relatorio', $filtro + ['html' => 1]);
        if (!$resp->ok()) {
            return null;
        }
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $resp->corpo);
        $xp = new \DOMXPath($dom);
        $num = fn (string $s) => (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^\d.,-]/', '', $s));
        $out = ['cargas' => [], 'subtotais' => [], 'total' => null];
        foreach ($xp->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' cargas ')]/tbody/tr") as $tr) {
            $classe = trim((string) $tr->getAttribute('class'));
            $tds = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                $tds[] = trim($td->textContent);
            }
            if ($classe === '' || $classe === 'cancelada') {
                preg_match('/#(\d+)/', $tds[0] ?? '', $mm);
                $out['cargas'][] = ['cod' => (int) ($mm[1] ?? 0), 'liquido' => $num($tds[9] ?? '0')];
            } elseif ($classe === 'subtotal') {
                $out['subtotais'][] = $num($tds[3] ?? '0');
            } elseif ($classe === 'totalgeral') {
                $out['total'] = $num($tds[3] ?? '0');
            }
        }
        return $out;
    }

    private function l3(): void
    {
        $dif = [];
        foreach (static::AGRUPAMENTOS as $agrupar) {
            $rel = $this->relatorio(['codsafra' => $this->safra, 'inativo' => 1, 'agrupar' => $agrupar]);
            if ($rel === null) {
                $dif[] = "{$agrupar}: recusado";
                continue;
            }
            $cods = array_column($rel['cargas'], 'cod');
            if (count($cods) !== count(array_unique($cods))) {
                $dif[] = "{$agrupar}: carga em mais de um grupo";
            }
            if ($agrupar !== 'nenhum' && abs(array_sum($rel['subtotais']) - (float) $rel['total']) > count($rel['subtotais'])) {
                $dif[] = "{$agrupar}: subtotais " . $this->kg(array_sum($rel['subtotais'])) . ' ≠ total ' . $this->kg($rel['total']);
            }
        }
        $this->resultado('L3', 'Relatório: subtotais somam o total, carga em um grupo só', $dif, count(static::AGRUPAMENTOS) . ' agrupamentos');
    }

    private function l4(): void
    {
        [, $total] = $this->totais(['inativo' => 1]);
        $rel = $this->relatorio(['codsafra' => $this->safra, 'inativo' => 1, 'agrupar' => 'nenhum']);
        $linhas = $rel ? count($rel['cargas']) : -1;
        $this->r->checar('L4', 'Mesmo filtro: total da tela = linhas do relatório', (int) $total === $linhas,
            "tela {$total}, relatório {$linhas}");
    }

    private function l5(): void
    {
        $resp = $this->api()->get('v1/carga/relatorio', ['html' => 1, 'inativo' => 1]);
        $this->r->checar('L5', 'Relatório sem período nem safra é recusado com mensagem', $resp->status === 422 && $resp->mensagem() !== '',
            'veio ' . $resp->resumo());
    }
}
