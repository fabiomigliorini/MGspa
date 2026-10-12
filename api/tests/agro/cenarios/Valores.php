<?php

namespace AgroBateria\Cenarios;

use AgroBateria\Cenario;
use AgroBateria\Patio;
use Illuminate\Support\Facades\DB;

/**
 * V — dinheiro do contrato. A referência do líquido por saca é a conta da lei e
 * da planilha, montada aqui a partir das tabelas (tblculturatributo e UPF):
 *   base UNIDADE = %/100 × UPF × pesosaca/1000 · base VALOR = %/100 × bruto
 *   FETHAB/IAGRO usam a UPF de jan/jul do ano anterior (Lei 13.002/2025;
 *   2025 inteiro = jan/2025); FUNRURAL só entra se a filial paga na venda.
 * Itens com 4 casas, o total da fixação com 2.
 */
final class Valores extends Cenario
{
    private const DATA = '2026-09-15';

    public function rodar(): void
    {
        $this->r->camada('V — Valores do contrato');
        $this->caso('V1', 'Fixação em R$: líquido pela tabela de tributos', fn () => $this->v1());
        $this->caso('V2', 'Fixação com a isenção de FETHAB declarada', fn () => $this->v2());
        $this->caso('V2b', 'Fixação sem tributos informados guarda os da época', fn () => $this->v2b());
        $this->r->info('V3', 'Card da fixação mostra o líquido gravado', 'conferir na tela do contrato (Fase 7): o card recalcula no navegador');
        $this->caso('V4', 'Fixação em US$ com câmbio travado em parte', fn () => $this->v4());
        $this->caso('V5', 'Edição abaixo do que já foi travado é recusada', fn () => $this->v5());
        $this->r->info('V6', 'Conferência do recebimento compara líquido com líquido', 'conferir na tela do contrato (Fase 7): a conta é do navegador');
        $this->caso('V7', 'Plano de emissão da NF do contrato', fn () => $this->v7());
        $this->r->info('V8', 'Mês da UPF do FETHAB', 'jan/jul do ano anterior (Lei 13.002/2025, ContratoCalculoService::competenciaFethab) — confirmar com a contabilidade');
    }

    // ------------------------------------------------------- referência

    /** R$/saca líquido e itens, pela tabela da cultura na data da fixação. */
    private function liquidoSaca(float $bruto, bool $isentoFethab = false): array
    {
        $cultura = $this->m()->cultura['soja'];
        $pesosaca = 60.0;
        $itens = [];
        foreach (DB::table('tblculturatributo')->where('codcultura', $cultura)->whereNull('inativo')->orderBy('ordem')->get() as $t) {
            if ($t->grupofethab && $isentoFethab) {
                continue;
            }
            if ($t->funrural) {
                continue; // os contratos de teste não têm filial que pague FUNRURAL na venda
            }
            if ($t->base === 'UNIDADE') {
                $upf = $this->upf((int) $t->codunidadereferencia, (bool) $t->grupofethab);
                $valor = round($t->percentual / 100 * $upf * $pesosaca / 1000, 4);
            } else {
                $valor = round($t->percentual / 100 * $bruto, 4);
            }
            $itens[$t->codtributo] = $valor;
        }
        return ['liquido' => round($bruto - array_sum($itens), 4), 'itens' => $itens];
    }

    private function upf(int $codref, bool $fethab): float
    {
        $d = strtotime(static::DATA);
        $ano = (int) date('Y', $d);
        $competencia = $fethab
            ? ($ano <= 2025 ? '2025-01-01' : ($ano - 1) . (date('n', $d) <= 6 ? '-01-01' : '-07-01'))
            : date('Y-m-01', $d);
        return (float) DB::table('tblunidadereferenciavalor')->where('codunidadereferencia', $codref)
            ->where('competencia', '<=', $competencia)->orderByDesc('competencia')->value('valor');
    }

    // ------------------------------------------------------------ casos

    private function fixar(int $ct, array $campos): array
    {
        $resp = $this->api()->post("v1/contrato/{$ct}/fixacao", array_replace([
            'data' => static::DATA, 'quantidade' => 500, 'preco' => 120, 'codmoeda' => 1,
        ], $campos));
        if (!$resp->ok()) {
            throw new \RuntimeException('fixação recusada: ' . $resp->resumo());
        }
        $cod = (int) $resp->dado()['codcontratofixacao'];
        return [$cod, DB::table('tblcontratofixacao')->where('codcontratofixacao', $cod)->first()];
    }

    private function v1(): void
    {
        $ct = $this->m()->contrato('soja', 'VENDA', 1000);
        [, $f] = $this->fixar($ct, []);
        $ref = $this->liquidoSaca(120);
        $esperado = round($ref['liquido'] * 500, 2);
        $dif = [];
        if (abs((float) $f->totalbrl - 60000) > 0.004) {
            $dif[] = 'total ' . number_format((float) $f->totalbrl, 2, ',', '.') . ' (regra: 60.000,00)';
        }
        if (abs((float) $f->liquidobrl - $esperado) > 0.004) {
            $dif[] = 'líquido ' . number_format((float) $f->liquidobrl, 2, ',', '.') . ' (regra: ' . number_format($esperado, 2, ',', '.') . ')';
        }
        $this->resultado('V1', 'Fixação em R$: líquido pela tabela de tributos', $dif,
            '500 sc a R$ 120,00: líquido R$ ' . number_format($esperado, 2, ',', '.') . ' (R$ ' . number_format($ref['liquido'], 4, ',', '.') . '/sc)');
    }

