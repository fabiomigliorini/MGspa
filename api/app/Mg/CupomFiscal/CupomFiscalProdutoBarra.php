<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:25:33
 */

namespace Mg\CupomFiscal;

use Mg\MgModel;
use Mg\CupomFiscal\CupomFiscal;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Produto\ProdutoBarra;
use Mg\Usuario\Usuario;

class CupomFiscalProdutoBarra extends MgModel
{
    protected $table = 'tblcupomfiscalprodutobarra';
    protected $primaryKey = 'codcupomfiscalprodutobarra';


    protected $fillable = [
        'aliquotaicms',
        'codcupomfiscal',
        'codnegocioprodutobarra',
        'codprodutobarra',
        'quantidade',
        'valorunitario'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcupomfiscal' => 'integer',
        'codcupomfiscalprodutobarra' => 'integer',
        'codnegocioprodutobarra' => 'integer',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'quantidade' => 'float',
        'valorunitario' => 'float'
    ];


    // Chaves Estrangeiras
    public function CupomFiscal()
    {
        return $this->belongsTo(CupomFiscal::class, 'codcupomfiscal', 'codcupomfiscal');
    }

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
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
