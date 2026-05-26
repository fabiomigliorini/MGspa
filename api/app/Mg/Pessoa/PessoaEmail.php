<?php

namespace Mg\Pessoa;

use App\Models\Usuario;
use Mg\MgModel;

class PessoaEmail extends MgModel
{
    protected $table = 'tblpessoaemail';
    protected $primaryKey = 'codpessoaemail';

    protected $fillable = [
        'apelido',
        'cobranca',
        'codpessoa',
        'codverificacao',
        'email',
        'inativo',
        'nfe',
        'ordem',
        'verificacao',
    ];

    protected $casts = [
        'cobranca' => 'boolean',
        'codpessoa' => 'integer',
        'codpessoaemail' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codverificacao' => 'integer',
        'nfe' => 'boolean',
        'ordem' => 'integer',
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
