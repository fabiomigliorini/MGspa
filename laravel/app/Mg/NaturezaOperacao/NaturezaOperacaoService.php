<?php
namespace Mg\NaturezaOperacao;
use Mg\MgService;

class NaturezaOperacaoService extends MgService
{

    const PRECO_NEGOCIO = 1;
    const PRECO_TRANSFERENCIA = 2;
    const PRECO_CUSTO = 3;
    const PRECO_ORIGEM = 4;

    const FINNFE_NORMAL = 1;
    const FINNFE_COMPLEMENTAR = 2;
    const FINNFE_AJUSTE = 3;
    const FINNFE_DEVOLUCAO_RETORNO = 4;

    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = NaturezaOperacao::query();
        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
