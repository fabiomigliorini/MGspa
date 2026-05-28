<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:36:17
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
        'codverificacao',
        'ddd',
        'inativo',
        'ordem',
        'pais',
        'telefone',
        'tipo',
        'verificacao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codpessoa' => 'integer',
        'codpessoatelefone' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codverificacao' => 'integer',
        'criacao' => 'datetime',
        'ddd' => 'float',
        'inativo' => 'datetime',
        'ordem' => 'integer',
        'pais' => 'float',
        'tipo' => 'integer',
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
