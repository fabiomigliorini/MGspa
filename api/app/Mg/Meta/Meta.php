<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Feb/2026 21:56:57
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\MetaFilial;
use Mg\Meta\MetaVendedor;
use Mg\Meta\BonificacaoEvento;
use Mg\Meta\MetaUnidadeNegocio;
use Mg\Meta\MetaUnidadeNegocioPessoa;
use Mg\Meta\MetaUnidadeNegocioPessoaFixo;
use Mg\Usuario\Usuario;

class Meta extends MgModel
{
    protected $table = 'tblmeta';
    protected $primaryKey = 'codmeta';


    protected $fillable = [
        'inativo',
        'observacoes',
        'periodofinal',
        'periodoinicial',
        'processando',
        'status'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'periodofinal',
        'periodoinicial'
    ];

    protected $casts = [
        'codmeta' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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