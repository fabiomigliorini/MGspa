<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:24:26
 */

namespace Mg\Tributacao;

use Mg\MgModel;
use Mg\Tributacao\NcmTributacao;
use Mg\Produto\Produto;
use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;
use Mg\Usuario\Usuario;

class Tributacao extends MgModel
{
    protected $table = 'tbltributacao';
    protected $primaryKey = 'codtributacao';


    protected $fillable = [
        'aliquotaicmsecf',
        'tributacao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codtributacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
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
    public function NcmTributacaoS()
    {
        return $this->hasMany(NcmTributacao::class, 'codtributacao', 'codtributacao');
    }

    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codtributacao', 'codtributacao');
    }

    public function TributacaoNaturezaOperacaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codtributacao', 'codtributacao');
    }

}
