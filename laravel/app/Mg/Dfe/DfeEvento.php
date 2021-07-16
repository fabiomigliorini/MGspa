<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jul/2021 11:29:36
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'coddfeevento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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