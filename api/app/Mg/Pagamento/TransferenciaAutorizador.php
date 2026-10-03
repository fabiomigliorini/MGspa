<?php

namespace Mg\Pagamento;

use Mg\Portador\Portador;
use Mg\Usuario\Autorizador;

/**
 * Quem opera cada portador numa transferencia (decisao 23 do doc-3): gaveta
 * = Caixa ou Gerente da filial; cofre e troco = Gerente da filial; Caixa
 * Financeiro, banco, adquirente e cartao = Financeiro. Administrador sempre.
 * "Dono" de um lado = quem pode operar aquele portador.
 */
class TransferenciaAutorizador
{
    public static function podeOperar(Portador $portador): bool
    {
        if (Autorizador::pode([])) {
            return true;
        }
        if ($portador->tipo !== Portador::TIPO_ESPECIE || $portador->codportador == Portador::CAIXA_FINANCEIRO) {
            return Autorizador::pode(['Financeiro']);
        }
        if (empty($portador->codfilial)) {
            return false;
        }
        $grupos = $portador->ehGaveta() ? ['Caixa', 'Gerente'] : ['Gerente'];
        return Autorizador::pode($grupos, $portador->codfilial);
    }

    public static function quemOpera(Portador $portador): string
    {
        if ($portador->tipo !== Portador::TIPO_ESPECIE || $portador->codportador == Portador::CAIXA_FINANCEIRO) {
            return 'Financeiro';
        }
        return $portador->ehGaveta() ? 'Caixa ou Gerente da filial' : 'Gerente da filial';
    }

    // registrar: dono de um dos lados
    public static function autorizarRegistro(Portador $origem, Portador $destino): void
    {
        if (!static::podeOperar($origem) && !static::podeOperar($destino)) {
            abort(403, "Transferir de {$origem->portador} para {$destino->portador}: só quem opera um dos dois ({$origem->portador}: "
                . static::quemOpera($origem) . "; {$destino->portador}: " . static::quemOpera($destino) . ').');
        }
    }

    // confirmar: o dono do destino (quem registrou nao era, senao ja'
    // nasceria efetivada)
    public static function podeConfirmar(Pagamento $pag): bool
    {
        return $pag->estado == PagamentoService::ESTADO_PENDENTE
            && PagamentoService::ehTransferencia($pag)
            && static::podeOperar($pag->PortadorDestino);
    }

    // cancelar: qualquer dos dois donos
    public static function podeCancelar(Pagamento $pag): bool
    {
        return $pag->estado != PagamentoService::ESTADO_CANCELADO
            && PagamentoService::ehTransferencia($pag)
            && (static::podeOperar($pag->PortadorOrigem) || static::podeOperar($pag->PortadorDestino));
    }
}
