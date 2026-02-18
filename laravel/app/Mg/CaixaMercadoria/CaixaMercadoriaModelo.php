<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 11:34:11
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

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codcaixamercadoriamodelo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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