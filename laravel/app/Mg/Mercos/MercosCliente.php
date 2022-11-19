<?php
/**
 * Created by php artisan gerador:model.
 * Date: 19/Nov/2022 12:12:48
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

    protected $dates = [
        'alteracao',
        'criacao',
        'ultimaalteracaomercos'
    ];

    protected $casts = [
        'clienteid' => 'integer',
        'codmercoscliente' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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