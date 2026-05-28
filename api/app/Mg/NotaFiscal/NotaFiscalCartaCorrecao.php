<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:31:38
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Usuario\Usuario;

class NotaFiscalCartaCorrecao extends MgModel
{
    protected $table = 'tblnotafiscalcartacorrecao';
    protected $primaryKey = 'codnotafiscalcartacorrecao';


    protected $fillable = [
        'codnotafiscal',
        'data',
        'lote',
        'protocolo',
        'protocolodata',
        'sequencia',
        'texto'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnotafiscal' => 'integer',
        'codnotafiscalcartacorrecao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'lote' => 'integer',
        'protocolodata' => 'datetime',
        'sequencia' => 'integer'
    ];


    // Chaves Estrangeiras
    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

}
