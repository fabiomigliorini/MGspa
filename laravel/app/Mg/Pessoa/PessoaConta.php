<?php
/**
 * Created by php artisan gerador:model.
 * Date: 25/Feb/2023 12:40:33
 */

namespace Mg\Pessoa;

use Mg\Banco\Banco;
use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class PessoaConta extends MgModel
{
    protected $table = 'tblpessoaconta';
    protected $primaryKey = 'codpessoaconta';


    protected $fillable = [
        'agencia',
        'banco',
        'cnpj',
        'codpessoa',
        'conta',
        'inativo',
        'pixaleatoria',
        'pixcnpj',
        'pixcpf',
        'pixemail',
        'pixtelefone',
        'tipo',
        'titular',
        'observacoes'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
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
        'tipo' => 'float'
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

    public function Banco()
    {
        return $this->belongsTo(Banco::class, 'banco', 'codbanco');
    }
}