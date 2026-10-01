<?php

namespace Mg\Pagamento;

use Carbon\Carbon;
use Mg\Portador\Portador;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\MovimentoTituloHelper;
use Mg\Titulo\MovimentoTituloService;
use Mg\Titulo\Titulo;

/**
 * Recebimento e pagamento de titulos (M6 do plano doc-3): o pagamento no
 * lugar da liquidacao. Um pagamento por forma (portador + meio), uma linha
 * de movimento por titulo com principal, juros, multa, desconto e total;
 * juros, multa e desconto do pagamento = soma das linhas, total = o
 * dinheiro que andou. Encontro de contas sem dinheiro = total zero, meio
 * compensacao, sem portador. Sem transacao interna.
 */
class PagamentoTituloService
{
    // portadores que viraram meio do pagamento (inativos, so' historico)
    const MEIO_DO_PSEUDOPORTADOR = [
        202018 => PagamentoService::MEIO_FOLHA,       // Acerto Folha Salarial
        202007 => PagamentoService::MEIO_PERMUTA,     // Barter
        202035 => PagamentoService::MEIO_PERDA,       // Perda por Prazo
        202016 => PagamentoService::MEIO_COMPENSACAO, // Programacao Pagamentos
        202053 => PagamentoService::MEIO_COMPENSACAO, // Cred Pis/Cofins
    ];

    // meios que o contas pode escolher no lugar do derivado do portador
    const MEIOS_CONTAS = [
        PagamentoService::MEIO_DINHEIRO,
        PagamentoService::MEIO_CHEQUE,
        PagamentoService::MEIO_CREDITO,
        PagamentoService::MEIO_DEBITO,
        PagamentoService::MEIO_BOLETO,
        PagamentoService::MEIO_DEPOSITO,
        PagamentoService::MEIO_PIX,
        PagamentoService::MEIO_TRANSFERENCIA,
        PagamentoService::MEIO_OUTROS,
    ];

    // Meio pelo tipo do portador: especie -> dinheiro, banco e adquirente ->
    // transferencia, cartao da empresa -> credito
    public static function meioDoPortador(?Portador $portador): int
    {
        if (!$portador) {
            return PagamentoService::MEIO_COMPENSACAO;
        }
        if (isset(static::MEIO_DO_PSEUDOPORTADOR[$portador->codportador])) {
            return static::MEIO_DO_PSEUDOPORTADOR[$portador->codportador];
        }
        switch ($portador->tipo) {
            case Portador::TIPO_ESPECIE:
                return PagamentoService::MEIO_DINHEIRO;
            case Portador::TIPO_BANCO:
            case Portador::TIPO_ADQUIRENTE:
                return PagamentoService::MEIO_TRANSFERENCIA;
            case Portador::TIPO_CARTAO:
                return PagamentoService::MEIO_CREDITO;
        }
        return PagamentoService::MEIO_OUTROS;
    }

