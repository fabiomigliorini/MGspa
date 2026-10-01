<?php

namespace Mg\Pagamento;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Regras do pagamento (M4 do plano doc-3): criar, efetivar, cancelar e o
 * pagamento contrario (cancelamento parcial, devolucao, estorno). O original
 * nunca e' alterado por um contrario.
 */
class PagamentoService
{
    // meio: codigo tPag da NF-e; internos acima de 90
    const MEIO_DINHEIRO = 1;
    const MEIO_CHEQUE = 2;
    const MEIO_CREDITO = 3;
    const MEIO_DEBITO = 4;
    const MEIO_VALE = 12;
    const MEIO_BOLETO = 15;
    const MEIO_DEPOSITO = 16;
    const MEIO_PIX = 17;
    const MEIO_TRANSFERENCIA = 18;
    const MEIO_COMPENSACAO = 91;
    const MEIO_FOLHA = 92;
    const MEIO_PERMUTA = 93;
    const MEIO_PERDA = 94;
    const MEIO_OUTROS = 99;

    const MEIOS = [
        self::MEIO_DINHEIRO => 'Dinheiro',
        self::MEIO_CHEQUE => 'Cheque',
        self::MEIO_CREDITO => 'Cartão de Crédito',
        self::MEIO_DEBITO => 'Cartão de Débito',
        self::MEIO_VALE => 'Vale Compras',
        self::MEIO_BOLETO => 'Boleto',
        self::MEIO_DEPOSITO => 'Depósito',
        self::MEIO_PIX => 'PIX',
        self::MEIO_TRANSFERENCIA => 'Transferência',
        self::MEIO_COMPENSACAO => 'Compensação',
        self::MEIO_FOLHA => 'Folha',
        self::MEIO_PERMUTA => 'Permuta',
        self::MEIO_PERDA => 'Perda',
        self::MEIO_OUTROS => 'Outros',
    ];

    const MEIOS_CARTAO = [self::MEIO_CREDITO, self::MEIO_DEBITO];

    const ESTADO_PENDENTE = 'P';
    const ESTADO_EFETIVADO = 'E';
    const ESTADO_CANCELADO = 'C';

    const ESTADOS = [
        self::ESTADO_PENDENTE => 'Pendente',
        self::ESTADO_EFETIVADO => 'Efetivado',
        self::ESTADO_CANCELADO => 'Cancelado',
    ];

    // so' pagamento sem documento
    const MOTIVO_TAXA = 'T';
    const MOTIVO_TARIFA = 'F';
    const MOTIVO_RENDIMENTO = 'R';
    const MOTIVO_AJUSTE = 'A';

    const MOTIVOS = [
        self::MOTIVO_TAXA => 'Taxa',
        self::MOTIVO_TARIFA => 'Tarifa',
        self::MOTIVO_RENDIMENTO => 'Rendimento',
        self::MOTIVO_AJUSTE => 'Ajuste de Caixa',
    ];

    // total = principal + juros + multa - desconto
    public static function calcularTotal(Pagamento $pag): float
    {
        return round(
            (float) $pag->principal
                + (float) $pag->juros
                + (float) $pag->multa
                - (float) $pag->desconto,
            2
        );
    }

    // preenche um pagamento (novo ou existente) e confere as regras; nao salva
    public static function preencher(Pagamento $pag, array $dados): Pagamento
    {
        $pag->fill($dados);
        foreach (['juros', 'multa', 'desconto'] as $col) {
            $pag->$col = round((float) ($pag->$col ?? 0), 2);
        }
        $pag->principal = round((float) $pag->principal, 2);
        $pag->total = static::calcularTotal($pag);
        if (empty($pag->valortroco)) {
            $pag->valortroco = null;
        }
        if (empty($pag->estado)) {
            $pag->estado = static::ESTADO_PENDENTE;
        }
        if (empty($pag->lancamento)) {
            $pag->lancamento = Carbon::now();
        }
        static::validar($pag);
        return $pag;
    }

