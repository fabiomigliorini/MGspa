<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:27:16
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\NaturezaOperacao\Cest;
use Mg\NaturezaOperacao\Ibptax;
use Mg\Produto\Produto;
use Mg\NaturezaOperacao\RegulamentoIcmsStMt;
use Mg\Usuario\Usuario;

class Ncm extends MgModel
{
    protected $table = 'tblncm';
    protected $primaryKey = 'codncm';


    protected $fillable = [
        'bit',
        'codncm2020',
        'codncmpai',
        'descricao',
        'inativo',
        'ncm'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'bit' => 'boolean',
        'codncm' => 'integer',
        'codncm2020' => 'integer',
        'codncmpai' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function NcmPai()
    {
        return $this->belongsTo(Ncm::class, 'codncmpai', 'codncm');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function CestS()
    {
        return $this->hasMany(Cest::class, 'codncm', 'codncm');
    }

    public function IbptaxS()
    {
        return $this->hasMany(Ibptax::class, 'codncm', 'codncm');
    }

    public function NcmS()
    {
        return $this->hasMany(Ncm::class, 'codncmpai', 'codncm');
    }

    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codncm', 'codncm');
    }

    public function RegulamentoIcmsStMtS()
    {
        return $this->hasMany(RegulamentoIcmsStMt::class, 'codncm', 'codncm');
    }

}