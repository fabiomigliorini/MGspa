<?php
/**
 * Created by php artisan gerador:model.
 * Date: 25/Feb/2023 12:38:30
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class PessoaTelefone extends MgModel
{
    protected $table = 'tblpessoatelefone';
    protected $primaryKey = 'codpessoatelefone';


    protected $fillable = [
        'apelido',
        'codpessoa',
        'ddd',
        'inativo',
        'ordem',
        'pais',
        'telefone',
        'tipo',
        'verificacao'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'verificacao'
    ];

    protected $casts = [
        'codpessoa' => 'integer',
        'codpessoatelefone' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'ddd' => 'float',
        'ordem' => 'integer',
        'pais' => 'float',
        'telefone' => 'float',
        'tipo' => 'integer'
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