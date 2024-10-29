<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Oct/2024 11:45:47
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;
use Mg\NaturezaOperacao\Cfop;

class DominioAcumulador extends MgModel
{
    protected $table = 'tbldominioacumulador';
    protected $primaryKey = 'coddominioacumulador';


    protected $fillable = [
        'acumuladoravista',
        'acumuladorprazo',
        'codcfop',
        'codfilial',
        'historico',
        'icmscst',
        'movimentacaocontabil',
        'movimentacaofisica'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'acumuladoravista' => 'integer',
        'acumuladorprazo' => 'integer',
        'codcfop' => 'integer',
        'coddominioacumulador' => 'integer',
        'codfilial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'icmscst' => 'float',
        'movimentacaocontabil' => 'boolean',
        'movimentacaofisica' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Cfop()
    {
        return $this->belongsTo(Cfop::class, 'codcfop', 'codcfop');
    }

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

}