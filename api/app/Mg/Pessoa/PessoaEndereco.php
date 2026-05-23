<?php

namespace Mg\Pessoa;

use App\Models\Usuario;
use Mg\Cidade\Cidade;
use Mg\MgModel;

class PessoaEndereco extends MgModel
{
    protected $table = 'tblpessoaendereco';
    protected $primaryKey = 'codpessoaendereco';

    protected $fillable = [
        'apelido',
        'bairro',
        'cep',
        'cobranca',
        'codcidade',
        'codpessoa',
        'complemento',
        'endereco',
        'entrega',
        'inativo',
        'nfe',
        'numero',
        'ordem',
    ];

    protected $casts = [
        'cobranca' => 'boolean',
        'codcidade' => 'integer',
        'codpessoa' => 'integer',
        'codpessoaendereco' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'entrega' => 'boolean',
        'nfe' => 'boolean',
        'ordem' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    public function Cidade()
    {
        return $this->belongsTo(Cidade::class, 'codcidade', 'codcidade');
    }

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
