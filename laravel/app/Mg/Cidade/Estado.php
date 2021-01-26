<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Jan/2021 08:35:55
 */

namespace Mg\Cidade;

use Mg\MgModel;
use Mg\Cidade\Cidade;
use Mg\NFePHP\IbptCache;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;
use Mg\Mdfe\Mdfe;
use Mg\Mdfe\MdfeEstado;
use Mg\Veiculo\Veiculo;
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

    public function MdfeFimS()
    {
        return $this->hasMany(Mdfe::class, 'codestadofim', 'codestado');
    }

    public function MdfeEstadoS()
    {
        return $this->hasMany(MdfeEstado::class, 'codestado', 'codestado');
    }

    public function NotaFiscalPlacaS()
    {
        return $this->hasMany(NotaFiscal::class, 'codestadoplaca', 'codestado');
    }

    public function TributacaoNaturezaOperacaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codestado', 'codestado');
    }

    public function VeiculoS()
    {
        return $this->hasMany(Veiculo::class, 'codestado', 'codestado');
    }

}