<?php

namespace AgroBateria\Cenarios;

use AgroBateria\Ambiente;
use AgroBateria\Cenario;
use AgroBateria\Massa;
use AgroBateria\Referencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mg\Grao\Carga;
use Mg\Grao\CargaService;

/**
 * D — descontos de classificação. Cada vetor passa pelo CargaService::calcular
 * DE VERDADE, numa transação desfeita no fim (nada fica gravado), e é comparado
 * com a Referencia: em gramas (a fórmula de hoje, exata) e em kg inteiro (a
 * regra decidida). Uma amostra também vai pela API, para provar que o caminho
 * HTTP dá o mesmo número. Os vetores vão para vetores.json, que o
 * desconto-front.mjs roda no desconto.js/ticket.js do pátio (paridade).
 */
final class Desconto extends Cenario
{
    private const NOMEADOS = [
        // id => [título, pbt, tara, leituras por nome, ajustes na tabela]
        'D1' => ['leituras exatamente na tolerância', 45000, 15000, ['Impureza' => 1, 'Umidade' => 14, 'Avariados' => 8, 'Esverdeados' => 8, 'Quebrados' => 30]],
        'D2' => ['0,1 acima de todas as tolerâncias', 45000, 15000, ['Impureza' => 1.1, 'Umidade' => 14.1, 'Avariados' => 8.1, 'Esverdeados' => 8.1, 'Quebrados' => 30.1]],
        'D3' => ['caso típico 2/18/10/5/20', 45000, 15000, ['Impureza' => 2, 'Umidade' => 18, 'Avariados' => 10, 'Esverdeados' => 5, 'Quebrados' => 20]],
        'D4' => ['todas as leituras zero', 45000, 15000, ['Impureza' => 0, 'Umidade' => 0, 'Avariados' => 0, 'Esverdeados' => 0, 'Quebrados' => 0]],
        'D5' => ['umidade 100 %', 45000, 15000, ['Umidade' => 100]],
        'D6' => ['sem nenhuma leitura', 45000, 15000, []],
        'D7' => ['avariados e esverdeados 100 % (desconto passa do bruto)', 45000, 15000, ['Avariados' => 100, 'Esverdeados' => 100]],
        'D8' => ['meio quilo: impureza 1,5 em 9.999 kg', 19999, 10000, ['Impureza' => 1.5]],
        'D9' => ['umidade pelo método FATOR (1,5 por ponto)', 45000, 15000, ['Umidade' => 18], ['Umidade' => ['metodo' => 'FATOR', 'fator' => 1.5]]],
        'D10' => ['deságio de 10 % nos avariados', 45000, 15000, ['Impureza' => 2, 'Umidade' => 18, 'Avariados' => 10], ['Avariados' => ['desagio' => 10]]],
        'D11' => ['empate de ordem (impureza e avariados na ordem 1)', 45000, 15000, ['Impureza' => 3, 'Avariados' => 13], ['Avariados' => ['ordem' => 1]]],
        'D12' => ['caso de 1 g entre pátio e servidor', 55883, 14312, ['Impureza' => 0.2, 'Umidade' => 24.4, 'Avariados' => 17.5, 'Esverdeados' => 10, 'Quebrados' => 19]],
        'D13' => ['tolerância 99,9 com leitura 100', 45000, 15000, ['Quebrados' => 100], ['Quebrados' => ['tolerancia' => 99.9]]],
    ];

    private array $vetores = [];

    public function rodar(): void
    {
        $this->r->camada('D — Descontos de classificação');
        foreach (static::NOMEADOS as $id => $def) {
            $this->caso($id, $def[0], fn () => $this->nomeado($id, $def));
        }
        $this->caso('D-sort', 'Vetores sorteados', fn () => $this->sorteados((int) ($this->opc['vetores'] ?? 10000)));
        $this->caso('D-http', 'Amostra pela API = cálculo em processo', fn () => $this->amostraHttp());
        $this->caso('D-ordem', 'Cadastro com ordem repetida é recusado', fn () => $this->cadastro('D-ordem', 'ordem repetida', ['ordem' => 1]));
        $this->caso('D-tol100', 'Cadastro com tolerância 100 é recusado', fn () => $this->cadastro('D-tol100', 'tolerância 100', ['ordem' => 99, 'tolerancia' => 100]));

        $arquivo = Ambiente::arquivo('vetores.json');
        file_put_contents($arquivo, json_encode($this->vetores, JSON_UNESCAPED_UNICODE));
        $this->r->info('D-front', 'Paridade com o pátio', count($this->vetores) . ' vetores em ' . $arquivo
            . ' — rode no host: node api/tests/agro/desconto-front.mjs');
    }

