<?php
/**
 * Created by php artisan gerador:model.
 * Date: 12/Mar/2021 23:04:17
 */

namespace Mg\Mdfe;

use Mg\MgModel;
use Mg\Mdfe\MdfeEstado;
use Mg\Mdfe\MdfeNfe;
use Mg\Mdfe\MdfeVeiculo;
use Mg\Mdfe\MdfeEnvioSefaz;
use Mg\Cidade\Cidade;
use Mg\Cidade\Estado;
use Mg\Filial\Filial;
use Mg\Mdfe\MdfeStatus;
use Mg\Usuario\Usuario;

class Mdfe extends MgModel
{
    const TIPO_EMITENTE_PRESTADOR_SERVICO = 1;
    const TIPO_EMITENTE_CARGA_PROPRIA = 2;
    const TIPO_EMITENTE_CTE_GLOBALIZADO = 3;

    const MODELO = 58;

    const MODAL_RODOVIARIO = 1;
    const MODAL_AEREO = 2;
    const MODAL_AQUAVIARIO = 3;
    const MODAL_FERROVIARIO = 4;

    const TIPO_EMISSAO_NORMAL = 1;
    const TIPO_EMISSAO_CONTINGENCIA = 2;

    protected $table = 'tblmdfe';
    protected $primaryKey = 'codmdfe';


    protected $fillable = [
        'chmdfe',
        'codcidadecarregamento',
        'codestadofim',
        'codfilial',
        'codmdfestatus',
        'emissao',
        'encerramento',
        'inativo',
        'informacoesadicionais',
        'informacoescomplementares',
        'inicioviagem',
        'justificativa',
        'modal',
        'modelo',
        'numero',
        'protocoloautorizacao',
        'protocolocancelamento',
        'serie',
        'tipoemissao',
        'tipoemitente',
        'tipotransportador'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'emissao',
        'encerramento',
        'inativo',
        'inicioviagem'
    ];

    protected $casts = [
        'codcidadecarregamento' => 'integer',
        'codestadofim' => 'integer',
        'codfilial' => 'integer',
        'codmdfe' => 'integer',
        'codmdfestatus' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'modal' => 'integer',
        'modelo' => 'integer',
        'numero' => 'integer',
        'protocoloautorizacao' => 'integer',
        'protocolocancelamento' => 'integer',
        'serie' => 'integer',
        'tipoemissao' => 'integer',
        'tipoemitente' => 'integer',
        'tipotransportador' => 'integer'
    ];


    // Chaves Estrangeiras
    public function CidadeCarregamento()
    {
        return $this->belongsTo(Cidade::class, 'codcidadecarregamento', 'codcidade');
    }

    public function EstadoFim()
    {
        return $this->belongsTo(Estado::class, 'codestadofim', 'codestado');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function MdfeStatus()
    {
        return $this->belongsTo(MdfeStatus::class, 'codmdfestatus', 'codmdfestatus');
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
    public function MdfeEnvioSefazS()
    {
        return $this->hasMany(MdfeEnvioSefaz::class, 'codmdfe', 'codmdfe');
    }

    public function MdfeEstadoS()
    {
        return $this->hasMany(MdfeEstado::class, 'codmdfe', 'codmdfe');
    }

    public function MdfeNfeS()
    {
        return $this->hasMany(MdfeNfe::class, 'codmdfe', 'codmdfe');
    }

    public function MdfeVeiculoS()
    {
        return $this->hasMany(MdfeVeiculo::class, 'codmdfe', 'codmdfe');
    }

}