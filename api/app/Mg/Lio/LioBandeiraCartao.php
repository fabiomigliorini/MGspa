<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:29:50
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codliobandeiracartao' => 'integer',
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
    public function LioPedidoPagamentoS()
    {
        return $this->hasMany(LioPedidoPagamento::class, 'codliobandeiracartao', 'codliobandeiracartao');
    }

}
