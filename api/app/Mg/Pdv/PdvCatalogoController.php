<?php

namespace Mg\Pdv;

use Mg\Produto\ProdutoService;

/**
 * Catálogo do PDV (TASK-197): versões e arquivos da sincronização incremental, e as consultas
 * ao vivo que alimentam o mesmo cache offline (bipe de barras, pesquisa por CNPJ/CPF). Roda
 * sem usuário logado: autoriza pelo dispositivo.
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
    public function versoes(PdvRequest $request, $tabela)
    {
        PdvService::autoriza($request->pdv);
        $desde = preg_match('/^\d{8}-\d{6}$/', (string) $request->desde) ? $request->desde : null;
        return PdvCatalogoService::versoes(static::tabela($tabela), $desde);
    }

    // devolve o .gz como está, com Content-Encoding: o navegador descomprime sozinho
    public function arquivo(PdvRequest $request, $tabela, $versao, $tipo)
    {
        PdvService::autoriza($request->pdv);
        $arquivo = PdvCatalogoService::arquivo(static::tabela($tabela), $versao, $tipo);
        if (!is_file($arquivo)) {
            abort(404, "A versão {$versao} do catálogo de {$tabela} não está mais no servidor.");
        }
        return response()->file($arquivo, [
            'Content-Type' => 'application/x-ndjson',
            'Content-Encoding' => 'gzip',
        ]);
    }

    public function produtoBarras(PdvRequest $request, $barras)
    {
        PdvService::autoriza($request->pdv);
        $codprodutobarra = -1;
        if ($pb = ProdutoService::buscaPorBarras($barras)) {
            $codprodutobarra = $pb->codprodutobarra;
        }
        return PdvCatalogoService::produto($codprodutobarra, 1);
    }

    public function pessoaPeloCnpj(PdvRequest $request, $cnpj)
    {
        PdvService::autoriza($request->pdv);
        return PdvCatalogoService::pessoa(null, $cnpj, 999999);
    }
}
