<?php

namespace Mg\NotaFiscal;

class NotaFiscalStatusService
{
    // Status da Nota Fiscal
    const STATUS_LANCADA             = 'LAN'; // Lançada (emitida = false)
    const STATUS_DIGITACAO           = 'DIG'; // Em Digitação (emitida = true e numero vazio)
    const STATUS_ERRO                = 'ERR'; // Não Autorizada (emitida = true, tem número, sem autorização)
    const STATUS_AUTORIZADA          = 'AUT'; // Autorizada (nfeautorizacao preenchido e não cancelada/inutilizada)
    const STATUS_CANCELADA           = 'CAN'; // Cancelada (nfecancelamento preenchido)
    const STATUS_INUTILIZADA         = 'INU'; // Inutilizada (nfeinutilizacao preenchido)
    const STATUS_DENEGADA            = 'DEN'; // Denegada

    const STATUS_LABELS = [
        self::STATUS_LANCADA     => 'Lançada',
        self::STATUS_DIGITACAO   => 'Em Digitação',
        self::STATUS_ERRO        => 'Não Autorizada',
        self::STATUS_AUTORIZADA  => 'Autorizada',
        self::STATUS_CANCELADA   => 'Cancelada',
        self::STATUS_INUTILIZADA => 'Inutilizada',
        self::STATUS_DENEGADA    => 'Denegada',
    ];

    /**
     * Calcula o status atual da nota baseado nos campos
     */
    public static function calcularStatus(NotaFiscal $nota): string
    {
        if (!$nota->emitida) {
            return static::STATUS_LANCADA;
        }

        if (!empty($nota->nfeinutilizacao)) {
            return static::STATUS_INUTILIZADA;
        }

        if (!empty($nota->nfecancelamento)) {
            return static::STATUS_CANCELADA;
        }

        if (!empty($nota->nfeautorizacao)) {
            return static::STATUS_AUTORIZADA;
        }

        if (empty($nota->numero)) {
            return static::STATUS_DIGITACAO;
        }

        return static::STATUS_ERRO;
    }

    /**
     * Atualiza o campo status da nota
     */
    public static function atualizarStatus(NotaFiscal $nota): void
    {
        $novoStatus = static::calcularStatus($nota);

        if ($nota->status !== $novoStatus) {
            $nota->status = $novoStatus;
            $nota->saveQuietly(); // Salva sem disparar eventos
        }
    }

    /**
     * Retorna o status atual da nota (DEPRECATED: use $nota->status)
     * @deprecated Use o campo $nota->status ao invés deste método
     */
    public static function getStatusNota(NotaFiscal $nota): string
    {
        return static::calcularStatus($nota);
    }

    public static function isInutilizada(NotaFiscal $nota): bool
    {
        return !empty($nota->nfeinutilizacao);
    }

    public static function isCancelada(NotaFiscal $nota): bool
    {
        return !empty($nota->nfecancelamento);
    }

    public static function isAutorizada(NotaFiscal $nota): bool
    {
        return !empty($nota->nfeautorizacao)
            && !static::isCancelada($nota)
            && !static::isInutilizada($nota);
    }

    public static function isCanceladaInutilizada(NotaFiscal $nota): bool
    {
        return static::isCancelada($nota) || static::isInutilizada($nota);
    }

    /**
     * Verifica se a nota pode ser editada
     */
    public static function isEditable(NotaFiscal $nota): bool
    {
        $statusBloqueados = [
            static::STATUS_AUTORIZADA,
            static::STATUS_CANCELADA,
            static::STATUS_INUTILIZADA,
            static::STATUS_ERRO,
        ];

        return !in_array($nota->status, $statusBloqueados);
    }

    public static function isAtiva(NotaFiscal $nota): bool
    {
        if (static::isAutorizada($nota)) {
            return true;
        }
        if (!$nota->emitida && !empty($nota->numero)) {
            return true;
        }
        return false;
    }

    public static function isDigitacao(NotaFiscal $nota): bool
    {
        return (empty($nota->numero) && ($nota->emitida));
    }
}
