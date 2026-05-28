<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:31:57
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Usuario\Usuario;

class NotaFiscalReferenciada extends MgModel
{
    protected $table = 'tblnotafiscalreferenciada';
    protected $primaryKey = 'codnotafiscalreferenciada';


    protected $fillable = [
        'codnotafiscal',
        'nfechave'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnotafiscal' => 'integer',
        'codnotafiscalreferenciada' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
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
