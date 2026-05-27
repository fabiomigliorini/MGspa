<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:36:35
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class PessoaConta extends MgModel
{
    protected $table = 'tblpessoaconta';
    protected $primaryKey = 'codpessoaconta';


    protected $fillable = [
        'agencia',
        'banco',
        'cnpj',
        'codpessoa',
        'conta',
        'inativo',
        'observacoes',
        'pixaleatoria',
        'pixcnpj',
        'pixcpf',
        'pixemail',
        'pixtelefone',
        'tipo',
        'titular'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'banco' => 'float',
        'cnpj' => 'float',
        'codpessoa' => 'integer',
        'codpessoaconta' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'pixcnpj' => 'integer',
        'pixcpf' => 'integer',
        'pixtelefone' => 'float',
        'tipo' => 'float'
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

}
