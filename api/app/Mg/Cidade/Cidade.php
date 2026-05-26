<?php

namespace Mg\Cidade;

use App\Models\Usuario;
use Mg\MgModel;

class Cidade extends MgModel
{
    protected $table = 'tblcidade';
    protected $primaryKey = 'codcidade';

    protected $fillable = [
        'cidade',
        'codestado',
        'codigooficial',
        'sigla',
    ];

    protected $casts = [
        'codcidade' => 'integer',
        'codestado' => 'integer',
        'codigooficial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
    ];

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

    public function PessoaS()
    {
        return $this->hasMany(\Mg\Pessoa\Pessoa::class, 'codcidade', 'codcidade');
    }

    public function PessoaCobrancaS()
    {
        return $this->hasMany(\Mg\Pessoa\Pessoa::class, 'codcidadecobranca', 'codcidade');
    }

    public function PessoaEnderecoS()
    {
        return $this->hasMany(\Mg\Pessoa\PessoaEndereco::class, 'codcidade', 'codcidade');
    }
}
