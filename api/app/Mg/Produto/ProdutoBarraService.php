<?php

namespace Mg\Produto;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProdutoBarraService
{
    /**
     * Regenera os movimentos de estoque da variação no MGLara (best-effort).
     * Quando uma barra muda de variação, as vendas/notas ligadas a ela
     * precisam ser recontabilizadas nas duas variações (origem e destino).
     * O MGLara é o dono da fila de jobs de estoque (mesmo padrão de
     * `unificaVariacoes`, que delega o custo médio ao MGLara).
     */
    public static function geraMovimentoVariacao($codprodutovariacao): void
    {
        $base = rtrim((string) env('MGLARA_URL'), '/');
        if ($base === '' || empty($codprodutovariacao)) {
            return;
        }
        $url = "{$base}/estoque/gera-movimento-produto-variacao/{$codprodutovariacao}";
        try {
            Http::timeout(10)->get($url);
        } catch (\Throwable $e) {
            Log::warning('Falha ao regenerar movimento de estoque da variação', [
                'codprodutovariacao' => $codprodutovariacao,
                'url' => $url,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cria ProdutoBarra. Quando 'barras' vem vazio, gera o código interno
     * (prefixo 234) a partir do próximo valor da sequence — replica o
     * save() do legado MGLara.
     */
    public static function criar(array $dados): ProdutoBarra
    {
        $pb = new ProdutoBarra();
        $pb->fill($dados);
        if (empty($pb->codprodutoembalagem)) {
            $pb->codprodutoembalagem = null;
        }
        if (empty($pb->barras)) {
            $next = DB::select("select nextval('tblprodutobarra_codprodutobarra_seq') as id");
            $pb->codprodutobarra = (int) $next[0]->id;
            $pb->barras = self::geraBarrasInterno($pb->codprodutobarra);
        }
        $pb->save();
        return $pb;
    }

    public static function geraBarrasInterno(int $codprodutobarra): string
    {
        $base = 234000000000 + $codprodutobarra;
        return $base . self::calculaDigitoGtin($base . '0');
    }

    public static function calculaDigitoGtin($barras): int
    {
        $codigo = substr('000000000000000000' . $barras, -18);
        $soma = 0;
        for ($i = 1; $i < strlen($codigo); $i++) {
            $digito = (int) substr($codigo, $i - 1, 1);
            $multiplicador = ($i % 2 === 0) ? 1 : 3;
            $soma += $digito * $multiplicador;
        }
        return (int) ((ceil($soma / 10) * 10) - $soma);
    }

    public static function unificaBarras ($codprodutobarraorigem, $codprodutobarradestino)
    {

        $pb_origem = ProdutoBarra::findOrFail($codprodutobarraorigem);
        $pb_destino = ProdutoBarra::findOrFail($codprodutobarradestino);

        if ($pb_origem->codprodutovariacao != $pb_destino->codprodutovariacao) {
            throw new \Exception('Barras não são da mesma Variacao!');
        }
        if ($pb_origem->codprodutoembalagem != $pb_destino->codprodutoembalagem) {
            throw new \Exception('Barras não são da mesma Embalagem!');
        }
        $regs = $pb_origem->NegocioProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->NotaFiscalProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->CupomFiscalProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->NfeTerceiroItemS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->ValeCompraModeloProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->ValeCompraProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $pb_origem->delete();

        return $pb_destino;

    }
}