    public static function validar(Pagamento $pag): void
    {
        if (!array_key_exists($pag->meio, static::MEIOS)) {
            abort(422, "Meio de pagamento {$pag->meio} inválido!");
        }
        if (!array_key_exists($pag->estado, static::ESTADOS)) {
            abort(422, "Estado de pagamento {$pag->estado} inválido!");
        }
        if (!empty($pag->motivo) && !array_key_exists($pag->motivo, static::MOTIVOS)) {
            abort(422, "Motivo de pagamento {$pag->motivo} inválido!");
        }
        if ($pag->principal < 0 || ($pag->principal == 0 && $pag->meio != static::MEIO_COMPENSACAO)) {
            abort(422, 'O valor do pagamento precisa ser maior que zero!');
        }
        if ($pag->juros < 0 || $pag->multa < 0 || $pag->desconto < 0 || ($pag->valortroco ?? 0) < 0) {
            abort(422, 'Juros, multa, desconto e troco não podem ser negativos!');
        }
        if ($pag->total < 0) {
            abort(422, 'O desconto não pode ser maior que o valor do pagamento!');
        }
    }

    public static function criar(array $dados): Pagamento
    {
        $pag = static::preencher(new Pagamento(), $dados);
        $pag->save();
        return $pag;
    }

    public static function efetivar(Pagamento $pag, ?Carbon $quando = null): Pagamento
    {
        if ($pag->estado == static::ESTADO_EFETIVADO) {
            return $pag;
        }
        if ($pag->estado == static::ESTADO_CANCELADO) {
            abort(422, "Pagamento {$pag->codpagamento} cancelado não pode ser efetivado!");
        }
        $pag->estado = static::ESTADO_EFETIVADO;
        $pag->efetivacao = $quando ?? Carbon::now();
        $pag->codusuarioefetivacao = Auth::user()->codusuario ?? null;
        $pag->save();
        return $pag;
    }

    public static function cancelar(Pagamento $pag, string $justificativa): Pagamento
    {
        if ($pag->estado == static::ESTADO_CANCELADO) {
            return $pag;
        }
        $justificativa = trim($justificativa);
        if (empty($justificativa)) {
            abort(422, 'Informe a justificativa do cancelamento do pagamento!');
        }
        $pag->estado = static::ESTADO_CANCELADO;
        $pag->cancelamento = Carbon::now();
        $pag->codusuariocancelamento = Auth::user()->codusuario ?? null;
        $pag->justificativa = mb_substr($justificativa, 0, 300);
        $pag->save();
        return $pag;
    }

    // Pagamento no sentido contrario do original, apontando para ele. O
    // original nunca muda. Valor limitado ao que ainda nao foi devolvido.
    public static function contrario(Pagamento $original, array $dados): Pagamento
    {
        if ($original->estado == static::ESTADO_CANCELADO) {
            abort(422, "Pagamento {$original->codpagamento} cancelado não tem contrário!");
        }
        $jaDevolvido = $original->PagamentoContrarioS()
            ->where('estado', '!=', static::ESTADO_CANCELADO)
            ->sum('total');
        $disponivel = round($original->total - $jaDevolvido, 2);
        $dados = array_merge([
            'meio' => $original->meio,
            'codportadororigem' => $original->codportadordestino,
            'codportadordestino' => $original->codportadororigem,
            'codnegocio' => $original->codnegocio,
            'codpessoa' => $original->codpessoa,
            'codfilial' => $original->codfilial,
            'codmaquineta' => $original->codmaquineta,
            'bandeira' => $original->bandeira,
            'autorizacao' => $original->autorizacao,
        ], $dados, [
            'codpagamentoorigem' => $original->codpagamento,
        ]);
        $pag = static::preencher(new Pagamento(), $dados);
        if ($pag->total - $disponivel > 0.005) {
            abort(422, 'O valor devolvido é maior que o que resta do pagamento original!');
        }
        $pag->save();
        return $pag;
    }
}
