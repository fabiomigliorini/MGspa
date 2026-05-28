<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:26:52
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\BonificacaoEvento;
use Mg\Meta\MetaFilial;
use Mg\Meta\MetaUnidadeNegocio;
use Mg\Meta\MetaUnidadeNegocioPessoa;
use Mg\Meta\MetaUnidadeNegocioPessoaFixo\MetaUnidadeNegocioPessoaFixo;
use Mg\Meta\MetaVendedor;
use Mg\Usuario\Usuario;

class Meta extends MgModel
{
    protected $table = 'tblmeta';
    protected $primaryKey = 'codmeta';


    protected $fillable = [
        'inativo',
        'observacoes',
        'percentualcomissaosubgerentemeta',
        'percentualcomissaovendedor',
        'percentualcomissaovendedormeta',
        'percentualcomissaoxerox',
        'periodofinal',
        'periodoinicial',
        'premioprimeirovendedorfilial',
        'processando',
        'status'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codmeta' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'percentualcomissaosubgerentemeta' => 'float',
        'percentualcomissaovendedor' => 'float',
        'percentualcomissaovendedormeta' => 'float',
        'percentualcomissaoxerox' => 'float',
        'periodofinal' => 'datetime',
        'periodoinicial' => 'datetime',
        'premioprimeirovendedorfilial' => 'float',
        'processando' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function BonificacaoEventoS()
    {
        return $this->hasMany(BonificacaoEvento::class, 'codmeta', 'codmeta');
    }

    public function MetaFilialS()
    {
        return $this->hasMany(MetaFilial::class, 'codmeta', 'codmeta');
    }

    public function MetaUnidadeNegocioS()
    {
        return $this->hasMany(MetaUnidadeNegocio::class, 'codmeta', 'codmeta');
    }

    public function MetaUnidadeNegocioPessoaS()
    {
        return $this->hasMany(MetaUnidadeNegocioPessoa::class, 'codmeta', 'codmeta');
    }

    public function MetaUnidadeNegocioPessoaFixoS()
    {
        return $this->hasMany(MetaUnidadeNegocioPessoaFixo::class, 'codmeta', 'codmeta');
    }

    public function MetaVendedorS()
    {
        return $this->hasMany(MetaVendedor::class, 'codmeta', 'codmeta');
    }

}
