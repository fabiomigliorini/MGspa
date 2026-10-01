<?php

namespace Mg\Negocio;

use Carbon\Carbon;
use Mg\Feriado\Feriado;
use Mg\NaturezaOperacao\Operacao;
use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloService;
use Mg\Titulo\TipoTituloService;

/**
 * Parcelas do negocio (M4 do plano doc-3): o prazo da venda. Vencimento
 * sugerido pela condicao; ao fechar, cada parcela vira um titulo.
 */
class NegocioParcelaService
{
    const CONDICAO_FECHAMENTO = 'F'; // ultimo dia util do mes seguinte
    const CONDICAO_PARCELADO = 'P';  // crediario 30/60/90
    const CONDICAO_BOLETO = 'B';
    const CONDICAO_ENTREGA = 'E';    // paga ao receber/retirar
    const CONDICAO_PIX = 'X';        // PIX/deposito a receber
    const CONDICAO_VALE = 'V';       // vale (credito) gerado na devolucao

    const CONDICOES = [
        self::CONDICAO_FECHAMENTO => 'Fechamento Mensal',
        self::CONDICAO_PARCELADO => 'Crediário',
        self::CONDICAO_BOLETO => 'Boleto',
        self::CONDICAO_ENTREGA => 'Entrega',
        self::CONDICAO_PIX => 'PIX / Depósito a Receber',
        self::CONDICAO_VALE => 'Vale da Devolução',
    ];

    // Ultimo dia util do mes (seg a sab, sem feriado), como o RH conta.
    public static function ultimoDiaUtil(Carbon $mes): Carbon
    {
        $dia = $mes->copy()->endOfMonth()->startOfDay();
        $feriados = Feriado::whereNull('inativo')
            ->whereBetween('data', [$dia->copy()->startOfMonth()->format('Y-m-d'), $dia->format('Y-m-d')])
            ->pluck('data')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->flip();
        while ($dia->dayOfWeek === Carbon::SUNDAY || isset($feriados[$dia->format('Y-m-d')])) {
            $dia->subDay();
        }
        return $dia;
    }

    // Vencimento da parcela $numero pela condicao. dias = 30 conta meses.
    public static function vencimento(string $condicao, int $numero, ?int $dias, ?Carbon $base = null): Carbon
    {
        $base = ($base ?? Carbon::today())->copy()->startOfDay();
        switch ($condicao) {
            case static::CONDICAO_FECHAMENTO:
                return static::ultimoDiaUtil($base->copy()->startOfMonth()->addMonthsNoOverflow($numero));
            case static::CONDICAO_VALE:
                return $base->addYear();
        }
        // sem dias, vence no dia (como o PDV antigo fazia: PIX por chave)
        $dias = $dias ?? 0;
        if ($dias == 30) {
            return $base->addMonthsNoOverflow($numero);
        }
        return $base->addDays($dias * $numero);
    }

    // Cria as parcelas de uma forma a prazo: valor total (com juros) em
    // $parcelas vezes de $valorparcela, a ultima fecha a diferenca. Juros
    // rateado proporcionalmente, a ultima fecha a diferenca.
    public static function gerar(
        Negocio $negocio,
        string $condicao,
        float $valor,
        float $juros = 0,
        ?int $parcelas = 1,
        ?float $valorparcela = null,
        ?int $dias = null,
        ?string $uuidforma = null,
        ?Carbon $base = null
    ): array {
        if (!array_key_exists($condicao, static::CONDICOES)) {
            abort(422, "Condição de prazo {$condicao} inválida!");
        }
        $valor = round($valor, 2);
        $juros = round($juros, 2);
        if ($valor <= 0) {
            abort(422, 'O valor a prazo precisa ser maior que zero!');
        }
        $parcelas = max(1, (int) $parcelas);
        if (empty($valorparcela) || $parcelas == 1) {
            $valorparcela = floor(($valor / $parcelas) * 100) / 100;
        }
        $ret = [];
        $somaValor = 0;
        $somaJuros = 0;
        for ($i = 1; $i <= $parcelas; $i++) {
            if ($i == $parcelas) {
                $v = round($valor - $somaValor, 2);
                $j = round($juros - $somaJuros, 2);
            } else {
                $v = round($valorparcela, 2);
                $j = round($juros * $v / $valor, 2);
            }
            $somaValor += $v;
            $somaJuros += $j;
            $np = new NegocioParcela([
                'codnegocio' => $negocio->codnegocio,
                'condicao' => $condicao,
                'numero' => $i,
                'vencimento' => static::vencimento($condicao, $i, $dias, $base),
                'valor' => $v,
                'juros' => max(0, min($j, $v)),
                'uuidforma' => $uuidforma,
            ]);
            $np->save();
            $ret[] = $np;
        }
        return $ret;
    }

