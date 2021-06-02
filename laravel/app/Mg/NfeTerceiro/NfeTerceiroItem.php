<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:25:09
 */

namespace Mg\NfeTerceiro;

use Mg\MgModel;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\Produto\ProdutoBarra;
use Mg\Usuario\Usuario;

class NfeTerceiroItem extends MgModel
{
    protected $table = 'tblnfeterceiroitem';
    protected $primaryKey = 'codnfeterceiroitem';


    protected $fillable = [
        'cean',
        'ceantrib',
        'cest',
        'cfop',
        'codnfeterceiro',
        'codprodutobarra',
        'complemento',
        'cprod',
        'csosn',
        'cst',
        'infadprod',
        'ipipipi',
        'ipivbc',
        'ipivipi',
        'margem',
        'modalidadeicmsgarantido',
        'ncm',
        'nitem',
        'picms',
        'picmsst',
        'qcom',
        'qtrib',
        'ucom',
        'utrib',
        'vbc',
        'vbcst',
        'vdesc',
        'vfrete',
        'vicms',
        'vicmsst',
        'voutro',
        'vprod',
        'vseg',
        'vuncom',
        'vuntrib',
        'xprod'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'cfop' => 'integer',
        'codnfeterceiro' => 'integer',
        'codnfeterceiroitem' => 'integer',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'complemento' => 'float',
        'ipipipi' => 'float',
        'ipivbc' => 'float',
        'ipivipi' => 'float',
        'margem' => 'float',
        'modalidadeicmsgarantido' => 'boolean',
        'nitem' => 'integer',
        'picms' => 'float',
        'picmsst' => 'float',
        'qcom' => 'float',
        'qtrib' => 'float',
        'vbc' => 'float',
        'vbcst' => 'float',
        'vdesc' => 'float',
        'vfrete' => 'float',
        'vicms' => 'float',
        'vicmsst' => 'float',
        'voutro' => 'float',
        'vprod' => 'float',
        'vseg' => 'float',
        'vuncom' => 'float',
        'vuntrib' => 'float'
    ];


    // Chaves Estrangeiras
    public function NfeTerceiro()
    {
        return $this->belongsTo(NfeTerceiro::class, 'codnfeterceiro', 'codnfeterceiro');
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