    /** Tabela da massa com os ajustes do vetor (por nome de parâmetro). */
    private function tabela(array $ajustes): array
    {
        return array_map(fn ($p) => array_replace($p, $ajustes[$p['nome']] ?? []), $this->m()->parametros('soja'));
    }

    private function nomeado(string $id, array $def): void
    {
        [$titulo, $pbt, $tara, $porNome] = $def;
        $tab = $this->tabela($def[4] ?? []);
        $leit = $this->m()->leituras('soja', $porNome);
        $v = $this->vetor($id, $pbt, $tara, $leit, $tab);
        $srv = $v['servidor'];
        $kg = $v['regra_kg'];
        $formula = $this->mesmaFormula($srv, $v['regra_g']);
        $detalhe = 'servidor ' . $this->kg($srv['liquido']) . ' · regra ' . $this->kg($kg['liquido'])
            . ($formula ? '' : ' · em gramas a fórmula dá ' . $this->kg($v['regra_g']['liquido'] / 1000));

        if ($id === 'D7') {
            $this->r->info($id, $titulo, "desconto {$this->kg($srv['desconto'])} de um bruto de {$this->kg($srv['bruto'])}: líquido {$this->kg($srv['liquido'])}; a regra nova recusa ao finalizar (Fase 3)");
            return;
        }
        $igual = abs($srv['liquido'] - $kg['liquido']) < 0.0005 && abs($srv['desconto'] - $kg['desconto']) < 0.0005;
        $this->r->checar($id, $titulo, $igual && $formula, $detalhe);
    }

    private function mesmaFormula(array $srv, array $regraG): bool
    {
        foreach ($regraG['itens'] as $cod => $g) {
            if (abs(($srv['itens'][$cod] ?? 0) - $g / 1000) > 0.0015) {
                return false;
            }
        }
        return abs($srv['liquido'] - $regraG['liquido'] / 1000) <= 0.0015 * max(1, count($regraG['itens']));
    }

    /** Calcula no servidor (em processo, sem gravar) e nas duas referências. */
    private function vetor(string $id, int $pbt, int $tara, array $leit, array $tab): array
    {
        $v = [
            'id' => $id,
            'pbt' => $pbt,
            'tara' => $tara,
            'parametros' => $tab,
            'leituras' => array_map(fn ($cod, $l) => ['codparametroclassificacao' => $cod, 'leitura' => $l], array_keys($leit), $leit),
            'servidor' => $this->servidor($pbt, $tara, $leit, $tab),
            'regra_kg' => Referencia::desconto($pbt, $tara, $leit, $tab),
            'regra_g' => Referencia::desconto($pbt, $tara, $leit, $tab, 1000),
        ];
        $this->vetores[] = $v;
        return $v;
    }

    private function servidor(int $pbt, int $tara, array $leit, array $tab): array
    {
        DB::beginTransaction();
        try {
            foreach ($tab as $p) {
                DB::table('tblparametroclassificacao')->where('codparametroclassificacao', $p['cod'])->update([
                    'metodo' => $p['metodo'], 'reduzbase' => $p['reduzbase'], 'ordem' => $p['ordem'],
                    'tolerancia' => $p['tolerancia'], 'fator' => $p['fator'], 'desagio' => $p['desagio'],
                ]);
            }
            $cod = DB::table('tblcarga')->insertGetId([
                'uuid' => (string) Str::uuid(), 'codsafra' => $this->m()->safra['soja'], 'sentido' => 'ENTRADA',
                'etapa' => 'CLASSIFICACAO', 'data' => Massa::DIA, 'pbt' => $pbt, 'tara' => $tara,
            ], 'codcarga');
            foreach ($leit as $codparam => $l) {
                DB::table('tblcargaclassificacao')->insert(['codcarga' => $cod, 'codparametroclassificacao' => $codparam, 'leitura' => $l]);
            }
            $carga = Carga::find($cod);
            CargaService::calcular($carga);
            $itens = [];
            foreach ($carga->CargaClassificacaoS as $cc) {
                $itens[(int) $cc->codparametroclassificacao] = (float) $cc->desconto;
            }
            return ['bruto' => (float) $carga->bruto, 'desconto' => (float) $carga->desconto, 'liquido' => (float) $carga->liquido, 'itens' => $itens];
        } finally {
            DB::rollBack();
        }
    }

