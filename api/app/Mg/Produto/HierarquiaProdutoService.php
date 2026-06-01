<?php

namespace Mg\Produto;

use Mg\MgService;

/**
 * Alimenta a árvore (q-tree lazy) da hierarquia de produto:
 * seção → família → grupo → subgrupo.
 *
 * Cada nó vem pronto pro q-tree: `key` único entre níveis, `nivel` pra
 * o frontend saber qual endpoint chamar ao criar filho/editar/inativar,
 * e `lazy` true em tudo menos subgrupo (folha).
 */
class HierarquiaProdutoService extends MgService
{
    public static function filhos(string $nivel, ?int $id = null): array
    {
        switch ($nivel) {
            case 'raiz':
                return self::montar(
                    SecaoProduto::orderBy('secaoproduto')->get(),
                    'secao',
                    'codsecaoproduto',
                    'secaoproduto',
                    true,
                );

            case 'secao':
                return self::montar(
                    FamiliaProduto::where('codsecaoproduto', $id)->orderBy('familiaproduto')->get(),
                    'familia',
                    'codfamiliaproduto',
                    'familiaproduto',
                    true,
                );

            case 'familia':
                return self::montar(
                    GrupoProduto::where('codfamiliaproduto', $id)->orderBy('grupoproduto')->get(),
                    'grupo',
                    'codgrupoproduto',
                    'grupoproduto',
                    true,
                );

            case 'grupo':
                return self::montar(
                    SubGrupoProduto::where('codgrupoproduto', $id)->orderBy('subgrupoproduto')->get(),
                    'subgrupo',
                    'codsubgrupoproduto',
                    'subgrupoproduto',
                    false,
                );

            default:
                return [];
        }
    }

    private static function montar($colecao, string $nivel, string $campoId, string $campoNome, bool $lazy): array
    {
        $ret = [];
        foreach ($colecao as $item) {
            $ret[] = [
                'key' => "{$nivel}-{$item->$campoId}",
                'codigo' => $item->$campoId,
                'nivel' => $nivel,
                'label' => $item->$campoNome,
                'inativo' => $item->inativo,
                'lazy' => $lazy,
            ];
        }
        return $ret;
    }
}
