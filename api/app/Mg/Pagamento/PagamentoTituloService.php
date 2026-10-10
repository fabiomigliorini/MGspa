<?php

namespace Mg\Pagamento;

use Mg\Ocorrencia\OcorrenciaService;
use Carbon\Carbon;
use Mg\Auditoria\AuditoriaService;
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
 * Recebimento e pagamento de titulos: o pagamento e' o fato (o dinheiro que
 * andou) e a baixa e' a amarracao (conceito do Fabio, 09/10/2026). Uma baixa
 * = um pagamento; uma linha de movimento por titulo, com principal, juros,
 * multa, desconto e total (o pagamento fica so' com o dinheiro). Um pagamento
 * pode baixar varios titulos e um titulo pode ser baixado por varios
 * pagamentos. Desamarrar estorna as baixas e o pagamento continua; cancelar
 * e' outra coisa (so' o manual ja' desamarrado). Encontro de contas = total
 * zero no portador Encontro de Contas. O mesmo servico atende o contas e o
 * PDV. Sem transacao interna.
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
     * Baixa os titulos com UM pagamento (uma baixa = um pagamento, conceito
     * do Fabio de 09/10/2026) e devolve [o pagamento].
     *
     * $dados: codpessoa, transacao (com hora), observacao, titulos[] (codtitulo,
     * saldo, juros, multa, desconto, total) e pagamentos[] com UMA forma
     * (meio, total, codportador, codmaquineta, bandeira, autorizacao,
     * parcelas, cheque; codpagamento = pagamento que ja' existe, com saldo
     * livre: cobranca integrada, PIX, orfao; codpagamentoorigem =
     * cancelamento no cartao / devolucao de PIX). Titulos que se anulam nao
     * levam forma: viram um encontro de contas (total zero no portador
     * Encontro de Contas).
     *
     * O pagamento guarda so' o dinheiro; juros, multa e desconto ficam nos
     * movimentos dos titulos. $pdv: baixa feita no PDV.
     */
    public static function baixar(array $dados, ?Pdv $pdv = null): array
    {
        if (empty($dados['titulos']) || !is_array($dados['titulos'])) {
            abort(422, 'Selecione ao menos um título!');
        }
        // a data com hora (TASK-204), no contas e no PDV: o periodo sai dela
        $transacao = LancamentoDataService::dataInformada($dados['transacao'] ?? null);

        // os titulos travados, na ordem do codigo, e o saldo conferido ja'
        // travado: duas baixas ao mesmo tempo nao passam as duas
        $codigos = array_map(fn ($t) => (int) ($t['codtitulo'] ?? 0), $dados['titulos']);
        if (count($codigos) != count(array_unique($codigos))) {
            abort(422, 'O mesmo título está duas vezes na baixa!');
        }
        $travados = Titulo::whereIn('codtitulo', $codigos)
            ->orderBy('codtitulo')
            ->lockForUpdate()
            ->get()
            ->keyBy('codtitulo');

        // linhas dos titulos, com o sinal do movimento (negativo = entra)
        $linhas = [];
        $liquido = 0;
        foreach ($dados['titulos'] as $t) {
            $titulo = $travados[(int) $t['codtitulo']] ?? abort(404, "Título {$t['codtitulo']} não encontrado!");
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
            ];
            $liquido += $sinal * (float) $t['total'];
        }
        $liquido = round($liquido, 2);
        $entrada = $liquido < 0;
        $compensacao = abs($liquido) < 0.005;

        // uma forma: o valor dela e' o liquido dos titulos
        $formas = array_values($dados['pagamentos'] ?? []);
        if ($compensacao) {
            $forma = ['meio' => PagamentoService::MEIO_COMPENSACAO, 'total' => 0];
        } else {
            if (count($formas) != 1) {
                abort(422, empty($formas)
                    ? 'Informe como foi pago!'
                    : 'Uma baixa é um pagamento: informe uma forma só.');
            }
            $forma = $formas[0];
            static::exigirCentavos($forma['total'] ?? 0);
            if ((float) ($forma['total'] ?? 0) <= 0) {
                abort(422, 'O valor do pagamento precisa ser maior que zero!');
            }
            if (abs(round((float) $forma['total'], 2) - abs($liquido)) > 0.005) {
                $s = number_format((float) $forma['total'], 2, ',', '.');
                $l = number_format(abs($liquido), 2, ',', '.');
                abort(422, "O pagamento ({$s}) não bate com o líquido dos títulos ({$l})!");
            }
        }

        $codfilialTitulos = $linhas[0]['titulo']->codfilial;
        $pag = static::pagamentoDaForma($forma, $entrada, $compensacao, $dados, $transacao, $pdv, $codfilialTitulos);
        // o titulo liquida na data do pagamento (o fato): o orfao amarrado
        // hoje baixa na data em que o dinheiro entrou
        $dataPagamento = Carbon::parse($pag->transacao ?? $transacao)->format('Y-m-d');
        foreach ($linhas as $linha) {
            MovimentoTituloHelper::liquidar(
                $linha['titulo'],
                $linha['total'],
                $linha['juros'],
                $linha['multa'],
                $linha['desconto'],
                $dataPagamento,
                $pag->codportadordestino ?? $pag->codportadororigem,
                null,
                $pag->codpagamento
            );
        }
        if ($entrada && $pag->meio == PagamentoService::MEIO_CHEQUE) {
            static::gerarCheque($pag);
        }

        return [PagamentoListaService::carregar($pag->codpagamento)];
    }

    // valor com no maximo 2 casas
    public static function exigirCentavos($valor): void
    {
        if (abs(round((float) $valor, 2) - (float) $valor) > 0.0000001) {
            abort(422, 'Valor com mais de duas casas decimais!');
        }
    }

    // Pagamento que ja' existe usado como forma (cobranca integrada que
    // confirmou, PIX, orfao): efetivado, sem venda, no mesmo sentido e com
    // saldo livre para o valor. Fica como esta' (e' o fato); so' ganha a
    // pessoa se nao tinha.
    public static function pagamentoExistente(int $codpagamento, float $valor, bool $entrada, array $dados): Pagamento
    {
        $pag = Pagamento::lockForUpdate()->findOrFail($codpagamento);
        if ($pag->estado != PagamentoService::ESTADO_EFETIVADO) {
            abort(422, "O pagamento {$pag->codpagamento} não está efetivado!");
        }
        if (!empty($pag->codnegocio)) {
            abort(422, "O pagamento {$pag->codpagamento} está amarrado à venda #{$pag->codnegocio}!");
        }
        if ($entrada != !empty($pag->codportadordestino)) {
            abort(422, $entrada
                ? "O pagamento {$pag->codpagamento} é uma saída de dinheiro!"
                : "O pagamento {$pag->codpagamento} é uma entrada de dinheiro!");
        }
        $livre = PagamentoPendenciaService::livre($pag);
        if ($valor > $livre + 0.005) {
            abort(422, "O pagamento {$pag->codpagamento} tem R$ " . number_format($livre, 2, ',', '.')
                . ' livre: ajuste o valor dos títulos.');
        }
        if (empty($pag->codpessoa) && !empty($dados['codpessoa'])) {
            $pag->codpessoa = (int) $dados['codpessoa'];
            $pag->save();
        }
        return $pag;
    }

    // Cria o pagamento da forma (ou usa o que ja' existe), com origem e
    // destino pelo meio e por onde aconteceu. So' o dinheiro: principal =
    // total. Usado tambem pelo vale/adiantamento (TituloAdiantamentoService).
    public static function pagamentoDaForma(
        array $forma,
        bool $entrada,
        bool $compensacao,
        array $dados,
        Carbon $transacao,
        ?Pdv $pdv,
        int $codfilialTitulos
    ): Pagamento {
        $total = round((float) ($forma['total'] ?? 0), 2);

        if (!empty($forma['codpagamento'])) {
            return static::pagamentoExistente((int) $forma['codpagamento'], $total, $entrada, $dados);
        }

        $meio = (int) ($forma['meio'] ?? 0);
        $base = [
            'codpessoa' => (int) $dados['codpessoa'],
            'observacoes' => $dados['observacao'] ?? null,
            'principal' => $total,
            'juros' => 0,
            'multa' => 0,
            'desconto' => 0,
            'meio' => $meio,
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'transacao' => $transacao,
            'efetivacao' => Carbon::now(),
            'codusuarioefetivacao' => auth()->user()->codusuario ?? null,
            'codpdv' => $pdv->codpdv ?? null,
            'codfilial' => $pdv->codfilial ?? $codfilialTitulos,
        ];

        // encontro de contas: total zero no portador Encontro de Contas
        if ($compensacao || $meio == PagamentoService::MEIO_COMPENSACAO) {
            return PagamentoService::criar(array_merge($base, [
                'meio' => PagamentoService::MEIO_COMPENSACAO,
                'principal' => 0,
                'codportadordestino' => Portador::ENCONTRO_CONTAS,
            ]));
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
            $maquineta = Maquineta::findOrFail((int) $forma['codmaquineta']);
            $campos += [
                'codmaquineta' => $maquineta->codmaquineta,
                'bandeira' => $forma['bandeira'] ?? null,
                'autorizacao' => $forma['autorizacao'] ?? null,
                'parcelas' => $forma['parcelas'] ?? null,
            ];
            // o cartao e' da loja da maquineta
            $campos['codfilial'] = $pdv->codfilial ?? $maquineta->codfilial ?? $campos['codfilial'];
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
        // a data alterada a mao (TASK-204) vale sobre a do banco no
        // reprocessamento: o pagamento e o movimento do titulo ficam nela
        $alterada = $pag->exists && $pag->AuditoriaS()
            ->where('tipo', AuditoriaService::TIPO_DATA_ALTERADA)
            ->exists();
        PagamentoService::preencher($pag, [
            'codportadordestino' => $entrada ? $codportador : null,
            'codportadororigem' => $entrada ? null : $codportador,
            'meio' => $meio,
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'principal' => $principal,
            'juros' => (float) $mov->juros,
            'multa' => (float) $mov->multa,
            'desconto' => (float) $mov->desconto,
            'transacao' => $alterada ? $pag->transacao : Carbon::parse($transacao ?? $mov->transacao),
            'efetivacao' => $pag->efetivacao ?? Carbon::now(),
            'codpessoa' => $titulo->codpessoa,
            'codfilial' => $titulo->codfilial,
        ]);
        $pag->save();
        PortadorMovimentoService::sincronizar($pag);
        $dia = Carbon::parse($pag->transacao)->format('Y-m-d');
        if ($mov->codpagamento != $pag->codpagamento || ($alterada && Carbon::parse($mov->transacao)->format('Y-m-d') != $dia)) {
            $mov->codpagamento = $pag->codpagamento;
            if ($alterada) {
                $mov->transacao = $dia;
            }
            $mov->save();
            if ($alterada) {
                MovimentoTituloService::recalcular($titulo);
            }
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
        // a data com hora (TASK-204): mudou, vai primeiro pelo alterar data
        // (periodo, razao, titulos e a trilha, com justificativa), com a
        // permissao do lapis; depois o portador, ja' na data nova
        $transacao = Carbon::parse($dados['transacao'])->startOfMinute();
        if ($pag->transacao->format('Y-m-d H:i') != $transacao->format('Y-m-d H:i')) {
            LancamentoDataService::alterarPagamento($pag, $transacao, $dados['justificativa'] ?? null, true);
            $pag->refresh();
        }

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
        $vale = $pag->meio == PagamentoService::MEIO_VALE;
        foreach ($pag->MovimentoTituloS as $mov) {
            if ($mov->ehEstorno() || $mov->MovimentoTituloEstornoS()->exists()) {
                continue;
            }
            // vale colaborador / adiantamento: o pagamento nasceu com o
            // titulo, estornar e' desfazer o titulo (so' se nao movimentado)
            if ($mov->codtipomovimentotitulo == MovimentoTituloService::TIPO_IMPLANTACAO) {
                $vale = true;
                TituloService::estornar($mov->Titulo, $justificativa);
                continue;
            }
            MovimentoTituloService::estornar($mov);
        }
        $pag->refresh();
        PagamentoService::cancelar($pag, $justificativa);
        OcorrenciaService::pagamentoEstornado($pag, $vale, $justificativa);
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
