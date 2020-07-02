<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Jul/2020 09:17:13
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\NaturezaOperacao\Cfop;
use Mg\Cidade\Estado;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Produto\TipoProduto;
use Mg\NaturezaOperacao\Tributacao;
use Mg\Usuario\Usuario;

class TributacaoNaturezaOperacao extends MgModel
{
    protected $table = 'tbltributacaonaturezaoperacao';
    protected $primaryKey = 'codtributacaonaturezaoperacao';


    protected $fillable = [
        'acumuladordominioprazo',
        'acumuladordominiovista',
        'bit',
        'certidaosefazmt',
        'codcfop',
        'codestado',
        'codnaturezaoperacao',
        'codtipoproduto',
        'codtributacao',
        'cofinscst',
        'cofinspercentual',
        'csllpercentual',
        'csosn',
        'fethabkg',
        'funruralpercentual',
        'historicodominio',
        'iagrokg',
        'icmsbase',
        'icmscst',
        'icmslpbase',
        'icmslppercentual',
        'icmslppercentualimportado',
        'icmspercentual',
        'ipicst',
        'irpjpercentual',
        'movimentacaocontabil',
        'movimentacaofisica',
        'ncm',
        'observacoesnf',
        'piscst',
        'pispercentual',
        'senarpercentual'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'acumuladordominioprazo' => 'integer',
        'acumuladordominiovista' => 'integer',
        'bit' => 'boolean',
        'certidaosefazmt' => 'boolean',
        'codcfop' => 'integer',
        'codestado' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codtipoproduto' => 'integer',
        'codtributacao' => 'integer',
        'codtributacaonaturezaoperacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cofinscst' => 'float',
        'cofinspercentual' => 'float',
        'csllpercentual' => 'float',
        'fethabkg' => 'float',
        'funruralpercentual' => 'float',
        'iagrokg' => 'float',
        'icmsbase' => 'float',
        'icmscst' => 'float',
        'icmslpbase' => 'float',
        'icmslppercentual' => 'float',
        'icmslppercentualimportado' => 'float',
        'icmspercentual' => 'float',
        'ipicst' => 'float',
        'irpjpercentual' => 'float',
        'movimentacaocontabil' => 'boolean',
        'movimentacaofisica' => 'boolean',
        'piscst' => 'float',
        'pispercentual' => 'float',
        'senarpercentual' => 'float'
    ];


    // Chaves Estrangeiras
    public function Cfop()
    {
        return $this->belongsTo(Cfop::class, 'codcfop', 'codcfop');
    }

    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function TipoProduto()
    {
        return $this->belongsTo(TipoProduto::class, 'codtipoproduto', 'codtipoproduto');
    }

    public function Tributacao()
    {
        return $this->belongsTo(Tributacao::class, 'codtributacao', 'codtributacao');
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