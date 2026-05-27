<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:35:32
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codpagarmebandeira' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
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
