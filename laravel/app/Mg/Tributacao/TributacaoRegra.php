<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Dec/2025 18:54:43
 */

namespace Mg\Tributacao;

use Mg\MgModel;
use Mg\Tributacao\EntreTributante;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Tributacao\Tributo;
use Mg\Usuario\Usuario;

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
        'codentetributante',
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
        'codentetributante' => 'integer',
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
    public function EntreTributante()
    {
        return $this->belongsTo(EntreTributante::class, 'codentetributante', 'codentetributante');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
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