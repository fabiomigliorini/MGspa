<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:27:54
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\Usuario\Usuario;

class NotaFiscalTerceiroDuplicata extends MgModel
{
    protected $table = 'tblnotafiscalterceiroduplicata';
    protected $primaryKey = 'codnotafiscalterceiroduplicata';


    protected $fillable = [
        'codnotafiscalterceiro',
        'codtitulo',
        'numero',
        'valor',
        'vencimento'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnotafiscalterceiro' => 'integer',
        'codnotafiscalterceiroduplicata' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'valor' => 'float',
        'vencimento' => 'date'
    ];


    // Chaves Estrangeiras
    public function NotaFiscalTerceiro()
    {
        return $this->belongsTo(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
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
