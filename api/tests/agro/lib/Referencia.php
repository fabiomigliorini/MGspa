<?php

namespace AgroBateria;

/**
 * As regras DECIDIDAS, escritas a partir da decisão e da norma — não copiadas do
 * CargaService. É contra isto que a bateria compara o servidor e o pátio.
 *
 * Desconto (IN MAPA 11/2007 soja, 60/2011 milho): cascata pela `ordem`.
 *   NORMALIZADO = (leitura − tol)/(100 − tol) × (100 − deságio)/100
 *   FATOR       = (leitura − tol) × fator/100
 *   Abaixo da tolerância, zero. Parâmetro com reduzbase tira o seu desconto da
 *   base dos seguintes (impureza, umidade); os demais usam a mesma base.
 *
 * Decisão de 28/09 — kg INTEIRO: cada item arredonda meio-para-cima sobre a
 * fração EXATA (leitura/tolerância/fator/deságio em milésimos, com bcmath), sem
 * ponto flutuante — PHP e JS chegam ao mesmo número. A base segue inteira.
 *
 * Rateio: kg inteiro pelos pesos do cliente; a sobra vai para o último ponto.
 * Movimento: silo como ORIGEM baixa o BRUTO, sem desconto na linha; os demais
 * pontos movimentam o líquido (silo destino +, talhão e contrato sempre +).
 */
final class Referencia
{
    // ------------------------------------------------------------ desconto

    /**
     * @param array $leituras   [codparametroclassificacao => leitura (%)]
     * @param array $parametros [['cod','metodo','reduzbase','ordem','tolerancia','fator','desagio'], ...]
     * @param int   $escala     1 = kg (a regra decidida); 1000 = gramas (a regra de hoje, exata)
     * @return array{bruto:int, itens:array<int,int>, desconto:int, liquido:int} na unidade da escala
     */
    public static function desconto(int|float|string $pbt, int|float|string $tara, array $leituras, array $parametros, int $escala = 1): array
    {
        $bruto = static::unidades($pbt, $escala) - static::unidades($tara, $escala);
        $base = (string) $bruto;
        $total = '0';
        $itens = [];
        foreach (static::emOrdem($parametros) as $p) {
            $cod = (int) $p['cod'];
            $l = $leituras[$cod] ?? null;
            if ($l === null || $l === '') {
                continue; // sem leitura: desconto zero e a base não muda
            }
            $d = static::item($base, $p, $l);
            $itens[$cod] = (int) $d;
            $total = bcadd($total, $d);
            if (!empty($p['reduzbase'])) {
                $base = bcsub($base, $d);
            }
        }
        return ['bruto' => $bruto, 'itens' => $itens, 'desconto' => (int) $total, 'liquido' => $bruto - (int) $total];
    }

    /** Ordem da cascata; empate de ordem desempata pelo código (a regra nova proíbe o empate). */
    public static function emOrdem(array $parametros): array
    {
        usort($parametros, fn ($a, $b) => [(int) $a['ordem'], (int) $a['cod']] <=> [(int) $b['ordem'], (int) $b['cod']]);
        return $parametros;
    }

    private static function item(string $base, array $p, int|float|string $leitura): string
    {
        $L = static::mil($leitura);
        $T = static::mil($p['tolerancia']);
        if (bccomp($L, $T) <= 0) {
            return '0';
        }
        $excesso = bcsub($L, $T);
        if (($p['metodo'] ?? 'NORMALIZADO') === 'FATOR') {
            // base × (L−T)/1000 × F/1000 / 100
            $num = bcmul(bcmul($base, $excesso), static::mil($p['fator']));
            $den = '100000000';
        } else {
            if (bccomp($T, '100000') >= 0) {
                return '0';
            }
            // base × (L−T)/(100000−T) × (100000−G)/100000
            $num = bcmul(bcmul($base, $excesso), bcsub('100000', static::mil($p['desagio'])));
            $den = bcmul(bcsub('100000', $T), '100000');
        }
        return static::divArred($num, $den);
    }

    /** num/den em inteiro, meio para longe do zero. */
    public static function divArred(string $num, string $den): string
    {
        $negativo = (bccomp($num, '0') < 0) !== (bccomp($den, '0') < 0);
        $n = ltrim($num, '-');
        $d = ltrim($den, '-');
        $q = bcdiv(bcadd(bcmul($n, '2'), $d), bcmul($d, '2'), 0);
        return ($negativo && $q !== '0') ? '-' . $q : $q;
    }

    /** Valor com até 3 casas em milésimos inteiros (24,4 -> 24400). */
    public static function mil(int|float|string|null $v): string
    {
        return (string) (int) round(((float) $v) * 1000);
    }

    public static function unidades(int|float|string|null $kg, int $escala): int
    {
        return (int) round(((float) $kg) * $escala);
    }

    // -------------------------------------------------------------- rateio