    private function sorteados(int $n): void
    {
        mt_srand(28092026);
        $tab = $this->m()->parametros('soja');
        $formula = 0;
        $difKg = [];
        for ($i = 0; $i < $n; $i++) {
            $pbt = mt_rand(30000, 75000);
            $tara = mt_rand(12000, 18000);
            $leit = [];
            foreach ($tab as $p) {
                if (mt_rand(1, 10) <= 7) {
                    $leit[$p['cod']] = max(0, round($p['tolerancia'] + (mt_rand(-20, 60) / 10), 1));
                }
            }
            $v = $this->vetor('S' . ($i + 1), $pbt, $tara, $leit, $tab);
            if ($this->mesmaFormula($v['servidor'], $v['regra_g'])) {
                $formula++;
            }
            $d = abs($v['servidor']['liquido'] - $v['regra_kg']['liquido']);
            if ($d > 0.0005) {
                $difKg[] = $d;
            }
        }
        $this->r->checar('D-form', "Fórmula do servidor = norma em {$n} vetores sorteados", $formula === $n,
            "{$formula} de {$n} conferem em gramas");
        $media = $difKg ? array_sum($difKg) / count($difKg) : 0;
        $this->r->checar('D-kg', "Líquido em kg inteiro em {$n} vetores sorteados", !$difKg,
            count($difKg) . " de {$n} diferem da regra de kg inteiro (média " . number_format($media, 3, ',', '.')
            . ' kg, máx ' . number_format($difKg ? max($difKg) : 0, 3, ',', '.') . ' kg)');
    }

    private function amostraHttp(): void
    {
        $m = $this->m();
        $silo = $m->silo('D silo');
        $amostra = array_slice(array_filter($this->vetores, fn ($v) => str_starts_with($v['id'], 'S')), 0, 20);
        $dif = 0;
        foreach ($amostra as $v) {
            $c = $this->p->nova('ENTRADA', 'soja', [['PLANTIO', $m->plantio['soja'][0]]], [['UNIDADE', $silo]], [
                'etapa' => 'TARA', 'pbt' => $v['pbt'], 'tara' => $v['tara'], 'classificacao' => $v['leituras'],
            ]);
            $resp = $this->p->enviar($c);
            $d = $resp->dado();
            if (!$resp->ok() || abs((float) ($d['liquido'] ?? -1) - $v['servidor']['liquido']) > 0.0005) {
                $dif++;
            }
        }
        $this->r->checar('D-http', 'Amostra pela API = cálculo em processo', $dif === 0, count($amostra) . " romaneios pela API, {$dif} diferente(s)");
    }

    private function cadastro(string $id, string $o_que, array $campos): void
    {
        $resp = $this->api()->post('v1/parametro-classificacao', array_replace([
            'codcultura' => $this->m()->cultura['soja'], 'parametroclassificacao' => Massa::PREFIXO . " {$o_que}",
            'metodo' => 'NORMALIZADO', 'reduzbase' => false, 'ordem' => 1, 'tolerancia' => 5, 'fator' => 0, 'desagio' => 0,
        ], $campos));
        if ($resp->ok() && ($cod = $resp->dado()['codparametroclassificacao'] ?? null)) {
            $this->api()->delete("v1/parametro-classificacao/{$cod}");
        }
        $this->r->checar($id, "Cadastro com {$o_que} é recusado", $resp->status === 422, 'veio ' . $resp->resumo());
    }
}
