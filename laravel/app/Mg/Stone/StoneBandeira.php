<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/Apr/2021 16:34:44
 */

namespace Mg\Stone;

use Mg\MgModel;
use Mg\Stone\StoneTransacao;
use Mg\Usuario\Usuario;

class StoneBandeira extends MgModel
{
    protected $table = 'tblstonebandeira';
    protected $primaryKey = 'codstonebandeira';


    protected $fillable = [
        'bandeira',
        'inativo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codstonebandeira' => 'integer',
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
    public function StoneTransacaoS()
    {
        return $this->hasMany(StoneTransacao::class, 'codstonebandeira', 'codstonebandeira');
    }

}