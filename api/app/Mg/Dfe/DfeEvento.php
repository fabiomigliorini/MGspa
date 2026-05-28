<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:27:32
 */

namespace Mg\Dfe;

use Mg\MgModel;
use Mg\Dfe\DistribuicaoDfeEvento;
use Mg\Usuario\Usuario;

class DfeEvento extends MgModel
{
    protected $table = 'tbldfeevento';
    protected $primaryKey = 'coddfeevento';


    protected $fillable = [
        'dfeevento',
        'tpevento'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'coddfeevento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'tpevento' => 'integer'
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
    public function DistribuicaoDfeEventoS()
    {
        return $this->hasMany(DistribuicaoDfeEvento::class, 'coddfeevento', 'coddfeevento');
    }

}
