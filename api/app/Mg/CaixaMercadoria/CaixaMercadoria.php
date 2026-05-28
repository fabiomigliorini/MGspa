<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:38:42
 */

namespace Mg\CaixaMercadoria;

use Mg\MgModel;
use Mg\Negocio\NegocioCaixaMercadoria;
use Mg\CaixaMercadoria\CaixaMercadoriaModelo;
use Mg\Usuario\Usuario;

class CaixaMercadoria extends MgModel
{
    protected $table = 'tblcaixamercadoria';
    protected $primaryKey = 'codcaixamercadoria';


    protected $fillable = [
        'codcaixamercadoriamodelo',
        'inativo',
        'observacoes'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcaixamercadoria' => 'integer',
        'codcaixamercadoriamodelo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function CaixaMercadoriaModelo()
    {
        return $this->belongsTo(CaixaMercadoriaModelo::class, 'codcaixamercadoriamodelo', 'codcaixamercadoriamodelo');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function NegocioCaixaMercadoriaS()
    {
        return $this->hasMany(NegocioCaixaMercadoria::class, 'codcaixamercadoria', 'codcaixamercadoria');
    }

}
