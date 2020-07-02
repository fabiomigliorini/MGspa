<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Jul/2020 09:17:05
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\NaturezaOperacao\NcmTributacao;
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codtributacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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