<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mg\Produto\Produto;
use Mg\Estoque\EstoqueLocalProdutoVariacao;
use Mg\Estoque\EstoqueSaldo;
use Mg\Estoque\EstoqueMovimento;
use Mg\Estoque\EstoqueMovimentoTipo;
use Mg\Estoque\EstoqueMesService;
use Mg\Estoque\EstoqueSaldoService;

class EstoqueBaixarEmbalagens extends Command
{
    protected $signature = 'estoque:baixar-embalagens {codproduto} {--dry-run} {--somente-fiscal} {--somente-fisico} {--codestoquelocal=}';
    protected $description = 'Gera ajustes de saída para compensar estoque acumulado de embalagens nas lojas';

    const LOJAS = [102001, 103001, 104001, 105001];

    public function handle()
    {
        $codproduto = $this->argument('codproduto');
        $dryRun = $this->option('dry-run');
        $somentefiscal = $this->option('somente-fiscal');
        $somentefisico = $this->option('somente-fisico');
        $filtroLocal = $this->option('codestoquelocal');

        $produto = Produto::findOrFail($codproduto);
        $this->info("Produto: #{$produto->codproduto} - {$produto->produto}");

        if ($dryRun) {
            $this->warn('=== DRY RUN - nenhum movimento será criado ===');
        }

        // Esferas a processar
        $esferas = [false, true];
        if ($somentefiscal) {
            $esferas = [true];
        } elseif ($somentefisico) {
            $esferas = [false];
        }

        // Locais a processar
        $locais = self::LOJAS;
        if ($filtroLocal) {
            $locais = [(int) $filtroLocal];
        }

        $totalAjustes = 0;

        // Variações em ordem alfabética
        $variacoes = $produto->ProdutoVariacaoS->sortBy('variacao');

        foreach ($variacoes as $variacao) {
            foreach ($locais as $codestoquelocal) {
                foreach ($esferas as $fiscal) {
                    $ajustes = $this->processarSaldo($variacao, $codestoquelocal, $fiscal, $dryRun);
                    $totalAjustes += $ajustes;
                }
            }
        }

        $this->info("Total de ajustes: {$totalAjustes}");
    }

