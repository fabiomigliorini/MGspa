<?php

namespace Mg\Pdv;

use Carbon\Carbon;
use Exception;
use Mg\Cheque\Cheque;
use Mg\Cheque\ChequeService;
use Mg\Cheque\Cmc7\Cmc7;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;

/**
 * Cheque recebido no PDV: os dados vêm no pagamento (sync offline) e o tblcheque
 * nasce no fechamento do negócio. Pré-datado não gera título nem entra no limite;
 * o controle fica no módulo Cheque (repasse/devolução).
 */
class PdvNegocioChequeService
{
    const TIPO_CHEQUE = 2; // tPag NFe

    public static function ehCheque(NegocioFormaPagamento $nfp): bool
    {
        return $nfp->tipo == static::TIPO_CHEQUE;
    }

    // regras do cheque no fechamento: cliente identificado, sem troco, CMC7 válido, data
    public static function validar(Negocio $negocio, NegocioFormaPagamento $nfp): void
    {
        if ($negocio->codpessoa == 1) {
            throw new Exception('Cheque só para cliente identificado! Informe o cliente.', 1);
        }
        if ($nfp->valortroco > 0) {
            throw new Exception('Cheque não pode dar troco!', 1);
        }
        if (empty($nfp->cmc7) || !(new Cmc7($nfp->cmc7))->valido()) {
            throw new Exception('CMC7 do cheque inválido!', 1);
        }
        if (empty($nfp->chequevencimento)) {
            throw new Exception('Informe a data do cheque (bom para)!', 1);
        }
    }

    // cria o tblcheque de cada pagamento em cheque (idempotente por pagamento)
    public static function gerar(Negocio $negocio): void
    {
        foreach ($negocio->NegocioFormaPagamentoS as $nfp) {
            if (!static::ehCheque($nfp)) {
                continue;
            }
            if (Cheque::where('codnegocioformapagamento', $nfp->codnegocioformapagamento)->exists()) {
                continue;
            }
            $emitentes = [];
            if (!empty($nfp->chequecnpj) || !empty($nfp->chequeemitente)) {
                $emitentes[] = [
                    'cnpj' => $nfp->chequecnpj,
                    'emitente' => $nfp->chequeemitente,
                ];
            }
            ChequeService::criar([
                'cmc7' => $nfp->cmc7,
                'codpessoa' => $negocio->codpessoa,
                'emitente' => $nfp->chequeemitente,
                'emissao' => Carbon::today(),
                'vencimento' => $nfp->chequevencimento,
                'valor' => $nfp->valortotal,
                'indstatus' => 1, // à repassar
                'lancamento' => Carbon::now(),
                'codnegocioformapagamento' => $nfp->codnegocioformapagamento,
                'emitentes' => $emitentes,
            ]);
        }
    }

    // cancelamento do negócio: cheque ainda à repassar é cancelado; já repassado bloqueia
    public static function cancelar(Negocio $negocio): void
    {
        foreach ($negocio->NegocioFormaPagamentoS as $nfp) {
            $cheques = Cheque::where('codnegocioformapagamento', $nfp->codnegocioformapagamento)
                ->whereNull('cancelamento')
                ->get();
            foreach ($cheques as $cheque) {
                if ($cheque->indstatus != 1) {
                    throw new Exception("O cheque {$cheque->numero} já foi repassado. Impossível cancelar!", 1);
                }
                $cheque->cancelamento = Carbon::now();
                $cheque->save();
            }
        }
    }
}
