<?php
/**
 * Created by php artisan gerador:model.
 * Date: 10/Jan/2024 11:31:19
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

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'verificacao'
    ];

    protected $casts = [
        'cobranca' => 'boolean',
        'codpessoa' => 'integer',
        'codpessoaemail' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codverificacao' => 'integer',
        'nfe' => 'boolean',
        'ordem' => 'integer'
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