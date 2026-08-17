<?php
/**
 * Item de um lote de recarga — quem entrou e com quanto.
 *
 * E' o registro imutavel do que foi enviado a' operadora: se o acerto do
 * colaborador mudar depois, a planilha do lote continua reproduzindo este
 * valor. Tambem e' o que sustenta a idempotencia — a previa exclui quem ja'
 * tem item em lote vivo (ver BeeRecargaService::previa).
 */

namespace Mg\Rh;

use Mg\MgModel;

class BeeRecargaPeriodoColaborador extends MgModel
{
    protected $table = 'tblbeerecargaperiodocolaborador';
    protected $primaryKey = 'codbeerecargaperiodocolaborador';

    protected $fillable = [
        'codbeerecarga',
        'codperiodocolaborador',
        'valor',
    ];

    protected $casts = [
        'codbeerecargaperiodocolaborador' => 'integer',
        'codbeerecarga' => 'integer',
        'codperiodocolaborador' => 'integer',
        'valor' => 'float',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    // Chaves Estrangeiras
    public function BeeRecarga()
    {
        return $this->belongsTo(BeeRecarga::class, 'codbeerecarga', 'codbeerecarga');
    }

    public function PeriodoColaborador()
    {
        return $this->belongsTo(PeriodoColaborador::class, 'codperiodocolaborador', 'codperiodocolaborador');
    }
}
