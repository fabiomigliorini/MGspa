<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Dec/2025 23:31:08
 */

namespace Mg\Tributacao;

use Mg\MgModel;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Tributacao\Tributo;
use Mg\Usuario\Usuario;
use Mg\Cidade\Cidade;
use Mg\Cidade\Estado;
use Mg\Produto\TipoProduto;

class TributacaoRegra extends MgModel
{
    protected $table = 'tbltributacaoregra';
    protected $primaryKey = 'codtributacaoregra';


    protected $fillable = [
        'aliquota',
        'basepercentual',
        'beneficiocodigo',
        'cclasstrib',
        'codcidadedestino',
        'codestadodestino',
        'codnaturezaoperacao',
        'codtipoproduto',
        'codtributo',
        'cst',
        'geracredito',
        'ncm',
        'observacoes',
        'tipocliente',
        'vigenciafim',
        'vigenciainicio'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'vigenciafim',
        'vigenciainicio'
    ];

    protected $casts = [
        'aliquota' => 'float',
        'basepercentual' => 'float',
        'codcidadedestino' => 'integer',
        'codestadodestino' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codtipoproduto' => 'integer',
        'codtributacaoregra' => 'integer',
        'codtributo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'geracredito' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function CidadeDestino()
    {
        return $this->belongsTo(Cidade::class, 'codcidadedestino', 'codcidade');
    }

    public function EstadoDestino()
    {
        return $this->belongsTo(Estado::class, 'codestadodestino', 'codestado');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function TipoProduto()
    {
        return $this->belongsTo(TipoProduto::class, 'codtipoproduto', 'codtipoproduto');
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