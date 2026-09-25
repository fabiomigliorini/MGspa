<?php

namespace Mg\Pdv;

use Exception;
use Illuminate\Support\Facades\DB;

use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamentoService;
use Mg\Titulo\TituloService;

/**
 * Consumo de vale compras POR ESCOPO -- a escola inteira, ou uma turma dela.
 *
 * O resgate por bipagem ("VAL00012345") já rodava e não mudou: ele resolve o
 * caso de quem chega com o papel na mão. Este serviço resolve o outro caso,
 * que é o comum na temporada: a escola comprou 40 vales, o pai chega sem
 * papel nenhum e o caixa precisa consumir o crédito da turma do filho.
 *
 * A escolha é FIFO pelo vencimento: gasta primeiro o crédito que vence
 * antes, e só o ÚLTIMO vale do lote entra parcial. Sem isso sobrariam
 * dezenas de restos de centavos espalhados pela escola.
 *
 * Vale AO PORTADOR (favorecido = Consumidor) fica fora do escopo de
 * propósito: são todos do mesmo "favorecido", então "consumir o crédito do
 * Consumidor" gastaria o vale de um estranho. Ao portador só por bipagem.
 */
class PdvValeEscopoService
{
    const CODPESSOA_CONSUMIDOR = 1;

    /**
     * Escolas que têm crédito de vale em aberto, com o saldo de cada uma.
     *
     * Lê as DUAS estruturas: o vale dentro do negócio (tblnegociovale) e o
     * vale do sistema antigo (tblvalecompra), que ainda tem crédito vivo. A
     * segunda sai quando o legado for convertido.
     */
    public static function favorecidos($busca = null)
    {
        $sql = '
            with vale as (
                ' . static::sqlValesAbertos() . '
            )
            select
                v.codpessoafavorecido,
                p.fantasia as favorecido,
                count(*) as vales,
                sum(v.saldo) as saldo
            from vale v
            inner join tblpessoa p on (p.codpessoa = v.codpessoafavorecido)
            where (:busca::varchar is null or p.fantasia ilike :busca::varchar)
            group by v.codpessoafavorecido, p.fantasia
            order by p.fantasia
        ';
        return DB::select($sql, [
            'busca' => $busca ? '%' . $busca . '%' : null,
        ]);
    }