    // Pagamentos que movimentam titulo (fora da venda)
    public static function query()
    {
        return Pagamento::query()
            ->whereNull('tblpagamento.codnegocio')
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('tblmovimentotitulo as mt')
                    ->whereColumn('mt.codpagamento', 'tblpagamento.codpagamento');
            });
    }

    public static function filtrar($q, array $filtros)
    {
        $q->join('tblpessoa as p', 'p.codpessoa', '=', 'tblpagamento.codpessoa');

        if (array_key_exists('filiais_permitidas', $filtros) && $filtros['filiais_permitidas'] !== null) {
            $filiais = $filtros['filiais_permitidas'];
            if (empty($filiais)) {
                $q->whereRaw('1 = 0');
            } else {
                $q->whereIn('tblpagamento.codfilial', $filiais);
            }
        }
        if (!empty($filtros['codpagamento'])) {
            $cod = preg_replace('/[^0-9]/', '', (string) $filtros['codpagamento']);
            $q->where(function ($w) use ($cod) {
                $w->where('tblpagamento.codpagamento', $cod)
                    ->orWhere('tblpagamento.codliquidacaotituloantigo', $cod);
            });
        }
        if (!empty($filtros['codpessoa'])) {
            $q->where('tblpagamento.codpessoa', $filtros['codpessoa']);
        }
        if (!empty($filtros['codgrupoeconomico'])) {
            $q->where('p.codgrupoeconomico', $filtros['codgrupoeconomico']);
        }
        if (!empty($filtros['codgrupocliente'])) {
            $valores = is_array($filtros['codgrupocliente']) ? $filtros['codgrupocliente'] : [$filtros['codgrupocliente']];
            $q->where(function ($w) use ($valores) {
                $cods = array_filter($valores, fn($v) => (int) $v !== -1);
                if (!empty($cods)) {
                    $w->whereIn('p.codgrupocliente', $cods);
                }
                if (count($cods) != count($valores)) {
                    $w->orWhereNull('p.codgrupocliente');
                }
            });
        }
        if (!empty($filtros['codportador'])) {
            $q->where(function ($w) use ($filtros) {
                $w->where('tblpagamento.codportadordestino', $filtros['codportador'])
                    ->orWhere('tblpagamento.codportadororigem', $filtros['codportador']);
            });
        }
        if (!empty($filtros['meio'])) {
            $q->whereIn('tblpagamento.meio', (array) $filtros['meio']);
        }
        // R = recebimento (entrou dinheiro), P = pagamento (saiu), C = compensacao
        if (!empty($filtros['sentido'])) {
            switch ($filtros['sentido']) {
                case 'R':
                    $q->whereNotNull('tblpagamento.codportadordestino');
                    break;
                case 'P':
                    $q->whereNotNull('tblpagamento.codportadororigem')->whereNull('tblpagamento.codportadordestino');
                    break;
                case 'C':
                    $q->where('tblpagamento.meio', PagamentoService::MEIO_COMPENSACAO);
                    break;
            }
        }
        if (!empty($filtros['codusuariocriacao'])) {
            $q->where('tblpagamento.codusuariocriacao', $filtros['codusuariocriacao']);
        }
        $cancelado = $filtros['cancelado'] ?? '0';
        if ((string) $cancelado === '0') {
            $q->where('tblpagamento.estado', '!=', PagamentoService::ESTADO_CANCELADO);
        } elseif ((string) $cancelado === '1') {
            $q->where('tblpagamento.estado', PagamentoService::ESTADO_CANCELADO);
        }
        foreach ([
            'criacao_de' => ['tblpagamento.criacao', '>=', 'startOfDay'],
            'criacao_ate' => ['tblpagamento.criacao', '<=', 'endOfDay'],
            'lancamento_de' => ['tblpagamento.lancamento', '>=', 'startOfDay'],
            'lancamento_ate' => ['tblpagamento.lancamento', '<=', 'endOfDay'],
        ] as $key => [$col, $op, $bound]) {
            if (!empty($filtros[$key])) {
                $q->where($col, $op, Carbon::parse($filtros[$key])->{$bound}()->format('Y-m-d H:i:s'));
            }
        }
        return $q;
    }

    public static function listar(array $filtros)
    {
        $q = static::query()
            ->select('tblpagamento.*')
            ->with([
                'Pessoa:codpessoa,fantasia',
                'PortadorDestino:codportador,portador,codfilial',
                'PortadorOrigem:codportador,portador,codfilial',
                'UsuarioCriacao:codusuario,usuario',
            ]);
        static::filtrar($q, $filtros);
        $q->orderBy('tblpagamento.lancamento', 'desc')
            ->orderBy('tblpagamento.criacao', 'desc')
            ->orderBy('tblpagamento.codpagamento', 'desc');
        return $q->paginate(50);
    }

    public static function carregar(int $id): Pagamento
    {
        return static::query()->with([
            'Pessoa',
            'PortadorDestino:codportador,portador,codfilial,tipo',
            'PortadorOrigem:codportador,portador,codfilial,tipo',
            'UsuarioCriacao:codusuario,usuario',
            'UsuarioAlteracao:codusuario,usuario',
            'MovimentoTituloS' => function ($q) {
                $q->orderBy('codmovimentotitulo')
                    ->with([
                        'Titulo:codtitulo,codpessoa,codfilial,numero,vencimento,fatura,nossonumero,boleto,gerencial,codportador,codtituloagrupamento,valor,saldo',
                        'Titulo.Pessoa:codpessoa,fantasia',
                        'Titulo.Filial:codfilial,filial',
                        'Titulo.Portador:codportador,portador',
                        'TipoMovimentoTitulo:codtipomovimentotitulo,tipomovimentotitulo',
                    ]);
            },
        ])->findOrFail($id);
    }

    // Recebimento: entra dinheiro (ou encontro de contas sem dinheiro)
    public static function receber(array $dados): Pagamento
    {
        return static::registrar($dados, false);
    }

    // Pagamento: sai dinheiro
    public static function pagar(array $dados): Pagamento
    {
        return static::registrar($dados, true);
    }

    // Liquido dos titulos com o sinal do movimento: negativo = entra
    // dinheiro (recebe mais do que paga), positivo = sai
    public static function liquido(array $titulos): float
    {
        $liquido = 0;
        foreach ($titulos as $t) {
            $titulo = Titulo::findOrFail((int) $t['codtitulo']);
            $liquido += $titulo->ehReceber() ? -(float) $t['total'] : (float) $t['total'];
        }
        return round($liquido, 2);
    }

    protected static function registrar(array $dados, bool $saida): Pagamento
    {
        if (empty($dados['titulos']) || !is_array($dados['titulos'])) {
            abort(422, 'Selecione ao menos um título!');
        }
        $transacao = Carbon::parse($dados['transacao'])->startOfDay();

        $liquido = 0;
        $juros = 0;
        $multa = 0;
        $desconto = 0;
        $codfilial = null;
        foreach ($dados['titulos'] as $t) {
            $titulo = Titulo::findOrFail((int) $t['codtitulo']);
            static::validarLinha($titulo, $t);
            if ((float) $t['total'] <= 0 && (float) ($t['desconto'] ?? 0) <= 0) {
                abort(422, "Total do título {$titulo->numero} deve ser maior que zero!");
            }
            $liquido += $titulo->ehReceber() ? -(float) $t['total'] : (float) $t['total'];
            $juros += (float) ($t['juros'] ?? 0);
            $multa += (float) ($t['multa'] ?? 0);
            $desconto += (float) ($t['desconto'] ?? 0);
            $codfilial = $codfilial ?? $titulo->codfilial;
        }
        $liquido = round($liquido, 2);
        $compensacao = abs($liquido) < 0.005;
        if ($saida && $liquido <= 0) {
            abort(422, 'Os títulos não somam um pagamento (sai dinheiro)!');
        }
        if (!$saida && $liquido > 0) {
            abort(422, 'Os títulos não somam um recebimento (entra dinheiro)!');
        }

        // portador: obrigatorio quando anda dinheiro; gaveta so' pelo PDV
        $portador = null;
        if (!$compensacao) {
            if (empty($dados['codportador'])) {
                abort(422, 'Informe o portador!');
            }
            $portador = Portador::findOrFail((int) $dados['codportador']);
            if (!empty($portador->inativo)) {
                abort(422, "Portador {$portador->portador} inativo!");
            }
            if ($portador->ehGaveta()) {
                abort(422, 'No contas não se baixa título em gaveta de caixa: receba pelo PDV ou escolha cofre/banco.');
            }
        }

        $meio = static::meioDoPortador($portador);
        if (!$compensacao && !empty($dados['meio'])) {
            if (!in_array((int) $dados['meio'], static::MEIOS_CONTAS)) {
                abort(422, "Meio de pagamento {$dados['meio']} não pode ser escolhido aqui!");
            }
            $meio = (int) $dados['meio'];
        }

        // colunas do pagamento = soma das linhas; encontro misto que daria
        // principal negativo fica so' com o total
        $total = abs($liquido);
        $principal = round($total - $juros - $multa + $desconto, 2);
        if ($principal < 0 || ($principal == 0 && !$compensacao)) {
            $principal = $total;
            $juros = $multa = $desconto = 0;
        }

        $pag = PagamentoService::criar([
            'codportadororigem' => ($liquido > 0) ? $portador->codportador : null,
            'codportadordestino' => ($liquido < 0) ? $portador->codportador : null,
            'meio' => $compensacao ? PagamentoService::MEIO_COMPENSACAO : $meio,
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'principal' => $principal,
            'juros' => $juros,
            'multa' => $multa,
            'desconto' => $desconto,
            'lancamento' => $transacao,
            'efetivacao' => Carbon::now(),
            'codusuarioefetivacao' => auth()->user()->codusuario ?? null,
            'codpessoa' => (int) $dados['codpessoa'],
            'codfilial' => $portador->codfilial ?? $codfilial,
            'observacoes' => $dados['observacao'] ?? null,
            'codperiodocolaboradoracerto' => $dados['codperiodocolaboradoracerto'] ?? null,
        ]);

        foreach ($dados['titulos'] as $t) {
            MovimentoTituloHelper::liquidar(
                Titulo::findOrFail((int) $t['codtitulo']),
                (float) $t['total'],
                (float) ($t['juros'] ?? 0),
                (float) ($t['multa'] ?? 0),
                (float) ($t['desconto'] ?? 0),
                $transacao->format('Y-m-d'),
                $portador->codportador ?? null,
                null,
                $pag->codpagamento
            );
        }

        return static::carregar($pag->codpagamento);
    }

    // Pagamento de uma baixa feita pelo banco (boleto BB pela API, retorno
    // de boleto): um por linha de movimento, com as colunas dela; o
    // reprocessamento regrava o mesmo. Sentido pelo total do movimento
    // (negativo = entrou dinheiro no portador).
    public static function daBaixa(MovimentoTitulo $mov, int $meio, ?int $codportador, $lancamento): ?Pagamento
    {
        $total = abs((float) $mov->total);
        $principal = abs((float) $mov->principal);
        if ($total <= 0 || $principal <= 0) {
            return null;
        }
        $pag = $mov->Pagamento ?? new Pagamento();
        $entrada = (float) $mov->total < 0;
        $titulo = $mov->Titulo;
        PagamentoService::preencher($pag, [
            'codportadordestino' => $entrada ? $codportador : null,
            'codportadororigem' => $entrada ? null : $codportador,
            'meio' => $meio,
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'principal' => $principal,
            'juros' => (float) $mov->juros,
            'multa' => (float) $mov->multa,
            'desconto' => (float) $mov->desconto,
            'lancamento' => Carbon::parse($lancamento ?? $mov->transacao),
            'efetivacao' => $pag->efetivacao ?? Carbon::now(),
            'codpessoa' => $titulo->codpessoa,
            'codfilial' => $titulo->codfilial,
        ]);
        $pag->save();
        if ($mov->codpagamento != $pag->codpagamento) {
            $mov->codpagamento = $pag->codpagamento;
            $mov->save();
        }
        return $pag;
    }

    // Corrige pessoa, portador, meio, data e observacao, como a liquidacao
    // permitia (decisao do Fabio, 01/10/2026: excecao a regra de o pagamento
    // nao mudar). Valores e titulos nao mudam: para isso, estorna e lanca de
    // novo. O portador e a data vao junto para as linhas do movimento.
    public static function atualizar(Pagamento $pag, array $dados): Pagamento
    {
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            abort(422, 'Pagamento estornado não pode ser alterado!');
        }
        if (!empty($pag->codnegocio)) {
            abort(422, 'Pagamento de venda: altere pelo negócio.');
        }
        if (!empty($pag->codperiodocolaboradoracerto)) {
            abort(422, 'Pagamento de acerto de RH: altere pelo acerto.');
        }
        foreach ($pag->MovimentoTituloS as $mov) {
            if (!empty($mov->codtituloboleto) || !empty($mov->codboletoretorno)) {
                abort(422, 'Baixa de boleto pelo banco não é alterada aqui.');
            }
        }
        $transacao = Carbon::parse($dados['transacao'])->startOfDay();
        $mudouData = $pag->lancamento->format('Y-m-d') != $transacao->format('Y-m-d');

        // compensacao (sem dinheiro) continua sem portador
        $codportador = null;
        if ($pag->meio != PagamentoService::MEIO_COMPENSACAO || !empty($pag->codportadororigem) || !empty($pag->codportadordestino)) {
            if (empty($dados['codportador'])) {
                abort(422, 'Informe o portador!');
            }
            $portador = Portador::findOrFail((int) $dados['codportador']);
            $atual = $pag->codportadordestino ?? $pag->codportadororigem;
            if ($portador->codportador != $atual) {
                if (!empty($portador->inativo)) {
                    abort(422, "Portador {$portador->portador} inativo!");
                }
                if ($portador->ehGaveta()) {
                    abort(422, 'No contas não se baixa título em gaveta de caixa: escolha cofre ou banco.');
                }
            }
            $codportador = $portador->codportador;
            $meio = !empty($dados['meio']) ? (int) $dados['meio'] : ($portador->codportador != $atual ? static::meioDoPortador($portador) : $pag->meio);
            if ($meio != $pag->meio && !in_array($meio, static::MEIOS_CONTAS)) {
                abort(422, "Meio de pagamento {$meio} não pode ser escolhido aqui!");
            }
            $pag->meio = $meio;
            if (!empty($pag->codportadordestino)) {
                $pag->codportadordestino = $codportador;
            } else {
                $pag->codportadororigem = $codportador;
            }
            $pag->codfilial = $portador->codfilial ?? $pag->codfilial;
        }
        $pag->codpessoa = (int) $dados['codpessoa'];
        $pag->lancamento = $transacao;
        $pag->observacoes = $dados['observacao'] ?? null;
        PagamentoService::validar($pag);
        $pag->save();

        foreach ($pag->MovimentoTituloS as $mov) {
            $mov->codportador = $codportador;
            $mov->transacao = $transacao->format('Y-m-d');
            $mov->save();
            // a data de liquidacao do titulo sai da transacao dos movimentos
            if ($mudouData) {
                MovimentoTituloService::recalcular($mov->Titulo);
            }
        }
        return static::carregar($pag->codpagamento);
    }

    // Estornar desfaz o pagamento inteiro: estorna cada linha de baixa e
    // cancela o pagamento (com justificativa)
    public static function estornar(Pagamento $pag, string $justificativa): Pagamento
    {
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            abort(422, 'Pagamento já estornado!');
        }
        if (!empty($pag->codnegocio)) {
            abort(422, 'Pagamento de venda: cancele pelo negócio.');
        }
        if (!empty($pag->codperiodocolaboradoracerto)) {
            abort(422, 'Pagamento de acerto de RH: estorne pelo acerto.');
        }
        foreach ($pag->MovimentoTituloS as $mov) {
            if (!empty($mov->codtituloboleto)) {
                abort(422, 'Baixa de boleto pelo banco não é estornada aqui.');
            }
        }
        foreach ($pag->MovimentoTituloS as $mov) {
            if ($mov->ehEstorno() || $mov->MovimentoTituloEstornoS()->exists()) {
                continue;
            }
            MovimentoTituloService::estornar($mov);
        }
        PagamentoService::cancelar($pag, $justificativa);
        return static::carregar($pag->codpagamento);
    }

    // Linhas de baixa (sem os estornos)
    public static function baixas(Pagamento $pag)
    {
        return $pag->MovimentoTituloS
            ->filter(fn(MovimentoTitulo $m) => !$m->ehEstorno() && (int) $m->codtipomovimentotitulo < 900);
    }

    // Baixou titulo a receber? (principal negativo)
    public static function temRecebimento(Pagamento $pag): bool
    {
        return static::baixas($pag)->contains(fn($m) => (float) $m->principal < 0);
    }

    // Baixou titulo a pagar? (principal positivo)
    public static function temPagamento(Pagamento $pag): bool
    {
        return static::baixas($pag)->contains(fn($m) => (float) $m->principal > 0);
    }

    // Total com o sinal do movimento, como a liquidacao antiga: negativo =
    // recebeu, positivo = pagou
    public static function valor(Pagamento $pag): float
    {
        return round((float) static::baixas($pag)->sum('total'), 2);
    }

    private static function validarLinha(Titulo $titulo, array $t): void
    {
        $saldo = (float) ($t['saldo'] ?? 0);
        $multa = (float) ($t['multa'] ?? 0);
        $juros = (float) ($t['juros'] ?? 0);
        $desconto = (float) ($t['desconto'] ?? 0);
        $total = (float) ($t['total'] ?? 0);

        $calculado = $saldo + $multa + $juros - $desconto;
        if (abs($calculado - $total) > 0.005) {
            abort(422, "Total incorreto para o título {$titulo->numero}! ({$calculado} != {$total})");
        }
        if ($saldo > abs((float) $titulo->saldo) + 0.005) {
            abort(422, "Saldo informado ({$saldo}) maior que o saldo atual do título {$titulo->numero}!");
        }
    }
}
