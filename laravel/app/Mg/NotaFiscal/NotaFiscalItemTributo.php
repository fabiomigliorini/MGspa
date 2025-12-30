<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Dec/2025 18:43:26
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Mg\Tributacao\Tributo;
use Mg\Usuario\Usuario;

class NotaFiscalItemTributo extends MgModel
{
    protected $table = 'tblnotafiscalitemtributo';
    protected $primaryKey = 'codnotafiscalitemtributo';


    protected $fillable = [
        'aliquota',
        'base',
        'basereducao',
        'basereducaopercentual',
        'beneficiocodigo',
        'cclasstrib',
        'codnotafiscalprodutobarra',
        'codtributo',
        'cst',
        'fundamentolegal',
        'geracredito',
        'valor',
        'valorcredito'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'aliquota' => 'float',
        'base' => 'float',
        'basereducao' => 'float',
        'basereducaopercentual' => 'float',
        'codnotafiscalitemtributo' => 'integer',
        'codnotafiscalprodutobarra' => 'integer',
        'codtributo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'geracredito' => 'boolean',
        'valor' => 'float',
        'valorcredito' => 'float'
    ];


    // Chaves Estrangeiras
    public function NotaFiscalProdutoBarra()
    {
        return $this->belongsTo(NotaFiscalProdutoBarra::class, 'codnotafiscalprodutobarra', 'codnotafiscalprodutobarra');
    }

    public function Tributo()
    {
        return $this->belongsTo(Tributo::class, 'codtributo', 'codtributo');
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