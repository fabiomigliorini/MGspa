<?php
/**
 * Created by php artisan gerador:model.
 * Date: 25/Feb/2023 12:39:09
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class PessoaEmail extends MgModel
{
    protected $table = 'tblpessoaemail';
    protected $primaryKey = 'codpessoatelefone';


    protected $fillable = [
        'apelido',
        'cobranca',
        'codpessoa',
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
        'codpessoatelefone' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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