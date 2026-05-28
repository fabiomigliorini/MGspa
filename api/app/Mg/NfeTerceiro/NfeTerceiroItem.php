<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:27:16
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
        'codusuarioconferencia',
        'cofinscst',
        'cofinspcofins',
        'cofinsvbc',
        'cofinsvcofins',
        'complemento',
        'compoetotal',
        'conferencia',
        'cprod',
        'csosn',
        'cst',
        'infadprod',
        'ipicst',
        'ipipipi',
        'ipivbc',
        'ipivipi',
        'margem',
        'modalidadeicmsgarantido',
        'modbc',
        'modbcst',
        'ncm',
        'nitem',
        'observacoes',
        'orig',
        'picms',
        'picmsst',
        'piscst',
        'pisppis',
        'pisvbc',
        'pisvpis',
        'pmvast',
        'predbc',
        'predbcst',
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

    protected $casts = [
        'alteracao' => 'datetime',
        'cfop' => 'integer',
        'codnfeterceiro' => 'integer',
        'codnfeterceiroitem' => 'integer',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuarioconferencia' => 'integer',
        'codusuariocriacao' => 'integer',
        'cofinscst' => 'integer',
        'cofinspcofins' => 'float',
        'cofinsvbc' => 'float',
        'cofinsvcofins' => 'float',
        'complemento' => 'float',
        'compoetotal' => 'boolean',
        'conferencia' => 'datetime',
        'criacao' => 'datetime',
        'ipicst' => 'integer',
        'ipipipi' => 'float',
        'ipivbc' => 'float',
        'ipivipi' => 'float',
        'margem' => 'float',
        'modalidadeicmsgarantido' => 'boolean',
        'modbc' => 'integer',
        'modbcst' => 'integer',
        'nitem' => 'integer',
        'orig' => 'integer',
        'picms' => 'float',
        'picmsst' => 'float',
        'piscst' => 'integer',
        'pisppis' => 'float',
        'pisvbc' => 'float',
        'pisvpis' => 'float',
        'pmvast' => 'float',
        'predbc' => 'float',
        'predbcst' => 'float',
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

    public function UsuarioConferencia()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioconferencia', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

}
