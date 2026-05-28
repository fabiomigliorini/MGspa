<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:38:48
 */

namespace Mg\CaixaMercadoria;

use Mg\MgModel;
use Mg\CaixaMercadoria\CaixaMercadoria;
use Mg\Usuario\Usuario;

class CaixaMercadoriaModelo extends MgModel
{
    protected $table = 'tblcaixamercadoriamodelo';
    protected $primaryKey = 'codcaixamercadoriamodelo';


    protected $fillable = [
        'caixamercadoriamodelo',
        'inativo',
        'observacoes'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcaixamercadoriamodelo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function CaixaMercadoriaS()
    {
        return $this->hasMany(CaixaMercadoria::class, 'codcaixamercadoriamodelo', 'codcaixamercadoriamodelo');
    }

}
