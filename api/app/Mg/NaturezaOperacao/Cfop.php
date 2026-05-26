<?php

namespace Mg\NaturezaOperacao;

use Mg\Usuario\Usuario;
use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;

class Cfop extends MgModel
{
    protected $table = 'tblcfop';
    protected $primaryKey = 'codcfop';

    protected $fillable = ['codcfop', 'cfop', 'descricao'];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'codcfop' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function DominioAcumuladorS()
    {
        return $this->hasMany(DominioAcumulador::class, 'codcfop', 'codcfop');
    }

    public function NotaFiscalProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codcfop', 'codcfop');
    }

    public function TributacaoNaturezaOperacaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codcfop', 'codcfop');
    }
}