    // Tipo do titulo pela condicao (PIX e entrega tem tipo proprio; o resto
    // vem da natureza de operacao, como hoje).
    public static function tipoTitulo(NegocioParcela $np): int
    {
        $receber = $np->Negocio->codoperacao == Operacao::SAIDA;
        switch ($np->condicao) {
            case static::CONDICAO_PIX:
                return $receber ? TipoTituloService::TIPO_PIX_RECEBER : TipoTituloService::TIPO_PIX_PAGAR;
            case static::CONDICAO_ENTREGA:
                return $receber ? TipoTituloService::TIPO_ENTREGA_RECEBER : TipoTituloService::TIPO_ENTREGA_PAGAR;
        }
        return $np->Negocio->NaturezaOperacao->codtipotitulo;
    }

    // Gera o titulo de cada parcela ainda sem titulo. Numero N00000000-1/3,
    // com sufixo -A, -B... quando o negocio tem mais de uma forma a prazo
    // (uuidforma), -DEV no vale da devolucao. Devolve a soma das parcelas.
    public static function gerarTitulos(Negocio $negocio, ?string $numeroFixo = null): float
    {
        $parcelas = $negocio->NegocioParcelaS()
            ->orderBy('codnegocioparcela')
            ->get();
        $grupos = $parcelas->groupBy(fn($np) => $np->uuidforma ?? $np->uuid);
        $sufixo = ($grupos->count() > 1) ? 'A' : null;
        $total = 0;
        foreach ($grupos as $grupo) {
            $qtd = $grupo->count();
            foreach ($grupo as $np) {
                $total += $np->valor;
                if (!empty($np->codtitulo)) {
                    continue;
                }
                $numero = $numeroFixo ?? ('N' . str_pad($negocio->codnegocio, 8, '0', STR_PAD_LEFT)
                    . ($sufixo ? "-{$sufixo}" : '') . "-{$np->numero}/{$qtd}");
                $titulo = new Titulo();
                $titulo->codnegocioparcela = $np->codnegocioparcela;
                $titulo->codfilial = $negocio->codfilial;
                $titulo->codtipotitulo = static::tipoTitulo($np);
                $titulo->codcontacontabil = $negocio->NaturezaOperacao->codcontacontabil;
                $titulo->valor = ($negocio->codoperacao == Operacao::SAIDA) ? $np->valor : -$np->valor;
                $titulo->boleto = false;
                $titulo->codpessoa = $negocio->codpessoa;
                $titulo->numero = $numero;
                $titulo->emissao = Carbon::now();
                $titulo->transacao = $titulo->emissao;
                $titulo->vencimento = $np->vencimento;
                $titulo->vencimentooriginal = $np->vencimento;
                $titulo->gerencial = true;
                $titulo->codportador = Portador::CARTEIRA;
                TituloService::implantar($titulo);
                $np->codtitulo = $titulo->codtitulo;
                $np->save();
            }
            if ($sufixo) {
                $sufixo = chr(ord($sufixo) + 1);
            }
        }
        return round($total, 2);
    }

    // Titulos das parcelas do negocio
    public static function titulos(Negocio $negocio)
    {
        return Titulo::whereIn('codnegocioparcela', $negocio->NegocioParcelaS()->select('codnegocioparcela'))
            ->orderBy('vencimento')
            ->orderBy('codtitulo')
            ->get();
    }
}
