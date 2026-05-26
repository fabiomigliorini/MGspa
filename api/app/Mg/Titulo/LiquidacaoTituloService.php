<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LiquidacaoTituloService
{
    public static function listar(array $filtros)
    {
        $q = LiquidacaoTitulo::query()
            ->select('tblliquidacaotitulo.*')
            ->with([
                'Pessoa:codpessoa,fantasia',
                'Portador:codportador,portador,codfilial',
                'UsuarioCriacao:codusuario,usuario',
            ]);

        $q->join('tblpessoa as p', 'p.codpessoa', '=', 'tblliquidacaotitulo.codpessoa');

        if (array_key_exists('filiais_permitidas', $filtros) && $filtros['filiais_permitidas'] !== null) {
            $q->join('tblportador as pt', 'pt.codportador', '=', 'tblliquidacaotitulo.codportador');
            $filiais = $filtros['filiais_permitidas'];
            if (empty($filiais)) {
                $q->whereRaw('1 = 0');
            } else {
                $q->whereIn('pt.codfilial', $filiais);
            }
        }

        if (!empty($filtros['codliquidacaotitulo'])) {
            $q->where('tblliquidacaotitulo.codliquidacaotitulo', preg_replace('/[^0-9]/', '', (string)$filtros['codliquidacaotitulo']));
        }
        if (!empty($filtros['codpessoa'])) {
            $q->where('tblliquidacaotitulo.codpessoa', $filtros['codpessoa']);
        }
        if (!empty($filtros['codgrupoeconomico'])) {
            $q->where('p.codgrupoeconomico', $filtros['codgrupoeconomico']);
        }
        if (!empty($filtros['codgrupocliente'])) {
            $valores = is_array($filtros['codgrupocliente'])
                ? $filtros['codgrupocliente'] : [$filtros['codgrupocliente']];
            $q->where(function ($w) use ($valores) {
                $semGrupo = false;
                $cods = [];
                foreach ($valores as $v) {
                    if ((int)$v === -1) $semGrupo = true;
                    else $cods[] = $v;
                }
                if (!empty($cods)) $w->whereIn('p.codgrupocliente', $cods);
                if ($semGrupo) $w->orWhereNull('p.codgrupocliente');
            });
        }
        if (!empty($filtros['codportador'])) {
            $q->where('tblliquidacaotitulo.codportador', $filtros['codportador']);
        }
        if (!empty($filtros['codusuariocriacao'])) {
            $q->where('tblliquidacaotitulo.codusuariocriacao', $filtros['codusuariocriacao']);
        }

        $estornado = $filtros['estornado'] ?? '0';
        if ((string)$estornado === '0') {
            $q->whereNull('tblliquidacaotitulo.estornado');
        } elseif ((string)$estornado === '1') {
            $q->whereNotNull('tblliquidacaotitulo.estornado');
        }

        foreach ([
            'criacao_de'   => ['tblliquidacaotitulo.criacao', '>=', 'startOfDay'],
            'criacao_ate'  => ['tblliquidacaotitulo.criacao', '<=', 'endOfDay'],
            'transacao_de' => ['tblliquidacaotitulo.transacao', '>=', 'startOfDay'],
            'transacao_ate'=> ['tblliquidacaotitulo.transacao', '<=', 'endOfDay'],
        ] as $key => [$col, $op, $bound]) {
            if (!empty($filtros[$key])) {
                $q->where($col, $op, Carbon::parse($filtros[$key])->{$bound}()->format('Y-m-d H:i:s'));
            }
        }

        $q->orderBy('tblliquidacaotitulo.transacao', 'desc')
          ->orderBy('tblliquidacaotitulo.criacao', 'desc')
          ->orderBy('tblliquidacaotitulo.codliquidacaotitulo', 'desc');

        return $q->paginate(50);
    }

    public static function carregar(int $id): LiquidacaoTitulo
    {
        return LiquidacaoTitulo::with([
            'Pessoa:codpessoa,fantasia',
            'Portador:codportador,portador,codfilial',
            'UsuarioCriacao:codusuario,usuario',
            'UsuarioAlteracao:codusuario,usuario',
            'MovimentoTituloS' => function ($q) {
                $q->orderBy('codmovimentotitulo')
                  ->with([
                      'Titulo:codtitulo,codpessoa,codfilial,numero,vencimento,fatura,nossonumero,boleto,gerencial,codportador',
                      'Titulo.Pessoa:codpessoa,fantasia',
                      'Titulo.Filial:codfilial,filial',
                      'Titulo.Portador:codportador,portador',
                      'TipoMovimentoTitulo:codtipomovimentotitulo,tipomovimentotitulo,estorno',
                  ]);
            },
        ])->findOrFail($id);
    }

    public static function criar(array $dados): LiquidacaoTitulo
    {
        if (empty($dados['titulos']) || !is_array($dados['titulos'])) {
            throw new \InvalidArgumentException('Selecione ao menos um título!');
        }

        $codpessoa = (int)$dados['codpessoa'];
        $codportador = (int)$dados['codportador'];
        $transacao = Carbon::parse($dados['transacao'])->format('Y-m-d');

        $debito = 0.0;
        $credito = 0.0;
        foreach ($dados['titulos'] as $t) {
            $titulo = Titulo::findOrFail((int)$t['codtitulo']);
            self::validarLinha($titulo, $t);
            $total = (float)$t['total'];
            if ($total <= 0) {
                throw new \InvalidArgumentException("Total do título {$titulo->codtitulo} deve ser maior que zero!");
            }
            if (MovimentoTituloHelper::operacao($titulo) === 'CR') {
                $credito += $total;
            } else {
                $debito += $total;
            }
        }

        $liq = new LiquidacaoTitulo([
            'codpessoa'   => $codpessoa,
            'codportador' => $codportador,
            'transacao'   => $transacao,
            'observacao'  => $dados['observacao'] ?? null,
            'debito'      => $debito,
            'credito'     => $credito,
            'sistema'     => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        $liq->save();

        foreach ($dados['titulos'] as $t) {
            $titulo = Titulo::findOrFail((int)$t['codtitulo']);
            MovimentoTituloHelper::adicionarMultaJurosDesconto(
                $titulo,
                (float)($t['multa'] ?? 0),
                (float)($t['juros'] ?? 0),
                (float)($t['desconto'] ?? 0),
                $transacao,
                $codportador,
                null,
                $liq->codliquidacaotitulo
            );
            MovimentoTituloHelper::liquidar(
                $titulo,
                (float)$t['total'],
                $transacao,
                $codportador,
                null,
                $liq->codliquidacaotitulo
            );
        }

        return self::carregar($liq->codliquidacaotitulo);
    }

    public static function atualizar(LiquidacaoTitulo $liq, array $dados): LiquidacaoTitulo
    {
        if (!empty($liq->estornado)) {
            throw new \Exception('Liquidação estornada, não pode ser editada!', 1);
        }
        if (!empty($liq->codperiodo)) {
            throw new \Exception('Liquidação fechada em um período de RH. Não é possível editar.', 1);
        }

        $codpessoa   = (int)$dados['codpessoa'];
        $codportador = (int)$dados['codportador'];
        $transacao   = Carbon::parse($dados['transacao'])->format('Y-m-d');

        $mudouPortador  = (int)$liq->codportador !== $codportador;
        $mudouTransacao = Carbon::parse($liq->transacao)->format('Y-m-d') !== $transacao;

        $liq->codpessoa   = $codpessoa;
        $liq->codportador = $codportador;
        $liq->transacao   = $transacao;
        $liq->observacao  = $dados['observacao'] ?? null;
        $liq->save();

        if ($mudouPortador || $mudouTransacao) {
            foreach ($liq->MovimentoTituloS as $mov) {
                if ($mudouPortador) {
                    $mov->codportador = $codportador;
                }
                if ($mudouTransacao) {
                    $mov->transacao = $transacao;
                }
                $mov->save();
            }
        }

        return self::carregar($liq->codliquidacaotitulo);
    }

    public static function estornar(LiquidacaoTitulo $liq): LiquidacaoTitulo
    {
        if (!empty($liq->estornado)) {
            throw new \Exception('Liquidação já estornada!', 1);
        }
        if (!empty($liq->codperiodo)) {
            throw new \Exception('Liquidação fechada em um período de RH. Não é possível estornar.', 1);
        }

        foreach ($liq->MovimentoTituloS as $mov) {
            if (optional($mov->TipoMovimentoTitulo)->estorno) continue;
            MovimentoTituloService::estornar($mov);
        }

        $liq->estornado = Carbon::now()->format('Y-m-d H:i:s');
        $liq->codusuarioestorno = Auth::user()->codusuario;
        $liq->save();

        return self::carregar($liq->codliquidacaotitulo);
    }

    private static function validarLinha(Titulo $titulo, array $t): void
    {
        $saldo = (float)($t['saldo'] ?? 0);
        $multa = (float)($t['multa'] ?? 0);
        $juros = (float)($t['juros'] ?? 0);
        $desconto = (float)($t['desconto'] ?? 0);
        $total = (float)($t['total'] ?? 0);

        $calculado = $saldo + $multa + $juros - $desconto;
        if (abs($calculado - $total) > 0.005) {
            throw new \InvalidArgumentException(
                "Total incorreto para o título #{$titulo->codtitulo}! ({$calculado} != {$total})"
            );
        }

        if ($saldo > abs((float)$titulo->saldo) + 0.005) {
            throw new \InvalidArgumentException(
                "Saldo informado ({$saldo}) maior que saldo atual do título #{$titulo->codtitulo}!"
            );
        }
    }
}
