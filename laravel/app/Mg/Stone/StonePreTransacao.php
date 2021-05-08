<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/Apr/2021 16:34:33
 */

namespace Mg\Stone;

use Mg\MgModel;
use Mg\Stone\StoneTransacao;
use Mg\Negocio\Negocio;
use Mg\Stone\StoneFilial;
use Mg\Stone\StonePos;
use Mg\Usuario\Usuario;

class StonePreTransacao extends MgModel
{

    const TIPO_DEBITO = 1;
    const TIPO_CREDITO = 2;
    const TIPO_VOUCHER = 3;

    const TIPO_PARCELAMENTO_SEM_JUROS = 1;
    const TIPO_PARCELAMENTO_COM_JUROS = 2;

    protected $table = 'tblstonepretransacao';
    protected $primaryKey = 'codstonepretransacao';


    protected $fillable = [
        'ativa',
        'codnegocio',
        'codstonefilial',
        'codstonepos',
        'inativo',
        'parcelas',
        'pretransactionid',
        'processada',
        'status',
        'tipo',
        'tipoparcelamento',
        'titulo',
        'token',
        'valor'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'ativa' => 'boolean',
        'codnegocio' => 'integer',
        'codstonefilial' => 'integer',
        'codstonepos' => 'integer',
        'codstonepretransacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'parcelas' => 'integer',
        'processada' => 'boolean',
        'tipo' => 'integer',
        'tipoparcelamento' => 'integer',
        'token' => 'integer',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function StoneFilial()
    {
        return $this->belongsTo(StoneFilial::class, 'codstonefilial', 'codstonefilial');
    }

    public function StonePos()
    {
        return $this->belongsTo(StonePos::class, 'codstonepos', 'codstonepos');
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
    public function StoneTransacaoS()
    {
        return $this->hasMany(StoneTransacao::class, 'codstonepretransacao', 'codstonepretransacao');
    }

}
