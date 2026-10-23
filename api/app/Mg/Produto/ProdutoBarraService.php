<?php

namespace Mg\Produto;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProdutoBarraService
{
    /**
     * Pesquisa de produto em grade (MgDialogPesquisaProduto, TASK-173), com o mesmo filtro da
     * pesquisa do PDV na base offline (negocios/src/stores/produto.js). O texto de busca de cada
     * barra é o que o catálogo do PDV monta (PdvCatalogoService::produto): a descrição com
     * variação, unidade e C/quantidade, o preço, as barras e os 6 últimos dígitos delas. A
     * primeira palavra digitada tem que começar uma palavra desse texto e as outras aparecer em
     * qualquer ponto dele, sem diferença de maiúscula: pesquisou "vermelh", só vem o que tem
     * "vermelh" escrito.
     *
     * A página é de 20 PRODUTOS, cada um com todas as barras dele que casaram -- nem mais
     * (pesquisou "verde", a azul não vem), nem menos (o produto nunca fica partido entre duas
     * páginas). Uma linha por barra, com o que a grade usa para agrupar (nome, variacao, sigla,
     * quantidade) e value/label, que é o que quem recebe o produto escolhido lê.
     *
     * A ordem é a do produto: a relevância dele é a da barra que mais se parece com o texto, o
     * preço o menor, as barras a menor.
     */
    public static function pesquisa(string $busca, ?string $ordem, int $pagina): array
    {
        $palavras = array_values(array_filter(explode(' ', trim($busca)), fn ($p) => $p !== ''));
        if (empty($palavras)) {
            return [];
        }

        $filtro = [
            'frase' => implode(' ', $palavras),
            'url' => config('services.mglara.imagens_url') . '/',
        ];
        $where = [];
        foreach ($palavras as $i => $palavra) {
            $like = addcslashes($palavra, '\\%_');
            if ($i == 0) {
                // a primeira começa uma palavra do texto
                $where[] = "(' ' || busca) ILIKE :palavra_{$i}";
                $filtro["palavra_{$i}"] = "% {$like}%";
                continue;
            }
            $where[] = "busca ILIKE :palavra_{$i}";
            $filtro["palavra_{$i}"] = "%{$like}%";
        }
        $where = implode(' AND ', $where);

        // whitelist -- nunca concatenar o que vem do request
        $ordens = [
            'alfabetica' => 'nome ASC, preco ASC',
            'preco' => 'preco ASC, nome ASC',
            'codigo' => 'codproduto ASC',
            'barras' => 'barras ASC, nome ASC',
        ];
        $ordenacao = $ordens[strtolower((string) $ordem)] ?? 'score DESC, nome ASC, preco ASC';

        $inicio = ($pagina - 1) * 20;
        $fim = $inicio + 20;

        $sql = "
            WITH linhas AS (
                SELECT
                    pb.codprodutobarra,
                    p.codproduto,
                    pv.codprodutovariacao,
                    pb.barras,
                    p.produto AS nome,
                    pv.variacao,
                    coalesce(ume.sigla, um.sigla) AS sigla,
                    coalesce(ume.decimais, um.decimais) AS decimais,
                    pe.quantidade,
                    coalesce(pe.preco, p.preco * coalesce(pe.quantidade, 1)) AS preco,
                    i.observacoes AS imagem
                FROM tblproduto p
                INNER JOIN tblunidademedida um ON (um.codunidademedida = p.codunidademedida)
                INNER JOIN tblprodutovariacao pv ON (pv.codproduto = p.codproduto)
                INNER JOIN tblprodutobarra pb ON (pb.codprodutovariacao = pv.codprodutovariacao)
                LEFT JOIN tblprodutoembalagem pe ON (pe.codprodutoembalagem = pb.codprodutoembalagem)
                LEFT JOIN tblunidademedida ume ON (ume.codunidademedida = pe.codunidademedida)
                LEFT JOIN tblprodutoimagem pri ON (pri.codprodutoimagem = pv.codprodutoimagem)
                LEFT JOIN tblimagem i ON (i.codimagem = pri.codimagem)
                WHERE coalesce(pv.inativo, p.inativo) IS NULL
            ), descritas AS (
                -- a descrição do PdvCatalogoService::montarDescricaoProduto
                SELECT
                    *,
                    nome
                        || coalesce(' ' || nullif(variacao, ''), '')
                        || ' ' || sigla
                        || coalesce(' C/' || trunc(nullif(quantidade, 0))::bigint, '') AS descricao
                FROM linhas
            ), casadas AS (
                SELECT *, strict_word_similarity(unaccent(:frase), unaccent(descricao)) AS score
                FROM (
                    SELECT
                        *,
                        descricao
                            || ' ' || replace(to_char(preco, 'FM999999999990.00'), '.', ',')
                            || ' ' || barras
                            || ' ' || right(barras, 6) AS busca
                    FROM descritas
                ) b
                WHERE {$where}
            ), produtos AS (
                SELECT
                    codproduto,
                    max(score) AS score,
                    min(nome) AS nome,
                    min(preco) AS preco,
                    min(barras) AS barras
                FROM casadas
                GROUP BY codproduto
            ), pagina AS (
                SELECT codproduto, row_number() OVER (ORDER BY {$ordenacao}, codproduto) AS posicao
                FROM produtos
            )
            SELECT
                c.codprodutobarra,
                c.codproduto,
                c.codprodutovariacao,
                c.barras,
                c.descricao,
                c.nome,
                c.variacao,
                c.sigla,
                c.decimais,
                c.quantidade,
                c.preco,
                :url || c.imagem AS imagem,
                c.codprodutobarra AS value,
                c.descricao AS label
            FROM casadas c
            INNER JOIN pagina p ON (p.codproduto = c.codproduto)
            WHERE p.posicao > {$inicio} AND p.posicao <= {$fim}
            ORDER BY p.posicao, c.score DESC, c.codprodutobarra
        ";
        return DB::select($sql, $filtro);
    }

