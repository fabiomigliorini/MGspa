<?php

namespace Mg\Tributacao;

use App\Models\Usuario;
use Mg\Cidade\Cidade;
use Mg\Cidade\Estado;
use Mg\MgModel;
use Mg\Produto\TipoProduto;

class TributacaoRegra extends MgModel
{
    protected $table = 'tbltributacaoregra';
    protected $primaryKey = 'codtributacaoregra';

    protected $fillable = [
        'aliquota', 'basepercentual', 'beneficiocodigo', 'cclasstrib',
        'codcidadedestino', 'codestadodestino', 'codnaturezaoperacao',
        'codtipoproduto', 'codtributo', 'cst', 'geracredito', 'ncm',
        'observacoes', 'tipocliente', 'vigenciafim', 'vigenciainicio',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'vigenciafim' => 'datetime',
        'vigenciainicio' => 'datetime',
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
        'geracredito' => 'boolean',
    ];

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
        // Resolve via string (NaturezaOperacao será migrado em seguida)
        return $this->belongsTo('Mg\\NaturezaOperacao\\NaturezaOperacao', 'codnaturezaoperacao', 'codnaturezaoperacao');
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
