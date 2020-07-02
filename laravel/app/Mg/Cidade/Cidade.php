<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Jul/2020 09:15:51
 */

namespace Mg\Cidade;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Cidade\Estado;
use Mg\Usuario\Usuario;

class Cidade extends MgModel
{
    protected $table = 'tblcidade';
    protected $primaryKey = 'codcidade';


    protected $fillable = [
        'cidade',
        'codestado',
        'codigooficial',
        'sigla'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcidade' => 'integer',
        'codestado' => 'integer',
        'codigooficial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codcidade', 'codcidade');
    }

    public function PessoaCobrancaS()
    {
        return $this->hasMany(Pessoa::class, 'codcidadecobranca', 'codcidade');
    }

}