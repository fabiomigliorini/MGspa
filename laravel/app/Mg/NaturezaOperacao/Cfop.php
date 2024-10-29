<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Oct/2024 12:01:06
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;
use Mg\NaturezaOperacao\DominioAcumulador;
use Mg\Usuario\Usuario;

class Cfop extends MgModel
{
    protected $table = 'tblcfop';
    protected $primaryKey = 'codcfop';


    protected $fillable = [
        'cfop'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcfop' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
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