    /**
     * Resolve um código digitado ou bipado numa barra ÚNICA, nas duas formas que o PDV aceita:
     * o código de barras exato, ou o código do produto com 6 dígitos, só nas barras sem
     * embalagem.
     *
     * Só responde quando cai numa barra só: produto com variação de cor tem uma barra base por
     * cor (o 200304, Tinta Acrilex 37ml, tem 6), e escolher uma delas seria ao acaso. Não achou
     * é 404, mais de uma é 409; nos dois a tela abre a pesquisa em grade.
     */
    public static function barras(string $codigo): ProdutoBarra
    {
        $codigo = trim($codigo);
        $qry = ProdutoBarra::where('barras', $codigo);
        if (preg_match('/^\d{6}$/', $codigo)) {
            $qry->orWhere(function ($q) use ($codigo) {
                $q->where('codproduto', intval($codigo))->whereNull('codprodutoembalagem');
            });
        }
        $barras = $qry->limit(10)->get();
        if ($barras->isEmpty()) {
            abort(404, "Nenhum produto com o código {$codigo}!");
        }
        if ($barras->count() > 1) {
            abort(409, "O código {$codigo} cai em {$barras->count()} produtos. Use a pesquisa.");
        }
        return $barras->first();
    }

    /**
     * Regenera os movimentos de estoque da variação no MGLara (best-effort).
     * Quando uma barra muda de variação, as vendas/notas ligadas a ela
     * precisam ser recontabilizadas nas duas variações (origem e destino).
     * O MGLara é o dono da fila de jobs de estoque (mesmo padrão de
     * `unificaVariacoes`, que delega o custo médio ao MGLara).
     */
    public static function geraMovimentoVariacao($codprodutovariacao): void
    {
        $base = rtrim((string) config('services.mglara.url'), '/');
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
        $regs = $pb_origem->ValeModeloProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->NegocioValeProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $pb_origem->delete();

        return $pb_destino;

    }
}
