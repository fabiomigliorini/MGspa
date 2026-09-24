<?php

namespace Mg\Vale;

use Mg\MgService;

class ValeModeloService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        // Join com a pessoa para poder ordenar pelo nome do favorecido, que e
        // a primeira coluna da tela. O select explicito evita que as colunas
        // de tblpessoa vazem para o model.
        $qry = ValeModelo::query()
            ->select('tblvalemodelo.*')
            ->leftJoin('tblpessoa', 'tblpessoa.codpessoa', '=', 'tblvalemodelo.codpessoafavorecido')
            ->with(['PessoaFavorecido', 'UsuarioCriacao', 'UsuarioAlteracao']);

        if (!empty($filter['modelo'])) {
            $qry->palavras('tblvalemodelo.modelo', $filter['modelo']);
        }
        if (!empty($filter['codpessoafavorecido'])) {
            $qry->where('tblvalemodelo.codpessoafavorecido', $filter['codpessoafavorecido']);
        }
        if (isset($filter['valorde']) && $filter['valorde'] !== '') {
            $qry->where('tblvalemodelo.valorprodutos', '>=', $filter['valorde']);
        }
        if (isset($filter['valorate']) && $filter['valorate'] !== '') {
            $qry->where('tblvalemodelo.valorprodutos', '<=', $filter['valorate']);
        }
        // 1=ativo (default), 2=inativo, 9=todos. O default e "ativo" de
        // proposito: o catalogo e sazonal e 169 dos 204 modelos estao
        // inativos, entao listar tudo abriria a tela cheia de kit velho.
        $qry->AtivoInativo($filter['inativo'] ?? 1);

        if (empty($sort)) {
            // Vale ao portador (sem favorecido) por ultimo.
            $qry->orderByRaw('tblpessoa.fantasia asc nulls last')
                ->orderBy('tblvalemodelo.modelo');
        } else {
            $qry = self::qryOrdem($qry, $sort);
        }
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Grava cabecalho e itens. A face (valorprodutos) nunca vem do cliente:
     * e sempre a soma dos itens, para o credito emitido no milestone 4 bater
     * com o kit impresso no vale.
     */
    public static function salvar(array $dados, ?ValeModelo $modelo = null)
    {
        $modelo = $modelo ?: new ValeModelo();
        $modelo->fill($dados);
        $modelo->save();

        self::sincronizarItens($modelo, $dados['itens'] ?? []);

        return $modelo->fresh(['PessoaFavorecido', 'ValeModeloProdutoBarraS.ProdutoBarra']);
    }

    /**
     * Deixa a lista de itens identica a recebida: atualiza os que vieram com
     * id, insere os novos e apaga o resto. Apagar de verdade e seguro -- nada
     * referencia o item do catalogo, so o do vale emitido.
     */
    private static function sincronizarItens(ValeModelo $modelo, array $itens)
    {
        $mantidos = [];
        $face = 0;

        foreach ($itens as $item) {
            $quantidade = round((float) ($item['quantidade'] ?? 0), 3);
            $valorunitario = round((float) ($item['valorunitario'] ?? 0), 2);
            $valorprodutos = round($quantidade * $valorunitario, 2);

            $reg = null;
            if (!empty($item['codvalemodeloprodutobarra'])) {
                $reg = ValeModeloProdutoBarra::where('codvalemodelo', $modelo->codvalemodelo)
                    ->find($item['codvalemodeloprodutobarra']);
            }
            $reg = $reg ?: new ValeModeloProdutoBarra();

            $reg->fill([
                'codvalemodelo' => $modelo->codvalemodelo,
                'codprodutobarra' => $item['codprodutobarra'],
                'quantidade' => $quantidade,
                'valorunitario' => $valorunitario,
                'valorprodutos' => $valorprodutos,
            ]);
            $reg->save();

            $mantidos[] = $reg->codvalemodeloprodutobarra;
            $face += $valorprodutos;
        }

        ValeModeloProdutoBarra::where('codvalemodelo', $modelo->codvalemodelo)
            ->whereNotIn('codvalemodeloprodutobarra', $mantidos ?: [0])
            ->delete();

        $modelo->valorprodutos = round($face, 2);
        $modelo->save();
    }

}
