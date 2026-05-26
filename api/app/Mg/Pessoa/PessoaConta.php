<?php

namespace Mg\Pessoa;

use Mg\Usuario\Usuario;
use Mg\Banco\Banco;
use Mg\MgModel;

class PessoaConta extends MgModel
{
    protected $table = 'tblpessoaconta';
    protected $primaryKey = 'codpessoaconta';

    protected $fillable = [
        'agencia', 'banco', 'cnpj', 'codpessoa', 'conta', 'inativo',
        'pixaleatoria', 'pixcnpj', 'pixcpf', 'pixemail', 'pixtelefone',
        'tipo', 'titular', 'observacoes',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'banco' => 'float',
        'cnpj' => 'float',
        'codpessoa' => 'integer',
        'codpessoaconta' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codbanco' => 'integer',
        'codusuariocriacao' => 'integer',
        'pixcnpj' => 'integer',
        'pixcpf' => 'integer',
        'pixtelefone' => 'float',
        'tipo' => 'float',
    ];

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

    public function Banco()
    {
        return $this->belongsTo(Banco::class, 'banco', 'codbanco');
    }
}
