<?php
/**
 * Created by php artisan gerador:model.
 * Date: 09/Dec/2024 11:29:53
 */

namespace Mg\Saurus;

use Mg\MgModel;
use Mg\Saurus\SaurusPagamento;
use Mg\Usuario\Usuario;

class SaurusPinPad extends MgModel
{
    protected $table = 'tblsauruspinpad';
    protected $primaryKey = 'codsauruspinpad';


    protected $fillable = [
        'apelido',
        'id',
        'inativo',
        'observacoes',
        'serial'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codsauruspinpad' => 'integer',
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
    public function SaurusPagamentoS()
    {
        return $this->hasMany(SaurusPagamento::class, 'codsauruspinpad', 'codsauruspinpad');
    }

}