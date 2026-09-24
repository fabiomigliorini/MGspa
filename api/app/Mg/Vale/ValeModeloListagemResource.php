<?php

namespace Mg\Vale;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

/**
 * Resource da listagem: so o cabecalho do modelo, sem nada dos itens.
 *
 * A listagem nao mostra item nenhum, e o formulario de edicao e outra rota,
 * que chama o show. Mandar a lista de itens aqui custava ~780 itens por
 * pagina, com toda a arvore de produto/variacao/imagem atras: eram 617
 * queries em 1,5s contra 6 em 0,08s.
 */
class ValeModeloListagemResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        unset($ret['pessoa_favorecido'], $ret['usuario_criacao'], $ret['usuario_alteracao']);

        $ret['favorecido'] = optional($this->PessoaFavorecido)->fantasia;

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        return $ret;
    }
}
