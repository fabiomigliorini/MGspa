<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Jul/2020 09:15:28
 */

namespace Mg\Cidade;

use Mg\MgModel;
use Mg\Cidade\Cidade;
use Mg\IbptCache\IbptCache;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;
use Mg\Cidade\Pais;
use Mg\Usuario\Usuario;

class Estado extends MgModel
{
    protected $table = 'tblestado';
    protected $primaryKey = 'codestado';


    protected $fillable = [
        'codigooficial',
        'codpais',
        'estado',
        'sigla'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codestado' => 'integer',
        'codigooficial' => 'integer',
        'codpais' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Pais()
    {
        return $this->belongsTo(Pais::class, 'codpais', 'codpais');
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
    public function CidadeS()
    {
        return $this->hasMany(Cidade::class, 'codestado', 'codestado');
    }

    public function IbptCacheS()
    {
        return $this->hasMany(IbptCache::class, 'codestado', 'codestado');
    }

    public function NotaFiscalS()
    {
        return $this->hasMany(NotaFiscal::class, 'codestadoplaca', 'codestado');
    }

    public function TributacaoNaturezaOperacaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codestado', 'codestado');
    }

}