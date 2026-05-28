<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:36:29
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Cidade\Cidade;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

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
        'ordem'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'cobranca' => 'boolean',
        'codcidade' => 'integer',
        'codpessoa' => 'integer',
        'codpessoaendereco' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'entrega' => 'boolean',
        'inativo' => 'datetime',
        'nfe' => 'boolean',
        'ordem' => 'integer'
    ];


    // Chaves Estrangeiras
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
