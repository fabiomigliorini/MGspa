<?php

namespace Mg\Cidade;

use Mg\Usuario\Usuario;
use Mg\MgModel;
use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;

class Estado extends MgModel
{
    protected $table = 'tblestado';
    protected $primaryKey = 'codestado';

    protected $fillable = [
        'codigooficial',
        'codpais',
        'estado',
        'sigla',
    ];

    protected $casts = [
        'codestado' => 'integer',
        'codigooficial' => 'integer',
        'codpais' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
    ];

    public function Pais()
    {
        return $this->belongsTo(Pais::class, 'codpais', 'codpais');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function CidadeS()
    {
        return $this->hasMany(Cidade::class, 'codestado', 'codestado');
    }

    public function TributacaoNaturezaOperacaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codestado', 'codestado');
    }
}