    /** Turmas com crédito em aberto dentro de uma escola. */
    public static function turmas($codpessoafavorecido)
    {
        $sql = '
            with vale as (
                ' . static::sqlValesAbertos() . '
            )
            select
                coalesce(v.turma, \'\') as turma,
                count(*) as vales,
                sum(v.saldo) as saldo
            from vale v
            where v.codpessoafavorecido = :codpessoafavorecido
            group by coalesce(v.turma, \'\')
            order by 1
        ';
        return DB::select($sql, [
            'codpessoafavorecido' => $codpessoafavorecido,
        ]);
    }

    /**
     * Escolhe, em FIFO, os vales que cobrem $valor.
     *
     * Devolve uma linha por vale com quanto usar dele; só o último entra
     * parcial. Se o escopo não cobrir o valor inteiro, devolve o que tem --
     * quem chama mostra o que falta e o caixa completa com outra forma.
     */
    public static function selecionar($codpessoafavorecido, $turma, $valor, $codtitulosJaUsados = [])
    {
        if (empty($codpessoafavorecido) || $codpessoafavorecido == static::CODPESSOA_CONSUMIDOR) {
            throw new Exception('Vale ao portador só pode ser resgatado bipando o código!', 1);
        }
        $valor = round((float) $valor, 2);
        if ($valor <= 0) {
            throw new Exception('Informe o valor a consumir!', 1);
        }

        $vales = static::valesAbertos($codpessoafavorecido, $turma, $codtitulosJaUsados);

        $restante = $valor;
        $escolhidos = [];
        foreach ($vales as $v) {
            if ($restante <= 0) {
                break;
            }
            $usar = round(min((float) $v->saldo, $restante), 2);
            if ($usar <= 0) {
                continue;
            }
            $v->usar = $usar;
            $escolhidos[] = $v;
            $restante = round($restante - $usar, 2);
        }

        return [
            'vales' => $escolhidos,
            'total' => round($valor - $restante, 2),
            'falta' => $restante,
            'saldoescopo' => round(array_sum(array_map(fn($v) => (float) $v->saldo, $vales)), 2),
        ];
    }

    public static function valesAbertos($codpessoafavorecido, $turma = null, $codtitulosJaUsados = [])
    {
        $sql = '
            with vale as (
                ' . static::sqlValesAbertos() . '
            )
            select v.*, p.fantasia as favorecido
            from vale v
            inner join tblpessoa p on (p.codpessoa = v.codpessoafavorecido)
            where v.codpessoafavorecido = :codpessoafavorecido
              and (:turma::varchar is null or coalesce(v.turma, \'\') = :turma::varchar)
              and (:semusados::bool or not (v.codtitulo = any(string_to_array(:usados, \',\')::bigint[])))
            order by v.vencimento, v.codtitulo
        ';
        return DB::select($sql, [
            'codpessoafavorecido' => $codpessoafavorecido,
            'turma' => ($turma === null || $turma === '') ? null : $turma,
            'semusados' => empty($codtitulosJaUsados),
            'usados' => implode(',', array_map('intval', $codtitulosJaUsados)) ?: '0',
        ]);
    }

    /**
     * Vales com crédito vivo, das duas estruturas.
     *
     * "saldo" sai positivo: no título o crédito é saldo NEGATIVO, e trocar o
     * sinal aqui evita espalhar `abs()` por todo lado.
     */
    private static function sqlValesAbertos()
    {
        $tipoVale = TituloService::TIPO_VALE;

        $legado = '';
        if (static::tabelaExiste('tblvalecompra')) {
            $legado = "
                union all
                select
                    vc.codpessoafavorecido,
                    vc.aluno,
                    vc.turma,
                    t.codtitulo,
                    t.numero,
                    t.vencimento,
                    -t.saldo as saldo,
                    'tblvalecompra' as origem
                from tblvalecompra vc
                inner join tbltitulo t on (t.codtitulo = vc.codtitulo)
                where vc.inativo is null
                  and t.codtipotitulo = {$tipoVale}
                  and t.estornado is null
                  and t.saldo < 0
            ";
        }

        return "
            select
                nv.codpessoafavorecido,
                nv.aluno,
                nv.turma,
                t.codtitulo,
                t.numero,
                t.vencimento,
                -t.saldo as saldo,
                'tblnegociovale' as origem
            from tblnegociovale nv
            inner join tbltitulo t on (t.codtitulo = nv.codtitulo)
            inner join tblnegocio n on (n.codnegocio = nv.codnegocio)
            where nv.inativo is null
              and n.codnegociostatus = 2
              and t.codtipotitulo = {$tipoVale}
              and t.estornado is null
              and t.saldo < 0
            {$legado}
        ";
    }

    /**
     * Reconfere, COM LOCK, se cada vale usado neste negócio ainda tem saldo.
     *
     * Roda no fechamento, antes do baixarVales. Sem isto, dois PDVs montam o
     * FIFO sobre o mesmo pool da escola ao mesmo tempo, os dois passam na
     * validação da tela e o segundo estoura o crédito -- o título fica com
     * saldo do lado errado e ninguém percebe até a escola reclamar.
     *
     * O `lockForUpdate` serializa os dois fechamentos: o segundo espera o
     * primeiro gravar e aí enxerga o saldo já consumido.
     */
    public static function reconferirSaldos(Negocio $negocio)
    {
        $nfps = $negocio->NegocioFormaPagamentoS()
            ->whereNotNull('codtitulo')
            ->where('codformapagamento', NegocioFormaPagamentoService::CODFORMAPAGAMENTO_VALE)
            ->orderBy('codnegocioformapagamento')
            ->get();

        if ($nfps->isEmpty()) {
            return;
        }

        // quanto este negócio quer tirar de cada título
        $querUsar = [];
        foreach ($nfps as $nfp) {
            $cod = (int) $nfp->codtitulo;
            $querUsar[$cod] = round(($querUsar[$cod] ?? 0) + (float) $nfp->valorpagamento, 2);
        }

        // trava os títulos na ordem do código, sempre -- ordem fixa é o que
        // impede dois fechamentos de travarem um no outro
        $codigos = array_keys($querUsar);
        sort($codigos);
        $titulos = DB::select(
            'select codtitulo, numero, saldo, estornado
               from tbltitulo
              where codtitulo = any(:codigos::bigint[])
              order by codtitulo
                for update',
            ['codigos' => '{' . implode(',', $codigos) . '}']
        );

        $porCodigo = [];
        foreach ($titulos as $t) {
            $porCodigo[(int) $t->codtitulo] = $t;
        }

        foreach ($querUsar as $cod => $valor) {
            $t = $porCodigo[$cod] ?? null;
            if (!$t) {
                throw new Exception("O vale #{$cod} não existe mais! Refaça o pagamento.", 1);
            }
            if (!empty($t->estornado)) {
                throw new Exception("O vale {$t->numero} foi estornado! Refaça o pagamento.", 1);
            }
            $disponivel = round(-1 * (float) $t->saldo, 2);
            if ($valor - $disponivel > 0.005) {
                $falta = formataNumero($valor - $disponivel, 2);
                $disp = formataNumero($disponivel, 2);
                throw new Exception(
                    "O vale {$t->numero} não tem mais saldo suficiente: disponível R$ {$disp}, "
                        . "faltam R$ {$falta}. Alguém consumiu esse crédito em outro caixa. "
                        . 'Refaça o pagamento do vale.',
                    1
                );
            }
        }
    }

    private static function tabelaExiste($tabela)
    {
        $r = DB::select('select to_regclass(?) as t', [$tabela]);
        return count($r) > 0 && $r[0]->t !== null;
    }
}
