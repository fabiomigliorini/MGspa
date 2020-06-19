<?php

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;
// use Mg\Titulo\MovimentoTitulo;

class Portador extends MGModel
{
    protected $table = 'tblportador';
    protected $primaryKey = 'codportador';
    protected $fillable = [
        'portador',
        'codbanco',
        'agencia',
        'agenciadigito',
        'conta',
        'contadigito',
        'emiteboleto',
        'codfilial',
        'convenio',
        'diretorioremessa',
        'diretorioretorno',
        'carteira',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
    ];
    protected $casts = [
        'emiteboleto' => 'boolean',
    ];

    // Chaves Estrangeiras
    // public function Banco()
    // {
    //     return $this->belongsTo(Banco::class, 'codbanco', 'codbanco');
    // }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
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
    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codportador', 'codportador');
    }

    // public function MovimentoTituloS()
    // {
    //     return $this->hasMany(MovimentoTitulo::class, 'codportador', 'codportador');
    // }

    public function BoletoRetornoS()
    {
        return $this->hasMany(BoletoRetorno::class, 'codportador', 'codportador');
    }

    public function BoletoRetornoS()
    {
        return $this->hasMany(BoletoRetorno::class, 'codportador', 'codportador');
    }


}
