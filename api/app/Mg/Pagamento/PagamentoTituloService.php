<?php

namespace Mg\Pagamento;

use Carbon\Carbon;
use Mg\Cheque\Cheque;
use Mg\Cheque\ChequeService;
use Mg\Cheque\Cmc7\Cmc7;
use Mg\Maquineta\Maquineta;
use Mg\Pdv\Pdv;
use Mg\Portador\LancamentoDataService;
use Mg\Portador\Portador;
use Mg\Portador\PortadorMovimentoService;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\MovimentoTituloHelper;
use Mg\Titulo\MovimentoTituloService;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloService;

/**
 * Recebimento e pagamento de titulos (M6 do plano doc-3, com varias formas
 * no M6.1): o pagamento no lugar da liquidacao. Um pagamento por forma; as
 * linhas dos titulos sao distribuidas pelos pagamentos por vencimento, uma
 * linha de movimento por titulo em cada pagamento, com principal, juros,
 * multa, desconto e total. Encontro de contas sem dinheiro = total zero,
 * meio compensacao, sem portador. O mesmo servico atende o contas e o PDV
 * (dinheiro na gaveta). Sem transacao interna.
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

    // meios que a edicao do contas pode escolher
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

    // meios que andam por conta de banco (so' no contas)
    const MEIOS_BANCO = [
        PagamentoService::MEIO_BOLETO,
        PagamentoService::MEIO_DEPOSITO,
        PagamentoService::MEIO_PIX,
        PagamentoService::MEIO_TRANSFERENCIA,
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

    // Portador (tipo A) da adquirente da maquineta: e' onde o cartao cai.
    // Parceiro sem portador (Brasil Card, Le Card...) fica sem destino.
    public static function portadorDaMaquineta(?Maquineta $maquineta): ?Portador
    {
        if (!$maquineta) {
            return null;
        }
        return Portador::where('tipo', Portador::TIPO_ADQUIRENTE)
            ->where('codpessoa', $maquineta->codpessoa)
            ->whereNull('inativo')
            ->orderBy('codportador')
            ->first();
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

    /**
     * Baixa os titulos com as formas informadas e devolve os pagamentos.
     *
     * $dados: codpessoa, transacao (com hora), observacao, titulos[] (codtitulo,
     * saldo, juros, multa, desconto, total) e pagamentos[] (meio, total,
     * codportador, codmaquineta, bandeira, autorizacao, parcelas, cheque,
     * codpagamento = cobranca integrada ja' confirmada, codpagamentoorigem =
     * cancelamento no cartao / devolucao de PIX). Titulos que se anulam nao
     * levam forma: viram um encontro de contas.
     *
     * $pdv: baixa feita no PDV (dinheiro na gaveta dele; sem banco).
     */
    public static function baixar(array $dados, ?Pdv $pdv = null): array
    {
        if (empty($dados['titulos']) || !is_array($dados['titulos'])) {
            abort(422, 'Selecione ao menos um título!');
        }
        // a data com hora (TASK-204), no contas e no PDV: o periodo sai dela
        $transacao = LancamentoDataService::dataInformada($dados['transacao'] ?? null);

        // linhas dos titulos, com o sinal do movimento (negativo = entra)
        $linhas = [];
        $liquido = 0;
        foreach ($dados['titulos'] as $t) {
            $titulo = Titulo::findOrFail((int) $t['codtitulo']);
            static::validarLinha($titulo, $t);
            if ((float) $t['total'] <= 0 && (float) ($t['desconto'] ?? 0) <= 0) {
                abort(422, "Total do título {$titulo->numero} deve ser maior que zero!");
            }
            $sinal = $titulo->ehReceber() ? -1 : 1;
            $linhas[] = [
                'titulo' => $titulo,
                'total' => round((float) $t['total'], 2),
                'juros' => round((float) ($t['juros'] ?? 0), 2),
                'multa' => round((float) ($t['multa'] ?? 0), 2),
                'desconto' => round((float) ($t['desconto'] ?? 0), 2),
                'sinal' => $sinal,
            ];
            $liquido += $sinal * (float) $t['total'];
        }
        $liquido = round($liquido, 2);
        $entrada = $liquido < 0;
        $compensacao = abs($liquido) < 0.005;

        // formas: a soma tem que ser o liquido; titulos que se anulam viram
        // um pagamento de compensacao sem dinheiro
        $formas = array_values($dados['pagamentos'] ?? []);
        if ($compensacao) {
            if ($pdv) {
                abort(422, 'Os títulos se anulam: faça o encontro de contas pelo financeiro.');
            }
            $formas = [['meio' => PagamentoService::MEIO_COMPENSACAO, 'total' => 0]];
        } else {
            if (empty($formas)) {
                abort(422, 'Informe como foi pago!');
            }
            $soma = 0;
            foreach ($formas as $f) {
                if ((float) ($f['total'] ?? 0) <= 0) {
                    abort(422, 'O valor de cada forma precisa ser maior que zero!');
                }
                $soma += (float) $f['total'];
            }
            if (abs(round($soma, 2) - abs($liquido)) > 0.005) {
                $s = number_format($soma, 2, ',', '.');
                $l = number_format(abs($liquido), 2, ',', '.');
                abort(422, "A soma das formas ({$s}) não bate com o líquido dos títulos ({$l})!");
            }
        }

        $partes = static::distribuir($linhas, $formas, $liquido);

        $codfilialTitulos = $linhas[0]['titulo']->codfilial;
        $pagamentos = [];
        foreach ($formas as $i => $forma) {
            $somaPartes = static::somarPartes($partes[$i]);
            $pag = static::pagamentoDaForma($forma, $entrada, $compensacao, $somaPartes, $dados, $transacao, $pdv, $codfilialTitulos);
            foreach ($partes[$i] as $parte) {
                MovimentoTituloHelper::liquidar(
                    $parte['titulo'],
                    $parte['total'],
                    $parte['juros'],
                    $parte['multa'],
                    $parte['desconto'],
                    $transacao->format('Y-m-d'),
                    $pag->codportadordestino ?? $pag->codportadororigem,
                    null,
                    $pag->codpagamento
                );
            }
            if ($entrada && $pag->meio == PagamentoService::MEIO_CHEQUE) {
                static::gerarCheque($pag);
            }
            $pagamentos[] = $pag;
        }

        return array_map(fn($p) => PagamentoListaService::carregar($p->codpagamento), $pagamentos);
    }

    // Distribui as linhas pelas formas: as do sentido do liquido em ordem de
    // vencimento, enchendo cada forma; as do sentido contrario (vale contra
    // notinha) inteiras na primeira, que comporta o valor delas a mais. Uma
    // linha que cai entre duas formas e' dividida, com juros, multa e
    // desconto proporcionais.
    protected static function distribuir(array $linhas, array $formas, float $liquido): array
    {
        $partes = array_fill(0, count($formas), []);
        $capacidade = array_map(fn($f) => round((float) ($f['total'] ?? 0), 2), $formas);
        $sentido = $liquido < 0 ? -1 : 1;

        $maioria = [];
        foreach ($linhas as $l) {
            if (abs($liquido) < 0.005 || $l['total'] <= 0 || $l['sinal'] != $sentido) {
                $partes[0][] = $l;
                if (abs($liquido) >= 0.005) {
                    $capacidade[0] = round($capacidade[0] + $l['total'], 2);
                }
                continue;
            }
            $maioria[] = $l;
        }
        usort($maioria, fn($a, $b) => [$a['titulo']->vencimento, $a['titulo']->codtitulo] <=> [$b['titulo']->vencimento, $b['titulo']->codtitulo]);

        $i = 0;
        foreach ($maioria as $l) {
            $resta = $l['total'];
            $pedacos = [];
            while ($resta > 0.005) {
                while ($i < count($formas) - 1 && $capacidade[$i] <= 0.005) {
                    $i++;
                }
                $valor = ($i == count($formas) - 1) ? $resta : min($resta, $capacidade[$i]);
                $pedacos[] = [$i, round($valor, 2)];
                $capacidade[$i] = round($capacidade[$i] - $valor, 2);
                $resta = round($resta - $valor, 2);
            }
            // juros, multa e desconto proporcionais; o ultimo pedaco leva a sobra
            $acum = ['juros' => 0, 'multa' => 0, 'desconto' => 0];
            foreach ($pedacos as $k => [$idx, $valor]) {
                $parte = $l;
                $parte['total'] = $valor;
                foreach (['juros', 'multa', 'desconto'] as $col) {
                    $parte[$col] = ($k == count($pedacos) - 1)
                        ? round($l[$col] - $acum[$col], 2)
                        : round($l[$col] * $valor / $l['total'], 2);
                    $acum[$col] = round($acum[$col] + $parte[$col], 2);
                }
                $partes[$idx][] = $parte;
            }
        }
        return $partes;
    }

    // colunas do pagamento = soma das linhas; encontro misto que daria
    // principal negativo fica so' com o total
    protected static function somarPartes(array $partes): array
    {
        $soma = ['juros' => 0, 'multa' => 0, 'desconto' => 0];
        foreach ($partes as $p) {
            foreach ($soma as $col => $v) {
                $soma[$col] = round($v + $p[$col], 2);
            }
        }
        return $soma;
    }

    // Cria o pagamento de uma forma (ou amarra a cobranca integrada ja'
    // confirmada), com origem e destino pelo meio e por onde aconteceu. Usado
    // tambem pelo lancamento de vale/adiantamento (TituloAdiantamentoService).
    public static function pagamentoDaForma(
        array $forma,
        bool $entrada,
        bool $compensacao,
        array $soma,
        array $dados,
        Carbon $transacao,
        ?Pdv $pdv,
        int $codfilialTitulos
    ): Pagamento {
        $total = round((float) ($forma['total'] ?? 0), 2);
        $principal = round($total - $soma['juros'] - $soma['multa'] + $soma['desconto'], 2);
        $valores = ['principal' => $principal] + $soma;
        if ($principal < 0 || ($principal == 0 && !$compensacao)) {
            $valores = ['principal' => $total, 'juros' => 0, 'multa' => 0, 'desconto' => 0];
        }
        $comum = [
            'codpessoa' => (int) $dados['codpessoa'],
            'observacoes' => $dados['observacao'] ?? null,
        ];

        // cobranca integrada (PIX QR, Stone, SafraPay): o pagamento nasceu na
        // confirmacao, sem titulo; aqui so' ganha a pessoa e os valores
        if (!empty($forma['codpagamento'])) {
            $pag = Pagamento::lockForUpdate()->findOrFail((int) $forma['codpagamento']);
            if ($pag->estado != PagamentoService::ESTADO_EFETIVADO || !empty($pag->codnegocio) || $pag->MovimentoTituloS()->exists()) {
                abort(422, "O pagamento {$pag->codpagamento} não está disponível para baixar títulos!");
            }
            if (abs($pag->total - $total) > 0.005) {
                abort(422, "O valor do pagamento {$pag->codpagamento} não confere com a forma!");
            }
            PagamentoService::preencher($pag, $comum + $valores);
            $pag->save();
            PortadorMovimentoService::sincronizar($pag);
            return $pag;
        }

        $meio = (int) ($forma['meio'] ?? 0);
        $base = $comum + $valores + [
            'meio' => $meio,
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'transacao' => $transacao,
            'efetivacao' => Carbon::now(),
            'codusuarioefetivacao' => auth()->user()->codusuario ?? null,
            'codpdv' => $pdv->codpdv ?? null,
            'codfilial' => $pdv->codfilial ?? $codfilialTitulos,
        ];

        if ($compensacao || $meio == PagamentoService::MEIO_COMPENSACAO) {
            if ($pdv) {
                abort(422, 'Compensação não é feita no PDV!');
            }
            return PagamentoService::criar(array_merge($base, ['meio' => PagamentoService::MEIO_COMPENSACAO]));
        }

        // cancelamento no cartao / devolucao de PIX: contrario do original
        if (!empty($forma['codpagamentoorigem'])) {
            if ($entrada) {
                abort(422, 'Cancelamento no cartão e devolução de PIX são só para pagar crédito!');
            }
            $original = Pagamento::findOrFail((int) $forma['codpagamentoorigem']);
            if (!in_array($original->meio, [PagamentoService::MEIO_CREDITO, PagamentoService::MEIO_DEBITO, PagamentoService::MEIO_PIX])) {
                abort(422, 'Só se registra cancelamento de cartão ou devolução de PIX!');
            }
            $origem = ($original->meio == PagamentoService::MEIO_PIX)
                ? $original->codportadordestino
                : (static::portadorDaMaquineta($original->Maquineta)->codportador ?? $original->codportadordestino);
            return PagamentoService::contrario($original, array_merge($base, [
                'meio' => $original->meio,
                'codnegocio' => null,
                'codportadororigem' => $origem,
                'codportadordestino' => null,
                'parcelas' => null,
            ]));
        }

        $portador = static::portadorDaForma($forma, $meio, $entrada, $pdv);
        $campos = [
            'codportadordestino' => $entrada ? ($portador->codportador ?? null) : null,
            'codportadororigem' => $entrada ? null : ($portador->codportador ?? null),
            'codfilial' => $pdv->codfilial ?? $portador->codfilial ?? $codfilialTitulos,
        ];

        if (in_array($meio, PagamentoService::MEIOS_CARTAO) && $entrada) {
            $campos += [
                'codmaquineta' => (int) $forma['codmaquineta'],
                'bandeira' => $forma['bandeira'] ?? null,
                'autorizacao' => $forma['autorizacao'] ?? null,
                'parcelas' => $forma['parcelas'] ?? null,
            ];
        }
        if ($meio == PagamentoService::MEIO_DINHEIRO && $entrada) {
            $campos['valortroco'] = $forma['valortroco'] ?? null;
        }
        if ($meio == PagamentoService::MEIO_CHEQUE) {
            $campos += [
                'cmc7' => $forma['cmc7'] ?? null,
                'chequevencimento' => $forma['chequevencimento'] ?? null,
                'chequecnpj' => $forma['chequecnpj'] ?? null,
                'chequeemitente' => $forma['chequeemitente'] ?? null,
            ];
        }
        return PagamentoService::criar(array_merge($base, $campos));
    }

    // Portador da forma: dinheiro na gaveta do PDV (no contas, cofre/troco/
    // Caixa Financeiro escolhido); cheque recebido na Carteira; cartao na
    // adquirente da maquineta; banco e cartao da empresa escolhidos no contas
    protected static function portadorDaForma(array $forma, int $meio, bool $entrada, ?Pdv $pdv): ?Portador
    {
        $escolhido = !empty($forma['codportador']) ? Portador::findOrFail((int) $forma['codportador']) : null;
        if ($escolhido && !empty($escolhido->inativo)) {
            abort(422, "Portador {$escolhido->portador} inativo!");
        }
        if ($escolhido && !$pdv && $escolhido->ehGaveta()) {
            abort(422, 'No contas não se baixa título em gaveta de caixa: receba pelo PDV ou escolha cofre/banco.');
        }

        switch ($meio) {
            case PagamentoService::MEIO_DINHEIRO:
                if ($pdv) {
                    if (empty($pdv->codportador)) {
                        abort(422, 'PDV sem gaveta: vincule o portador em Config → PDV.');
                    }
                    return Portador::findOrFail($pdv->codportador);
                }
                if (!$escolhido || $escolhido->tipo != Portador::TIPO_ESPECIE) {
                    abort(422, 'Escolha o cofre, troco ou caixa do dinheiro!');
                }
                return $escolhido;

            case PagamentoService::MEIO_CHEQUE:
                if (empty($forma['cmc7']) || !(new Cmc7($forma['cmc7']))->valido()) {
                    abort(422, 'CMC7 do cheque inválido!');
                }
                if (empty($forma['chequevencimento'])) {
                    abort(422, 'Informe a data do cheque (bom para)!');
                }
                if ($entrada) {
                    return $escolhido ?? Portador::findOrFail(Portador::CARTEIRA);
                }
                if ($pdv) {
                    abort(422, 'Cheque da empresa não é emitido no PDV!');
                }
                if (!$escolhido || $escolhido->tipo != Portador::TIPO_BANCO) {
                    abort(422, 'Escolha a conta do cheque!');
                }
                return $escolhido;

            case PagamentoService::MEIO_CREDITO:
            case PagamentoService::MEIO_DEBITO:
                if (!$entrada) {
                    if ($pdv) {
                        abort(422, 'No PDV o crédito é pago em dinheiro ou registrando o cancelamento no cartão!');
                    }
                    if (!$escolhido || $escolhido->tipo != Portador::TIPO_CARTAO) {
                        abort(422, 'Escolha o cartão da empresa!');
                    }
                    return $escolhido;
                }
                if (empty($forma['codmaquineta'])) {
                    abort(422, 'Escolha a maquineta do cartão!');
                }
                if (empty($forma['autorizacao'])) {
                    abort(422, 'Informe a autorização do cartão!');
                }
                return static::portadorDaMaquineta(Maquineta::findOrFail((int) $forma['codmaquineta']));
        }

        if (in_array($meio, static::MEIOS_BANCO)) {
            if ($pdv) {
                abort(422, 'PIX por chave, transferência, depósito e boleto são baixados pelo financeiro.');
            }
            if (!$escolhido || !in_array($escolhido->tipo, [Portador::TIPO_BANCO, Portador::TIPO_ADQUIRENTE])) {
                abort(422, 'Escolha a conta do banco!');
            }
            return $escolhido;
        }

        abort(422, "Meio de pagamento {$meio} não pode ser usado aqui!");
    }

    // Cheque recebido vai para o controle de cheques (idempotente)
    public static function gerarCheque(Pagamento $pag): void
    {
        if (Cheque::where('codpagamento', $pag->codpagamento)->exists()) {
            return;
        }
        $emitentes = [];
        if (!empty($pag->chequecnpj) || !empty($pag->chequeemitente)) {
            $emitentes[] = ['cnpj' => $pag->chequecnpj, 'emitente' => $pag->chequeemitente];
        }
        ChequeService::criar([
            'cmc7' => $pag->cmc7,
            'codpessoa' => $pag->codpessoa,
            'emitente' => $pag->chequeemitente,
            'emissao' => Carbon::today(),
            'vencimento' => $pag->chequevencimento,
            'valor' => $pag->total,
            'indstatus' => 1, // à repassar
            'transacao' => $pag->transacao ?? Carbon::now(),
            'codpagamento' => $pag->codpagamento,
            'emitentes' => $emitentes,
        ]);
    }

    // Pagamento de uma baixa feita pelo banco (boleto BB pela API, retorno
    // de boleto): um por linha de movimento, com as colunas dela; o
    // reprocessamento regrava o mesmo. Sentido pelo total do movimento
    // (negativo = entrou dinheiro no portador).
    public static function daBaixa(MovimentoTitulo $mov, int $meio, ?int $codportador, $transacao): ?Pagamento
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
            'transacao' => Carbon::parse($transacao ?? $mov->transacao),
            'efetivacao' => $pag->efetivacao ?? Carbon::now(),
            'codpessoa' => $titulo->codpessoa,
            'codfilial' => $titulo->codfilial,
        ]);
        $pag->save();
        PortadorMovimentoService::sincronizar($pag);
        if ($mov->codpagamento != $pag->codpagamento) {
            $mov->codpagamento = $pag->codpagamento;
            $mov->save();
        }
        return $pag;
    }

    // Corrige pessoa, portador, meio, data e observacao, como a liquidacao
    // permitia (decisao do Fabio, 01/10/2026: excecao a regra de o pagamento
    // nao mudar). Valores e titulos nao mudam: para isso, estorna e lanca de
    // novo. O portador vai junto para as linhas do movimento; a data, pelo
    // LancamentoDataService (com justificativa, TASK-204).
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
        if (!$pag->MovimentoTituloS()->exists()) {
            abort(422, 'Pagamento sem título não é alterado aqui.');
        }
        foreach ($pag->MovimentoTituloS as $mov) {
            if (!empty($mov->codtituloboleto) || !empty($mov->codboletoretorno)) {
                abort(422, 'Baixa de boleto pelo banco não é alterada aqui.');
            }
        }
        // a data com hora (TASK-204): mudou, vai pelo alterar data (periodo,
        // razao, titulos e a trilha, com justificativa) depois do resto
        $transacao = Carbon::parse($dados['transacao'])->startOfMinute();
        $mudouData = $pag->transacao->format('Y-m-d H:i') != $transacao->format('Y-m-d H:i');

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
        $pag->observacoes = $dados['observacao'] ?? null;
        PagamentoService::validar($pag);
        $pag->save();
        PortadorMovimentoService::sincronizar($pag);

        foreach ($pag->MovimentoTituloS as $mov) {
            $mov->codportador = $codportador;
            $mov->save();
        }
        if ($mudouData) {
            LancamentoDataService::alterarPagamento($pag->fresh(), $transacao, $dados['justificativa'] ?? null);
        }
        return PagamentoListaService::carregar($pag->codpagamento);
    }

    // Estornar desfaz o pagamento inteiro: estorna cada linha de baixa,
    // cancela o cheque ainda a repassar e cancela o pagamento (com
    // justificativa)
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
        if (!$pag->MovimentoTituloS()->exists()) {
            abort(422, 'Pagamento sem título não é estornado aqui.');
        }
        foreach ($pag->MovimentoTituloS as $mov) {
            if (!empty($mov->codtituloboleto)) {
                abort(422, 'Baixa de boleto pelo banco não é estornada aqui.');
            }
        }
        foreach (Cheque::where('codpagamento', $pag->codpagamento)->whereNull('cancelamento')->get() as $cheque) {
            if ($cheque->indstatus != 1) {
                abort(422, "O cheque {$cheque->numero} já foi repassado. Impossível estornar!");
            }
            $cheque->cancelamento = Carbon::now();
            $cheque->save();
        }
        foreach ($pag->MovimentoTituloS as $mov) {
            if ($mov->ehEstorno() || $mov->MovimentoTituloEstornoS()->exists()) {
                continue;
            }
            // vale colaborador / adiantamento: o pagamento nasceu com o
            // titulo, estornar e' desfazer o titulo (so' se nao movimentado)
            if ($mov->codtipomovimentotitulo == MovimentoTituloService::TIPO_IMPLANTACAO) {
                TituloService::estornar($mov->Titulo, $justificativa);
                continue;
            }
            MovimentoTituloService::estornar($mov);
        }
        $pag->refresh();
        PagamentoService::cancelar($pag, $justificativa);
        return PagamentoListaService::carregar($pag->codpagamento);
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
