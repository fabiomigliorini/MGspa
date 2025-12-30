<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Dec/2025 18:44:54
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

    protected $dates = [
        'alteracao',
        'criacao',
        'data',
        'protocolodata'
    ];

    protected $casts = [
        'codnotafiscal' => 'integer',
        'codnotafiscalcartacorrecao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'lote' => 'integer',
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