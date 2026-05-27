<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:35:58
 */

namespace Mg\Mercos;

use Mg\MgModel;
use Mg\Mercos\MercosPedido;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class MercosCliente extends MgModel
{
    protected $table = 'tblmercoscliente';
    protected $primaryKey = 'codmercoscliente';


    protected $fillable = [
        'clienteid',
        'codpessoa',
        'ultimaalteracaomercos'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'clienteid' => 'integer',
        'codmercoscliente' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'ultimaalteracaomercos' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
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
    public function MercosPedidoS()
    {
        return $this->hasMany(MercosPedido::class, 'codmercoscliente', 'codmercoscliente');
    }

}
