<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Dec/2025 18:54:14
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\Tributacao\EntreTributante;
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
        'codentetributante',
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
        'codentetributante' => 'integer',
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
    public function EntreTributante()
    {
        return $this->belongsTo(EntreTributante::class, 'codentetributante', 'codentetributante');
    }

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