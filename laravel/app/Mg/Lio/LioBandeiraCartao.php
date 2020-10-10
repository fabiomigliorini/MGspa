<?php
/**
 * Created by php artisan gerador:model.
 * Date: 10/Oct/2020 11:11:26
 */

namespace Mg\Lio;

use Mg\MgModel;
use Mg\Lio\LioPedidoPagamento;
use Mg\Usuario\Usuario;

class LioBandeiraCartao extends MgModel
{
    protected $table = 'tblliobandeiracartao';
    protected $primaryKey = 'codliobandeiracartao';


    protected $fillable = [
        'bandeiracartao',
        'siglalio'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codliobandeiracartao' => 'integer',
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
    public function LioPedidoPagamentoS()
    {
        return $this->hasMany(LioPedidoPagamento::class, 'codliobandeiracartao', 'codliobandeiracartao');
    }

}