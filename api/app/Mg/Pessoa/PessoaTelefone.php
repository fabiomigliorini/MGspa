<?php

namespace Mg\Pessoa;

use App\Models\Usuario;
use Mg\MgModel;

class PessoaTelefone extends MgModel
{
    protected $table = 'tblpessoatelefone';
    protected $primaryKey = 'codpessoatelefone';

    protected $fillable = [
        'apelido',
        'codpessoa',
        'codverificacao',
        'ddd',
        'inativo',
        'ordem',
        'pais',
        'telefone',
        'tipo',
        'verificacao',
    ];

    protected $casts = [
        'codpessoa' => 'integer',
        'codpessoatelefone' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codverificacao' => 'integer',
        'ddd' => 'float',
        'ordem' => 'integer',
        'pais' => 'float',
        'tipo' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'verificacao' => 'datetime',
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
}
