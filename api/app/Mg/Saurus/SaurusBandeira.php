<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:41:02
 */

namespace Mg\Saurus;

use Mg\MgModel;
use Mg\Saurus\SaurusPagamento;
use Mg\Usuario\Usuario;

class SaurusBandeira extends MgModel
{
    protected $table = 'tblsaurusbandeira';
    protected $primaryKey = 'codsaurusbandeira';


    protected $fillable = [
        'bandeira',
        'tband'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codsaurusbandeira' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'tband' => 'integer'
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
    public function SaurusPagamentoS()
    {
        return $this->hasMany(SaurusPagamento::class, 'codsaurusbandeira', 'codsaurusbandeira');
    }

}