    private function v2(): void
    {
        $ct = $this->m()->contrato('soja', 'VENDA', 1000);
        $linhas = [];
        foreach (DB::table('tblculturatributo as ct')->join('tbltributo as t', 't.codtributo', '=', 'ct.codtributo')
            ->where('ct.codcultura', $this->m()->cultura['soja'])->whereNull('ct.inativo')->orderBy('ct.ordem')
            ->get(['ct.*', 't.codigo', 't.descricao']) as $t) {
            if ($t->funrural) {
                continue;
            }
            // Isenção = linha do grupo FETHAB com alíquota zerada (é o que o modal manda).
            $linhas[] = [
                'codtributo' => $t->codtributo, 'codigo' => $t->codigo, 'descricao' => $t->descricao,
                'base' => $t->base, 'percentual' => $t->grupofethab ? 0 : (float) $t->percentual,
                'upf' => $t->base === 'UNIDADE' ? $this->upf((int) $t->codunidadereferencia, (bool) $t->grupofethab) : null,
                'grupofethab' => (bool) $t->grupofethab,
            ];
        }
        [, $f] = $this->fixar($ct, ['tributos' => $linhas]);
        $esperado = round($this->liquidoSaca(120, true)['liquido'] * 500, 2);
        $this->r->checar('V2', 'Fixação com a isenção de FETHAB declarada', abs((float) $f->liquidobrl - $esperado) < 0.005,
            'líquido R$ ' . number_format((float) $f->liquidobrl, 2, ',', '.') . ' (sem FETHAB: R$ ' . number_format($esperado, 2, ',', '.') . ')');
    }

    private function v2b(): void
    {
        $ct = $this->m()->contrato('soja', 'VENDA', 1000);
        [, $f] = $this->fixar($ct, []);
        $tributos = json_decode((string) $f->tributos, true);
        $this->r->checar('V2b', 'Fixação sem tributos informados guarda os da época', is_array($tributos) && count($tributos) > 0,
            is_array($tributos) && $tributos ? count($tributos) . ' linhas guardadas'
                : 'nada guardado: o líquido segue a tabela AO VIVO, e mexer em tributo muda fixação antiga');
    }

    private function v4(): void
    {
        $ct = $this->m()->contrato('soja', 'VENDA', 1000);
        [$cod] = $this->fixar($ct, ['quantidade' => 100, 'preco' => 20, 'codmoeda' => 2]);
        $trava = $this->api()->post("v1/contrato/{$ct}/fixacao/{$cod}/cambio", ['data' => static::DATA, 'valor' => 800, 'cotacao' => 5.5]);
        if (!$trava->ok()) {
            $this->r->erro('V4', 'Fixação em US$ com câmbio travado em parte', 'trava recusada: ' . $trava->resumo());
            return;
        }
        $f = DB::table('tblcontratofixacao')->where('codcontratofixacao', $cod)->first();
        $esperado = round($this->liquidoSaca(4400 / 40)['liquido'] * 40, 2);
        $dif = [];
        foreach (['totalmoeda' => 2000, 'saldomoeda' => 1200, 'totalbrl' => 4400, 'liquidobrl' => $esperado] as $campo => $v) {
            if (abs((float) $f->{$campo} - $v) > 0.004) {
                $dif[] = "{$campo} " . number_format((float) $f->{$campo}, 2, ',', '.') . ' (regra: ' . number_format($v, 2, ',', '.') . ')';
            }
        }
        $this->resultado('V4', 'Fixação em US$ com câmbio travado em parte', $dif, 'US$ 800 de 2.000 travados a 5,50 = 40 sc firmes');
    }

    private function v5(): void
    {
        $ct = $this->m()->contrato('soja', 'VENDA', 1000);
        [$cod] = $this->fixar($ct, ['quantidade' => 100, 'preco' => 20, 'codmoeda' => 2]);
        $this->api()->post("v1/contrato/{$ct}/fixacao/{$cod}/cambio", ['data' => static::DATA, 'valor' => 800, 'cotacao' => 5.5]);
        $base = ['data' => static::DATA, 'quantidade' => 100, 'preco' => 20, 'codmoeda' => 2];
        $menos = $this->api()->put("v1/contrato/{$ct}/fixacao/{$cod}", array_replace($base, ['quantidade' => 30]));
        $moeda = $this->api()->put("v1/contrato/{$ct}/fixacao/{$cod}", array_replace($base, ['codmoeda' => 1]));
        $dif = [];
        if ($menos->status !== 422) {
            $dif[] = '30 sc com 40 já travadas: ' . $menos->resumo();
        }
        if ($moeda->status !== 422) {
            $dif[] = 'trocar US$ por R$ com câmbio travado: ' . $moeda->resumo();
        }
        $this->resultado('V5', 'Edição abaixo do que já foi travado é recusada', $dif);
    }

    private function v7(): void
    {
        $m = $this->m();
        $silo = $m->silo('V7 silo');
        $m->estoque($silo, 'soja', 100000);
        $ct = $m->contrato('soja', 'VENDA', 1000);
        $this->fixar($ct, []);
        DB::table('tblcontratonota')->insert(['codcontrato' => $ct, 'ordem' => 1]);
        $c = $this->p->nova('SAIDA', 'soja', [['UNIDADE', $silo]], [['CONTRATO', $ct]]);
        if (!$this->p->percorrer($c, 45000, 15000)->ok()) {
            $this->r->erro('V7', 'Plano de emissão da NF do contrato', 'expedição recusada');
            return;
        }
        $cod = Patio::banco($c['uuid'])->codcarga;
        $resp = $this->api()->get("v1/contrato/{$ct}/carga/{$cod}/emissao");
        $this->r->checar('V7', 'Plano de emissão da NF do contrato', $resp->ok(), 'veio ' . $resp->resumo());
    }
}
