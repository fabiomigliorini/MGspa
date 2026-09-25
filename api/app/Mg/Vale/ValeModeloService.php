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
        // Faixa sobre a face (valorvale), que e o numero que a tela mostra.
        if (isset($filter['valorde']) && $filter['valorde'] !== '') {
            $qry->where('tblvalemodelo.valorvale', '>=', $filter['valorde']);
        }
        if (isset($filter['valorate']) && $filter['valorate'] !== '') {
            $qry->where('tblvalemodelo.valorvale', '<=', $filter['valorate']);
        }
        // 1=ativo (default), 2=inativo, 9=todos. O default e "ativo" de
        // proposito: o catalogo e sazonal e 169 dos 204 modelos estao
        // inativos, entao listar tudo abriria a tela cheia de kit velho.
        $qry->AtivoInativo($filter['inativo'] ?? 1);

        if (empty($sort)) {
            // Vale ao portador (sem favorecido) por primeiro -- sao poucos
            // e nao pertencem a escola nenhuma, entao ficam a mao em vez de
            // afundar depois da ultima escola.
            $qry->orderByRaw('tblpessoa.fantasia asc nulls first')
                ->orderBy('tblvalemodelo.modelo');
        } else {
            $qry = self::qryOrdem($qry, $sort);
        }
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Grava cabecalho e itens. Do valor, so o avulso vem do cliente:
     * valorprodutos e sempre a soma dos itens e valorvale (a face) e a soma
     * dos dois, para o credito bater com o kit impresso no vale.
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
        $somaItens = 0;

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
            $somaItens += $valorprodutos;
        }

        ValeModeloProdutoBarra::where('codvalemodelo', $modelo->codvalemodelo)
            ->whereNotIn('codvalemodeloprodutobarra', $mantidos ?: [0])
            ->delete();

        $modelo->valorprodutos = round($somaItens, 2);
        $modelo->valoravulso = round((float) $modelo->valoravulso, 2);
        $modelo->valorvale = round($modelo->valorprodutos + $modelo->valoravulso, 2);
        $modelo->save();
    }

}