    /**
     * Divide $total (inteiro) proporcional aos $pesos; a sobra vai para o último.
     *
     * @return int[]
     */
    public static function rateio(int $total, array $pesos): array
    {
        $pesos = array_values($pesos);
        $n = count($pesos);
        $w = array_map(fn ($p) => static::mil($p), $pesos);
        $soma = array_reduce($w, fn ($s, $x) => bcadd($s, $x), '0');
        if ($n === 0 || bccomp($soma, '0') <= 0) {
            return array_fill(0, $n, 0);
        }
        $out = [];
        $acc = 0;
        foreach ($w as $i => $x) {
            if ($i < $n - 1) {
                $v = (int) static::divArred(bcmul((string) $total, $x), $soma);
                $out[] = $v;
                $acc += $v;
            } else {
                $out[] = $total - $acc;
            }
        }
        return $out;
    }

    // ----------------------------------------------------------- movimento

    /**
     * Linhas do extrato que uma carga FINALIZADA e ativa deve gerar.
     *
     * @param array $pontos [['papel','contatipo','conta','peso'], ...] na ordem de gravação
     * @return array [['papel','contatipo','conta','bruto','desconto','liquido'], ...]
     */
    public static function movimentos(int $bruto, int $liquido, array $pontos): array
    {
        $linhas = [];
        foreach (['ORIGEM', 'DESTINO'] as $papel) {
            $grupo = array_values(array_filter($pontos, fn ($p) => $p['papel'] === $papel));
            if (!$grupo) {
                continue;
            }
            $pesos = array_map(fn ($p) => $p['peso'], $grupo);
            $liq = static::rateio($liquido, $pesos);
            $bru = static::rateio($bruto, $pesos);
            foreach ($grupo as $i => $p) {
                $base = ['papel' => $papel, 'contatipo' => $p['contatipo'], 'conta' => (int) $p['conta']];
                if ($p['contatipo'] === 'UNIDADE' && $papel === 'ORIGEM') {
                    $linhas[] = $base + ['bruto' => -$bru[$i], 'desconto' => 0, 'liquido' => -$bru[$i]];
                } else {
                    $linhas[] = $base + ['bruto' => $bru[$i], 'desconto' => $bru[$i] - $liq[$i], 'liquido' => $liq[$i]];
                }
            }
        }
        return $linhas;
    }

    // ------------------------------------------- o pátio de hoje, em PHP

    /** Math.round do JS: o meio vai para +infinito. */
    public static function jsRound(float $v): float
    {
        return floor($v + 0.5);
    }

    /** arredondar() do agro/src/utils/desconto.js (3 casas, com o Number.EPSILON). */
    public static function jsArred(float $v, int $casas = 3): float
    {
        $f = 10 ** $casas;
        return floor(($v + PHP_FLOAT_EPSILON) * $f + 0.5) / $f;
    }

    /**
     * O cálculo que o PÁTIO faz hoje (desconto.js::calcularCarga), operação por
     * operação, em ponto flutuante: é o que o aparelho mostra e rateia antes de
     * enviar. Serve só para o aparelho virtual mandar o mesmo payload do app.
     */
    public static function descontoPatioAtual(array $carga, array $parametros): array
    {
        if (!isset($carga['pbt'], $carga['tara']) || $carga['pbt'] === '' || $carga['tara'] === '') {
            return ['bruto' => null, 'desconto' => null, 'liquido' => null];
        }
        $bruto = static::jsArred((float) $carga['pbt'] - (float) $carga['tara']);
        $leituras = [];
        foreach ($carga['classificacao'] ?? [] as $l) {
            if (($l['leitura'] ?? null) !== null && $l['leitura'] !== '') {
                $leituras[(int) $l['codparametroclassificacao']] = (float) $l['leitura'];
            }
        }
        $base = $bruto;
        $total = 0.0;
        foreach (static::emOrdem($parametros) as $p) {
            $cod = (int) $p['cod'];
            if (!array_key_exists($cod, $leituras)) {
                continue;
            }
            $desc = static::jsArred($base * static::percentualJs($p, $leituras[$cod]));
            $total += $desc;
            if (!empty($p['reduzbase'])) {
                $base = $base - $desc;
            }
        }
        $desconto = static::jsArred($total);
        return ['bruto' => $bruto, 'desconto' => $desconto, 'liquido' => static::jsArred($bruto - $desconto)];
    }

    /** percentualItem() do desconto.js, com a MESMA ordem das operações do JS. */
    private static function percentualJs(array $p, float $leitura): float
    {
        $tol = (float) $p['tolerancia'];
        $excesso = $leitura - $tol;
        if (!($excesso > 0)) {
            return 0.0;
        }
        if (($p['metodo'] ?? 'NORMALIZADO') === 'FATOR') {
            return ($excesso * (float) $p['fator']) / 100;
        }
        $den = 100 - $tol;
        if ($den <= 0) {
            return 0.0;
        }
        return ($excesso / $den) * ((100 - (float) $p['desagio']) / 100);
    }
}
