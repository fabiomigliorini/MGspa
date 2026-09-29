<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MovimentoTituloService
{
    const TIPO_IMPLANTACAO = 100;
    const TIPO_AJUSTE = 200;
    const TIPO_AMORTIZACAO = 300;
    const TIPO_JUROS = 400;
    const TIPO_MULTA = 401;
    const TIPO_DESCONTO = 500;
    const TIPO_LIQUIDACAO = 600;
    const TIPO_RH = 601;
    const TIPO_LIQUIDACAO_COBRANCA = 610;
    const TIPO_ESTORNO_IMPLANTACAO = 900;
    const TIPO_AGRUPAMENTO = 901;
    const TIPO_ESTORNO_LIQUIDACAO_COBRANCA = 910;
    const TIPO_ESTORNO_AJUSTE = 920;
    const TIPO_ESTORNO_LIQUIDACAO = 930;
    const TIPO_ESTORNO_AMORTIZACAO = 933;
    const TIPO_ESTORNO_JUROS = 940;
    const TIPO_ESTORNO_MULTA = 941;
    const TIPO_ESTORNO_DESCONTO = 950;
    const TIPO_ESTORNO_AGRUPAMENTO = 991;
    const TIPO_TRANSFERENCIA = 992;

    // Tipos que só existem para desfazer outro. O estorno de hoje usa o tipo
    // do movimento original e aponta para ele; estes ficam para o histórico
    // e para o estorno do título inteiro (900).
    const TIPOS_ESTORNO = [
        self::TIPO_ESTORNO_IMPLANTACAO,
        self::TIPO_ESTORNO_LIQUIDACAO_COBRANCA,
        self::TIPO_ESTORNO_AJUSTE,
        self::TIPO_ESTORNO_LIQUIDACAO,
        self::TIPO_ESTORNO_AMORTIZACAO,
        self::TIPO_ESTORNO_JUROS,
        self::TIPO_ESTORNO_MULTA,
        self::TIPO_ESTORNO_DESCONTO,
        self::TIPO_ESTORNO_AGRUPAMENTO,
    ];

    /**
     * Único ponto que grava movimento de título.
     *
     * $valor tem sinal: positivo aumenta o que o título tem a receber,
     * negativo diminui (ou aumenta o que tem a pagar). Liquidar um título a
     * receber é lançar o valor negativo.
     *
     * $vinculos são as demais colunas do movimento (portador, liquidação,
     * agrupamento, boleto, acerto, histórico, transacao...).
     *
     * $unicoPor lista os vínculos que identificam o movimento: se já existe
     * um deste tipo com os mesmos vínculos ele é regravado, em vez de nascer
     * outro. É o que deixa reprocessar retorno de boleto e refechar negócio
     * sem duplicar.
     *
     * Sem transação interna: fica a cargo de quem chama.
     */
    public static function lancar(
        Titulo $titulo,
        int $tipo,
        float $valor,
        array $vinculos = [],
        array $unicoPor = []
    ): MovimentoTitulo {
        $mov = null;
        if (!empty($unicoPor)) {
            $mov = MovimentoTitulo::where('codtipomovimentotitulo', $tipo)
                ->where(array_intersect_key($vinculos, array_flip($unicoPor)))
                ->first();
        }
        $mov = $mov ?? new MovimentoTitulo();
        $codtituloAnterior = $mov->codtitulo;

        $mov->fill($vinculos);
        $mov->codtitulo = $titulo->codtitulo;
        $mov->codtipomovimentotitulo = $tipo;
        $mov->valor = round($valor, 2);
        $mov->transacao = $mov->transacao ?? Carbon::today();
        $mov->save();

        static::recalcular($titulo);
        if (!empty($codtituloAnterior) && $codtituloAnterior != $titulo->codtitulo) {
            static::recalcular(Titulo::findOrFail($codtituloAnterior));
        }
        if (!empty($mov->codliquidacaotitulo)) {
            static::recalcularLiquidacao($mov->codliquidacaotitulo);
        }

        return $mov;
    }

    /**
     * Desfaz um movimento: lança o valor contrário, com o mesmo tipo do
     * original e apontando para ele.
     */
    public static function estornar(MovimentoTitulo $movimento): MovimentoTitulo
    {
        // findOrFail, e não a relação: quem chama costuma trazer o título
        // carregado só com as colunas da tela
        return static::lancar(
            Titulo::findOrFail($movimento->codtitulo),
            static::tipoEstorno($movimento),
            -1 * (float) $movimento->valor,
            [
                'codmovimentotituloestorno'   => $movimento->codmovimentotitulo,
                'codportador'                 => $movimento->codportador,
                'codliquidacaotitulo'         => $movimento->codliquidacaotitulo,
                'codperiodocolaboradoracerto' => $movimento->codperiodocolaboradoracerto,
            ]
        );
    }

    /**
     * Tipo com que o estorno é gravado. É o do próprio movimento: quem diz
     * que a linha é estorno é o codmovimentotituloestorno, não o tipo.
     */
    public static function tipoEstorno(MovimentoTitulo $movimento): int
    {
        return (int) $movimento->codtipomovimentotitulo;
    }

    /**
     * Refaz no título o que depende dos movimentos: saldo, data de
     * liquidação e de estorno (as duas só existem com o saldo zerado) e, se
     * o título nasceu de um agrupamento, o total dele.
     *
     * Chamar depois de apagar movimento ou de mudar a transacao dele por
     * fora do lancar().
     *
     * UPDATE direto, e não pelo model: recalcular saldo não é alteração do
     * título, e o Eloquent carimbaria alteracao/codusuarioalteracao.
     */
    public static function recalcular(Titulo $titulo): void
    {
        $sql = '
            update tbltitulo t
               set saldo               = m.saldo,
                   transacaoliquidacao = case when m.saldo = 0 then m.transacao end,
                   estornado           = case when m.saldo = 0 then m.estornado end
              from (
                    select coalesce(sum(valor), 0) as saldo,
                           max(transacao) as transacao,
                           max(criacao)
                               filter (where codtipomovimentotitulo = :estorno) as estornado
                      from tblmovimentotitulo
                     where codtitulo = :codtitulomov
                   ) m
             where t.codtitulo = :codtitulo
         returning t.saldo, t.transacaoliquidacao, t.estornado
        ';
        $novo = DB::selectOne($sql, [
            'estorno'      => static::TIPO_ESTORNO_IMPLANTACAO,
            'codtitulomov' => $titulo->codtitulo,
            'codtitulo'    => $titulo->codtitulo,
        ]);

        // espelha no objeto que quem chamou tem na mão, sem marcar como sujo
        if ($novo) {
            $campos = (array) $novo;
            $titulo->forceFill($campos)->syncOriginalAttributes(array_keys($campos));
        }

        if (!empty($titulo->codtituloagrupamento)) {
            static::recalcularAgrupamento($titulo->codtituloagrupamento);
        }
    }

    /**
     * Total da liquidação = soma líquida das liquidações (600) dela, com o
     * sinal do movimento: negativo quando recebeu mais do que pagou. Estorno
     * não entra: a liquidação estornada guarda o total que teve.
     *
     * debito/credito da liquidação ainda são gravados junto: o "Totais de
     * Caixa" do MGLara soma as duas colunas. Saem quando essa tela sair.
     */
    public static function recalcularLiquidacao(int $codliquidacaotitulo): void
    {
        $sql = '
            update tblliquidacaotitulo l
               set valor   = q.valor,
                   debito  = q.debito,
                   credito = q.credito
              from (
                    select codliquidacaotitulo,
                           sum(valor) as valor,
                           sum(greatest(valor, 0)) as debito,
                           sum(greatest(-valor, 0)) as credito
                      from tblmovimentotitulo
                     where codliquidacaotitulo = :codmov
                       and codtipomovimentotitulo = :tipo
                       and codmovimentotituloestorno is null
                     group by codliquidacaotitulo
                   ) q
             where l.codliquidacaotitulo = :cod
        ';
        DB::update($sql, [
            'codmov' => $codliquidacaotitulo,
            'tipo'   => static::TIPO_LIQUIDACAO,
            'cod'    => $codliquidacaotitulo,
        ]);
    }

    /**
     * Total do agrupamento = soma dos títulos que ele gerou.
     */
    public static function recalcularAgrupamento(int $codtituloagrupamento): void
    {
        $sql = '
            update tbltituloagrupamento a
               set valor = q.valor
              from (
                    select codtituloagrupamento,
                           sum(valor) as valor
                      from tbltitulo
                     where codtituloagrupamento = :codtit
                     group by codtituloagrupamento
                   ) q
             where a.codtituloagrupamento = :cod
        ';
        DB::update($sql, [
            'codtit' => $codtituloagrupamento,
            'cod'    => $codtituloagrupamento,
        ]);
    }
}
