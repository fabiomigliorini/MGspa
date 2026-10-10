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
        // a maquineta carregada so' com algumas colunas (a da listagem) vem
        // sem a pessoa: sem ela, casaria a primeira adquirente sem pessoa
        $codpessoa = $maquineta->codpessoa
            ?? Maquineta::whereKey($maquineta->codmaquineta)->value('codpessoa');
        if (empty($codpessoa)) {
            return null;
        }
        // lembrado no request: a listagem pergunta uma vez por cartao
        if (!array_key_exists($codpessoa, static::$adquirentes)) {
            static::$adquirentes[$codpessoa] = Portador::where('tipo', Portador::TIPO_ADQUIRENTE)
                ->where('codpessoa', $codpessoa)
                ->whereNull('inativo')
                ->orderBy('codportador')
                ->first();
        }
        return static::$adquirentes[$codpessoa];
    }

    // {codpessoa: Portador|null} da adquirente
    private static array $adquirentes = [];

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
                $pag->codportadorDoPagamento(),
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
        if ($entrada != $pag->entrada()) {
            abort(422, $entrada
                ? "O pagamento {$pag->codpagamento} é uma saída de dinheiro!"
                : "O pagamento {$pag->codpagamento} é uma entrada de dinheiro!");
        }
        $livre = PagamentoPendenciaService::livre($pag);
        if ($valor > $livre + 0.005) {
            abort(422, "O pagamento {$pag->codpagamento} tem R$ " . number_format($livre, 2, ',', '.')
                . ' livre: ajuste o valor dos títulos.');
        }
        // o fato fica como esta'; so' ganha a pessoa e a observacao que nao tinha
        $mudou = false;
        if (empty($pag->codpessoa) && !empty($dados['codpessoa'])) {
            $pag->codpessoa = (int) $dados['codpessoa'];
            $mudou = true;
        }
        if (empty($pag->observacoes) && !empty($dados['observacao'])) {
            $pag->observacoes = mb_substr($dados['observacao'], 0, 255);
            $mudou = true;
        }
        if ($mudou) {
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
            // a compensacao com valor (credito de PIS/Cofins, programacao de
            // pagamentos) fica com o valor compensado; o encontro, com zero
            return PagamentoService::criar(array_merge($base, [
                'meio' => PagamentoService::MEIO_COMPENSACAO,
                'principal' => $compensacao ? 0 : $total,
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

    // O portador em que a forma vai mexer, para conferir o papel do usuario
    // antes de gravar (PagamentoTituloAutorizador). Sem validar a forma.
    public static function portadorPrevisto(array $forma, bool $entrada, ?Pdv $pdv): ?Portador
    {
        if (!empty($forma['codpagamento'])) {
            return optional(Pagamento::find((int) $forma['codpagamento']))->portadorDoPagamento();
        }
        $meio = (int) ($forma['meio'] ?? 0);
        if ($meio == PagamentoService::MEIO_COMPENSACAO) {
            return Portador::find(Portador::ENCONTRO_CONTAS);
        }
        if (!empty($forma['codpagamentoorigem'])) {
            $original = Pagamento::find((int) $forma['codpagamentoorigem']);
            if (!$original) {
                return null;
            }
            return ($original->meio == PagamentoService::MEIO_PIX)
                ? Portador::find($original->codportadordestino)
                : (static::portadorDaMaquineta($original->Maquineta) ?? Portador::find($original->codportadordestino));
        }
        if (!empty($forma['codportador'])) {
            return Portador::find((int) $forma['codportador']);
        }
        if (in_array($meio, PagamentoService::MEIOS_CARTAO) && $entrada && !empty($forma['codmaquineta'])) {
            return static::portadorDaMaquineta(Maquineta::find((int) $forma['codmaquineta']));
        }
        if ($meio == PagamentoService::MEIO_DINHEIRO && $pdv && !empty($pdv->codportador)) {
            return Portador::find($pdv->codportador);
        }
        if ($meio == PagamentoService::MEIO_CHEQUE && $entrada) {
            return Portador::find(Portador::CARTEIRA);
        }
        return null;
    }

    // Portador da forma: dinheiro no portador de especie escolhido (no PDV, a
    // gaveta dele vem pre-selecionada: sem escolha, e' ela); cheque recebido
    // na Carteira; cartao na adquirente da maquineta; banco e cartao da
    // empresa escolhidos no contas
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
                if ($pdv && !$escolhido) {
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

    // O lapis do pagamento: pessoa e observacao; a data so' do pagamento
    // manual (a do integrado e' a do banco/maquineta), pelo alterar data
    // (periodo, razao, titulos, com justificativa na auditoria, TASK-204).
    // Meio e portador nao mudam: sao o fato; errou, desamarra e lanca de novo.
    public static function atualizar(Pagamento $pag, array $dados): Pagamento
    {
        if ($pag->estado != PagamentoService::ESTADO_EFETIVADO) {
            abort(422, 'Só pagamento efetivado é alterado!');
        }
        if (!empty($pag->codnegocio)) {
            abort(422, 'Pagamento de venda: altere pelo negócio.');
        }
        if (!empty($pag->codperiodocolaboradoracerto)) {
            abort(422, 'Pagamento de acerto de RH: altere pelo acerto.');
        }
        $atual = $pag->codportadorDoPagamento();
        if ((!empty($dados['meio']) && (int) $dados['meio'] != $pag->meio)
            || (!empty($dados['codportador']) && (int) $dados['codportador'] != $atual)) {
            abort(422, 'Meio e portador não mudam: desamarre os títulos, cancele e lance de novo.');
        }
        if (!empty($dados['transacao'])) {
            $transacao = Carbon::parse($dados['transacao'])->startOfMinute();
            if ($pag->transacao->format('Y-m-d H:i') != $transacao->format('Y-m-d H:i')) {
                if (!static::manual($pag)) {
                    abort(422, 'A data do pagamento integrado é a do banco ou da maquineta.');
                }
                // a permissao da data e' a do alterar data: gestor do portador
                LancamentoDataService::alterarPagamento($pag, $transacao, $dados['justificativa'] ?? null);
                $pag->refresh();
            }
        }
        $pag->codpessoa = !empty($dados['codpessoa']) ? (int) $dados['codpessoa'] : $pag->codpessoa;
        $pag->observacoes = $dados['observacao'] ?? null;
        $pag->save();
        return PagamentoListaService::carregar($pag->codpagamento);
    }

    // Desamarrar desfaz a baixa dos titulos (todas, ou so' as linhas
    // escolhidas): os movimentos sao estornados e os titulos reabrem; o vale
    // e o adiantamento que nasceram com o pagamento sao estornados. O
    // PAGAMENTO CONTINUA: e' o fato (o dinheiro andou) e fica sem amarracao,
    // em "Pagamentos nao resolvidos", para amarrar de novo ou cancelar (so' o
    // manual). Venda pelo negocio, acerto pelo acerto.
    public static function desamarrar(Pagamento $pag, string $justificativa, ?array $codmovimentos = null): Pagamento
    {
        $pag = Pagamento::lockForUpdate()->findOrFail($pag->codpagamento);
        if ($pag->estado != PagamentoService::ESTADO_EFETIVADO) {
            abort(422, 'Só pagamento efetivado tem amarração para desfazer!');
        }
        if (!empty($pag->codnegocio)) {
            abort(422, 'Pagamento de venda: altere pelo negócio.');
        }
        if (!empty($pag->codperiodocolaboradoracerto)) {
            abort(422, 'Pagamento de acerto de RH: estorne pelo acerto.');
        }
        $ativos = PagamentoPendenciaService::movimentosAtivos($pag);
        // encontro de contas, compensacao e pagamento de titulos a receber e a
        // pagar juntos: os titulos se pagam entre si, desamarrar e' tudo ou nada
        $sentidos = $ativos->map(fn ($m) => (float) $m->total < 0 ? -1 : 1)->unique()->count();
        if ($codmovimentos !== null && count(array_unique($codmovimentos)) < $ativos->count()
            && ($pag->meio == PagamentoService::MEIO_COMPENSACAO || $sentidos > 1)) {
            abort(422, 'Estes títulos se pagam entre si (a receber e a pagar): desamarre todos juntos.');
        }
        if ($codmovimentos !== null) {
            $ativos = $ativos->whereIn('codmovimentotitulo', array_map('intval', $codmovimentos));
        }
        if ($ativos->isEmpty()) {
            abort(422, 'Nada amarrado para desfazer!');
        }
        $vale = $pag->meio == PagamentoService::MEIO_VALE;
        foreach ($ativos as $mov) {
            // vale colaborador / adiantamento: o titulo nasceu com o
            // pagamento; desamarrar e' estornar o titulo (so' se nao
            // movimentado), sem cancelar o pagamento
            if ($mov->codtipomovimentotitulo == MovimentoTituloService::TIPO_IMPLANTACAO) {
                $vale = true;
                TituloService::estornar($mov->Titulo, $justificativa, false);
                continue;
            }
            MovimentoTituloService::estornar($mov);
        }
        $pag->refresh();
        OcorrenciaService::pagamentoEstornado($pag, $vale, $justificativa);
        return PagamentoListaService::carregar($pag->codpagamento);
    }

    // Cancelar = o fato nao existiu (foi digitado errado): so' o pagamento
    // manual e ja' desamarrado. Integrado (PIX, cartao da maquineta, Stone,
    // SafraPay) e boleto nunca se cancelam: o dinheiro entrou de verdade;
    // devolver e' pela devolucao do PIX / cancelamento no cartao. O cheque a
    // repassar e' cancelado junto. Sai do razao do portador.
    public static function cancelar(Pagamento $pag, string $justificativa): Pagamento
    {
        $pag = Pagamento::lockForUpdate()->findOrFail($pag->codpagamento);
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            abort(422, 'Pagamento já cancelado!');
        }
        if (!empty($pag->codnegocio)) {
            abort(422, 'Pagamento de venda: cancele pelo negócio.');
        }
        if (!empty($pag->codperiodocolaboradoracerto)) {
            abort(422, 'Pagamento de acerto de RH: estorne pelo acerto.');
        }
        if (!static::manual($pag)) {
            abort(422, 'Pagamento integrado (banco, maquineta, boleto) não se cancela: o dinheiro entrou de verdade. Para devolver, registre a devolução do PIX ou o cancelamento no cartão.');
        }
        if (PagamentoPendenciaService::movimentosAtivos($pag)->isNotEmpty()) {
            abort(422, 'Desamarre os títulos antes de cancelar o pagamento.');
        }
        foreach (Cheque::where('codpagamento', $pag->codpagamento)->whereNull('cancelamento')->get() as $cheque) {
            if ($cheque->indstatus != 1) {
                abort(422, "O cheque {$cheque->numero} já foi repassado. Impossível cancelar!");
            }
            $cheque->cancelamento = Carbon::now();
            $cheque->save();
        }
        PagamentoService::cancelar($pag, $justificativa);
        return PagamentoListaService::carregar($pag->codpagamento);
    }

    // Lancado a mao (dinheiro, cheque, cartao digitado, banco digitado):
    // nao veio de integracao nem de baixa do banco
    public static function manual(Pagamento $pag): bool
    {
        if ($pag->ehIntegrado() || !empty($pag->codpix)) {
            return false;
        }
        return !MovimentoTitulo::where('codpagamento', $pag->codpagamento)
            ->where(fn ($q) => $q->whereNotNull('codtituloboleto')->orWhereNotNull('codboletoretorno'))
            ->exists();
    }

    // Linhas de baixa (sem os estornos)
    public static function baixas(Pagamento $pag)
    {
        // a que foi estornada (desamarrada) tambem sai
        $estornadas = $pag->MovimentoTituloS->pluck('codmovimentotituloestorno')->filter()->all();
        return $pag->MovimentoTituloS
            ->filter(fn(MovimentoTitulo $m) => !$m->ehEstorno()
                && (int) $m->codtipomovimentotitulo < 900
                && !in_array($m->codmovimentotitulo, $estornadas));
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