    private function processarSaldo($variacao, $codestoquelocal, $fiscal, $dryRun)
    {
        // Buscar EstoqueLocalProdutoVariacao
        $elpv = EstoqueLocalProdutoVariacao::where('codestoquelocal', $codestoquelocal)
            ->where('codprodutovariacao', $variacao->codprodutovariacao)
            ->first();

        if (!$elpv) {
            return 0;
        }

        // Buscar EstoqueSaldo
        $saldo = EstoqueSaldo::where('codestoquelocalprodutovariacao', $elpv->codestoquelocalprodutovariacao)
            ->where('fiscal', $fiscal)
            ->first();

        if (!$saldo) {
            return 0;
        }

        // Buscar todos os movimentos ordenados cronologicamente
        $codestoquemesIds = $saldo->EstoqueMesS->pluck('codestoquemes');
        if ($codestoquemesIds->isEmpty()) {
            return 0;
        }

        $movimentos = EstoqueMovimento::whereIn('codestoquemes', $codestoquemesIds)
            ->orderBy('data', 'asc')
            ->orderBy('codestoquemovimento', 'asc')
            ->get();

        if ($movimentos->isEmpty()) {
            return 0;
        }

        // Identificar entradas
        $entradas = [];
        foreach ($movimentos as $idx => $mov) {
            if ($mov->entradaquantidade > 0) {
                $entradas[] = [
                    'idx' => $idx,
                    'mov' => $mov,
                ];
            }
        }

        if (count($entradas) < 1) {
            return 0;
        }

        // Com apenas 1 entrada, só processa se for antiga (> 2 anos)
        $ultimaEntrada = $entradas[count($entradas) - 1]['mov'];
        $limiteAntiga = now()->subYears(2);
        if (count($entradas) < 2 && !$ultimaEntrada->data->lt($limiteAntiga)) {
            return 0;
        }

        // Montar running balance em cada ponto
        $balances = [];
        $running = 0;
        foreach ($movimentos as $mov) {
            $running += ($mov->entradaquantidade ?? 0) - ($mov->saidaquantidade ?? 0);
            $balances[] = $running;
        }

        $esfera = $fiscal ? 'Fiscal' : 'Físico';
        $ajustesCount = 0;
        $mesesRecalcular = [];

        // Se última entrada é mais antiga que 2 anos, inclui ela também
        $incluirUltima = $ultimaEntrada->data->lt($limiteAntiga);
        $limite = $incluirUltima ? count($entradas) : count($entradas) - 1;

        // Processar cada entrada (exceto última, ou todas se última > 2 anos)
        for ($i = 0; $i < $limite; $i++) {
            $entrada = $entradas[$i];
            $entradaMov = $entrada['mov'];
            $entradaIdx = $entrada['idx'];
            $entradaQty = $entradaMov->entradaquantidade;

            // Determinar fim da janela
            $isUltimaProcessada = ($i == $limite - 1);
            if ($isUltimaProcessada) {
                // Janela vai até o fim
                $fimIdx = count($movimentos) - 1;
            } else {
                // Janela vai até a próxima entrada (exclusive)
                $fimIdx = $entradas[$i + 1]['idx'] - 1;
            }

            // Somar saídas na janela (excluindo a própria entrada)
            $exitsInWindow = 0;
            for ($j = $entradaIdx + 1; $j <= $fimIdx; $j++) {
                $exitsInWindow += ($movimentos[$j]->saidaquantidade ?? 0);
            }

            // Restrição de janela
            $adjJanela = max(0, $entradaQty - $exitsInWindow);

            // Restrição de saldo não-negativo
            // Menor saldo futuro a partir do ponto de inserção do ajuste
            $minSaldoFuturo = PHP_INT_MAX;
            for ($j = $entradaIdx; $j < count($balances); $j++) {
                if ($balances[$j] < $minSaldoFuturo) {
                    $minSaldoFuturo = $balances[$j];
                }
            }

            // Ajuste final
            $adjQty = min($adjJanela, $minSaldoFuturo);

            if ($adjQty < 0.001) {
                continue;
            }

            // Custo unitário da entrada
            $custoUnitario = 0;
            if ($entradaMov->entradaquantidade > 0 && $entradaMov->entradavalor > 0) {
                $custoUnitario = $entradaMov->entradavalor / $entradaMov->entradaquantidade;
            }

            $adjValor = round($custoUnitario * $adjQty, 2);
            $dataAjuste = $entradaMov->data->copy()->addMinute();

            $this->line(
                "  Var:{$variacao->variacao} Local:{$codestoquelocal} {$esfera}"
                . " | Entrada:{$entradaQty} em {$entradaMov->data->format('d/m/Y')}"
                . " | Ajuste saída:{$adjQty} (R$ {$adjValor})"
            );

            if (!$dryRun) {
                // Buscar/criar EstoqueMes
                $mes = EstoqueMesService::buscaOuCria(
                    $codestoquelocal,
                    $variacao->codprodutovariacao,
                    $fiscal,
                    $dataAjuste
                );

                // Criar movimento de ajuste
                $mov = new EstoqueMovimento();
                $mov->codestoquemovimentotipo = EstoqueMovimentoTipo::AJUSTE;
                $mov->codestoquemes = $mes->codestoquemes;
                $mov->saidaquantidade = $adjQty;
                $mov->saidavalor = $adjValor;
                $mov->entradaquantidade = null;
                $mov->entradavalor = null;
                $mov->data = $dataAjuste;
                $mov->manual = true;
                $mov->observacoes = "Ajuste automático baixa embalagem - codproduto {$variacao->codproduto}";
                $mov->save();

                $mesesRecalcular[$mes->codestoquemes] = $mes;
            }

            // Atualizar running balance para cálculos seguintes
            for ($j = $entradaIdx; $j < count($balances); $j++) {
                $balances[$j] -= $adjQty;
            }

            $ajustesCount++;
        }

        // Recalcular custo médio dos meses afetados
        if (!$dryRun && !empty($mesesRecalcular)) {
            // Ordenar por mês cronologicamente
            uasort($mesesRecalcular, fn($a, $b) => $a->mes <=> $b->mes);
            foreach ($mesesRecalcular as $codestoquemes => $mes) {
                EstoqueSaldoService::estoqueCalculaCustoMedio($codestoquemes);
            }
        }

        return $ajustesCount;
    }
}
