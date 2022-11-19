<?php
/**
 * Created by php artisan gerador:model.
 * Date: 19/Nov/2022 18:19:14
 */

namespace Mg\PagarMe;

use Mg\MgModel;
use Mg\PagarMe\PagarMePagamento;
use Mg\Usuario\Usuario;

class PagarMeBandeira extends MgModel
{
    protected $table = 'tblpagarmebandeira';
    protected $primaryKey = 'codpagarmebandeira';


    protected $fillable = [
        'bandeira',
        'scheme'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codpagarmebandeira' => 'integer',
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
    public function PagarMePagamentoS()
    {
        return $this->hasMany(PagarMePagamento::class, 'codpagarmebandeira', 'codpagarmebandeira');
    }

}