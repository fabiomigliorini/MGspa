<?php

namespace Mg\Colaborador;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ColaboradorComissaoService
{

    public static function comissaoCaixas($inicio, $fim)
    {
        $sql = '
            WITH cx AS (
                SELECT
                    cc.codfilial,
                    f.filial,
                    p.codpessoa,
                    p.pessoa,
                    u.codusuario,
                    c.codcargo,
                    c.cargo,
                    c.comissaocaixa
                FROM
                    tblcargo c
                INNER JOIN tblcolaboradorcargo cc ON cc.codcargo = c.codcargo
                INNER JOIN tblcolaborador col ON col.codcolaborador = cc.codcolaborador
                INNER JOIN tblpessoa p ON p.codpessoa = col.codpessoa
                INNER JOIN tblusuario u ON u.codpessoa = col.codpessoa
                INNER JOIN tblfilial f ON f.codfilial = cc.codfilial
                WHERE
                    c.comissaocaixa IS NOT NULL
                    -- 1. Vigência do CARGO sobrepõe o período [:inicio, :fim]
                    AND (cc.inicio, COALESCE(cc.fim, \'infinity\'::date)) OVERLAPS (:inicio, :fim)
                    -- 2. Vigência da CONTRATAÇÃO sobrepõe o período [:inicio, :fim]
                    AND (col.contratacao, COALESCE(col.rescisao, \'infinity\'::date)) OVERLAPS (:inicio, :fim)
                    -- 3. EXCLUI colaboradores que estavam em PERÍODO DE EXPERIÊNCIA no intervalo [:inicio, :fim]
                    --    Se col.experiencia for nulo, a sobreposição é falsa, incluindo o colaborador (CORRETO).
                    --    Se col.experiencia for uma data, verifica se o período [Contratação, Experiência] se sobrepõe.
                    AND NOT (
                        (col.contratacao, col.experiencia) OVERLAPS (:inicio, :fim)
                    )
            )
            SELECT
                cx.codfilial,
                cx.filial,
                cx.codpessoa,
                cx.pessoa,
                cx.codcargo,
                cx.cargo,
                cx.comissaocaixa,
                COUNT(n.codnegocio) AS negocios,
                SUM(n.valortotal) AS valor,
                SUM(n.valortotal * (cx.comissaocaixa / 100)) AS comissao
            FROM
                tblnegocio n
            INNER JOIN cx ON cx.codusuario = n.codusuario -- Conexão direta com a CTE (melhor performance)
            INNER JOIN tblnaturezaoperacao nat ON nat.codnaturezaoperacao = n.codnaturezaoperacao
            WHERE
                n.lancamento BETWEEN :inicio AND :fim
                AND n.codnegociostatus = 2 -- Status de concluído
                AND nat.venda = TRUE
            GROUP BY
                cx.codfilial,
                cx.filial,
                cx.codpessoa,
                cx.pessoa,
                cx.codcargo,
                cx.cargo,
                cx.comissaocaixa
            ORDER BY
                cx.filial,
                cx.pessoa;
        ';

        $ret = DB::select($sql, [
            'inicio' => $inicio,
            'fim' => $fim,
        ]);
        return $ret;
    }
}
