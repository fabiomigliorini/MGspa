<?php

namespace Mg\Grao;

use Mg\MgModel;
use Mg\Fazenda\Plantio;
use Mg\Contrato\Contrato;

/**
 * Ponto da carga: uma origem ou um destino. contatipo aponta para UMA das tres
 * contas (PLANTIO/UNIDADE/CONTRATO). `liquido` = rateio (kg seco) deste ponto.
 * O extrato (tblmovimentograo) e derivado destes registros.
 */
class CargaPonto extends MgModel
{
    protected $table = 'tblcargaponto';
    protected $primaryKey = 'codcargaponto';

    protected $fillable = [
        'codcarga',
        'papel',
        'contatipo',
        'codplantio',
        'codunidadearmazenadora',
        'codcontrato',
        'liquido',
        'numeronf',
        'valornf',
        'chavenf',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcarga' => 'integer',
        'codcargaponto' => 'integer',
        'codcontrato' => 'integer',
        'codplantio' => 'integer',
        'codunidadearmazenadora' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'liquido' => 'float',
        'valornf' => 'float',
    ];

    // Chaves Estrangeiras
    public function Carga()
    {
        return $this->belongsTo(Carga::class, 'codcarga', 'codcarga');
    }

    public function Plantio()
    {
        return $this->belongsTo(Plantio::class, 'codplantio', 'codplantio');
    }

    public function UnidadeArmazenadora()
    {
        return $this->belongsTo(UnidadeArmazenadora::class, 'codunidadearmazenadora', 'codunidadearmazenadora');
    }

    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }
}
