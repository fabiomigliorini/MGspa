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

    // Tipos que baixam o título (ou desfazem uma baixa): o valor efetivo da
    // baixa vai em total. Nos demais (implantação, ajuste...) total é 0.
    const TIPOS_BAIXA = [
        self::TIPO_AMORTIZACAO,
        self::TIPO_LIQUIDACAO,
        self::TIPO_RH,
        self::TIPO_LIQUIDACAO_COBRANCA,
        self::TIPO_AGRUPAMENTO,
        self::TIPO_ESTORNO_LIQUIDACAO_COBRANCA,
        self::TIPO_ESTORNO_LIQUIDACAO,
        self::TIPO_ESTORNO_AMORTIZACAO,
        self::TIPO_ESTORNO_AGRUPAMENTO,
    ];

    /**
     * Único ponto que grava movimento de título. Uma linha por título em
     * cada baixa, com juros, multa e desconto nela.
     *
     * $principal tem sinal: é o efeito no saldo. Positivo aumenta o que o
     * título tem a receber, negativo diminui (ou aumenta o que tem a pagar).
     * Liquidar um título a receber é lançar o principal negativo.
     *
     * $valores: juros, multa e desconto (positivos) e total (o valor
     * efetivo da baixa, mesmo sinal do principal). Sem total, a baixa
     * calcula |total| = |principal| + juros + multa - desconto; os demais
     * tipos gravam 0.
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
        float $principal,
        array $valores = [],
        array $vinculos = [],
        array $unicoPor = []
    ): MovimentoTitulo {
        $juros = round((float) ($valores['juros'] ?? 0), 2);
        $multa = round((float) ($valores['multa'] ?? 0), 2);
        $desconto = round((float) ($valores['desconto'] ?? 0), 2);
        if ($juros < 0 || $multa < 0 || $desconto < 0) {
            abort(422, 'Juros, multa e desconto não podem ser negativos!');
        }
        $principal = round($principal, 2);
        if (array_key_exists('total', $valores)) {
            $total = round((float) $valores['total'], 2);
        } elseif (in_array($tipo, static::TIPOS_BAIXA)) {
            $sinal = $principal < 0 ? -1 : 1;
            $total = round($sinal * (abs($principal) + $juros + $multa - $desconto), 2);
        } else {
            $total = 0;
        }

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
        $mov->principal = $principal;
        $mov->juros = $juros;
        $mov->multa = $multa;
        $mov->desconto = $desconto;
        $mov->total = $total;
        $mov->transacao = $mov->transacao ?? Carbon::today();
        $mov->save();

        static::recalcular($titulo);
        if (!empty($codtituloAnterior) && $codtituloAnterior != $titulo->codtitulo) {
            static::recalcular(Titulo::findOrFail($codtituloAnterior));
        }

        return $mov;
    }

    /**
     * Desfaz um movimento inteiro numa linha: principal e total contrários,
     * os mesmos juros, multa e desconto, com o mesmo tipo do original e
     * apontando para ele.
     */
    public static function estornar(MovimentoTitulo $movimento): MovimentoTitulo
    {
        // findOrFail, e não a relação: quem chama costuma trazer o título
        // carregado só com as colunas da tela
        return static::lancar(
            Titulo::findOrFail($movimento->codtitulo),
            static::tipoEstorno($movimento),
            -1 * (float) $movimento->principal,
            [
                'juros'    => (float) $movimento->juros,
                'multa'    => (float) $movimento->multa,
                'desconto' => (float) $movimento->desconto,
                'total'    => -1 * (float) $movimento->total,
            ],
            [
                'codmovimentotituloestorno'   => $movimento->codmovimentotitulo,
                'codportador'                 => $movimento->codportador,
                'codpagamento'                => $movimento->codpagamento,
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
                    select coalesce(sum(principal), 0) as saldo,
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
