<?php
/**
 * Created by php artisan gerador:model.
 * Date: 18/Jul/2020 13:37:01
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroGrupo;
use Mg\Usuario\Usuario;

class NotaFiscalTerceiroItem extends MgModel
{
    protected $table = 'tblnotafiscalterceiroitem';
    protected $primaryKey = 'codnotafiscalterceiroitem';


    protected $fillable = [
        'adicional',
        'barras',
        'barrastributavel',
        'cest',
        'cfop',
        'codnotafiscalterceirogrupo',
        'cofinsbase',
        'cofinscst',
        'cofinspercentual',
        'cofinsvalor',
        'compoetotal',
        'csosn',
        'icmsbase',
        'icmsbasemodalidade',
        'icmscst',
        'icmspercentual',
        'icmsstbase',
        'icmsstbasemodalidade',
        'icmsstbasepercentualreducao',
        'icmsstmva',
        'icmsstpercentual',
        'icmsstvalor',
        'icmsvalor',
        'ipibase',
        'ipicst',
        'ipipercentual',
        'ipivalor',
        'ncm',
        'numero',
        'origem',
        'pisbase',
        'piscst',
        'pispercentual',
        'pisvalor',
        'produto',
        'quantidade',
        'quantidadetributavel',
        'referencia',
        'unidademedida',
        'unidademedidatributavel',
        'valordesconto',
        'valorfrete',
        'valoroutras',
        'valorproduto',
        'valorseguro',
        'valortotal',
        'valorunitario',
        'valorunitariotributavel'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'cfop' => 'integer',
        'codnotafiscalterceirogrupo' => 'integer',
        'codnotafiscalterceiroitem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cofinsbase' => 'float',
        'cofinscst' => 'integer',
        'cofinspercentual' => 'float',
        'cofinsvalor' => 'float',
        'compoetotal' => 'boolean',
        'csosn' => 'integer',
        'icmsbase' => 'float',
        'icmsbasemodalidade' => 'integer',
        'icmscst' => 'integer',
        'icmspercentual' => 'float',
        'icmsstbase' => 'float',
        'icmsstbasemodalidade' => 'integer',
        'icmsstbasepercentualreducao' => 'float',
        'icmsstmva' => 'float',
        'icmsstpercentual' => 'float',
        'icmsstvalor' => 'float',
        'icmsvalor' => 'float',
        'ipibase' => 'float',
        'ipicst' => 'integer',
        'ipipercentual' => 'float',
        'ipivalor' => 'float',
        'numero' => 'integer',
        'origem' => 'integer',
        'pisbase' => 'float',
        'piscst' => 'integer',
        'pispercentual' => 'float',
        'pisvalor' => 'float',
        'quantidade' => 'float',
        'quantidadetributavel' => 'float',
        'valordesconto' => 'float',
        'valorfrete' => 'float',
        'valoroutras' => 'float',
        'valorproduto' => 'float',
        'valorseguro' => 'float',
        'valortotal' => 'float',
        'valorunitario' => 'float',
        'valorunitariotributavel' => 'float'
    ];


    // Chaves Estrangeiras
    public function NotaFiscalTerceiroGrupo()
    {
        return $this->belongsTo(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
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