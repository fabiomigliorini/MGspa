<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:36:23
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

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
        'verificacao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'cobranca' => 'boolean',
        'codpessoa' => 'integer',
        'codpessoaemail' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codverificacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'nfe' => 'boolean',
        'ordem' => 'integer',
        'verificacao' => 'datetime'
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
