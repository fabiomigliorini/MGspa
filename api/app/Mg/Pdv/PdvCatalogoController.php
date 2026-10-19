<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Mg\Produto\ProdutoService;

/**
 * Catálogo do PDV (TASK-197): versões e arquivos da sincronização incremental, e as consultas
 * ao vivo que alimentam o mesmo cache offline (bipe de barras, pesquisa por CNPJ/CPF). Roda
 * sem usuário logado: autoriza pelo dispositivo (middleware `pdv`).
 */
class PdvCatalogoController
{
    private static function tabela($tabela): string
    {
        if (!isset(PdvCatalogoService::TABELAS[$tabela])) {
            abort(404, "Catálogo '{$tabela}' não existe.");
        }
        return $tabela;
    }

    // ?desde=AAAAMMDD-HHMMSS devolve só as versões mais novas que a do PDV
    public function versoes(Request $request, $tabela)
    {
        $desde = preg_match('/^\d{8}-\d{6}$/', (string) $request->desde) ? $request->desde : null;
        return PdvCatalogoService::versoes(static::tabela($tabela), $desde);
    }

    // devolve o .gz como está, com Content-Encoding: o navegador descomprime sozinho
    public function arquivo(Request $request, $tabela, $versao, $tipo)
    {
        $arquivo = PdvCatalogoService::arquivo(static::tabela($tabela), $versao, $tipo);
        if (!is_file($arquivo)) {
            abort(404, "A versão {$versao} do catálogo de {$tabela} não está mais no servidor.");
        }
        return response()->file($arquivo, [
            'Content-Type' => 'application/x-ndjson',
            'Content-Encoding' => 'gzip',
        ]);
    }

    public function produtoBarras(Request $request, $barras)
    {
        $codprodutobarra = -1;
        if ($pb = ProdutoService::buscaPorBarras($barras)) {
            $codprodutobarra = $pb->codprodutobarra;
        }
        return PdvCatalogoService::produto($codprodutobarra, 1);
    }

    public function pessoaPeloCnpj(Request $request, $cnpj)
    {
        return PdvCatalogoService::pessoa(null, $cnpj, 999999);
    }
}
