<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Dec/2025 18:44:14
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codnotafiscal' => 'integer',
        'codnotafiscalreferenciada' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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