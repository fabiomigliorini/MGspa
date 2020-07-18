<?php
/**
 * Created by php artisan gerador:model.
 * Date: 18/Jul/2020 13:36:54
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroGrupo;
use Mg\Produto\ProdutoBarra;
use Mg\Usuario\Usuario;

class NotaFiscalTerceiroProdutoBarra extends MgModel
{
    protected $table = 'tblnotafiscalterceiroprodutobarra';
    protected $primaryKey = 'codnotafiscalterceiroprodutobarra';


    protected $fillable = [
        'codnotafiscalterceirogrupo',
        'codprodutobarra',
        'complemento',
        'margem',
        'quantidade',
        'valorproduto'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codnotafiscalterceirogrupo' => 'integer',
        'codnotafiscalterceiroprodutobarra' => 'integer',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'complemento' => 'float',
        'margem' => 'float',
        'quantidade' => 'float',
        'valorproduto' => 'float'
    ];


    // Chaves Estrangeiras
    public function NotaFiscalTerceiroGrupo()
    {
        return $this->belongsTo(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
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