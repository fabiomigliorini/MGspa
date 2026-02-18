<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 11:34:04
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

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codcaixamercadoria' => 'integer',
        'codcaixamercadoriamodelo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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