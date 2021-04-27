<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/Apr/2021 16:34:23
 */

namespace Mg\Stone;

use Mg\MgModel;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Stone\StoneTransacaoParcela;
use Mg\Stone\StoneBandeira;
use Mg\Stone\StoneFilial;
use Mg\Stone\StonePreTransacao;
use Mg\Usuario\Usuario;

class StoneTransacao extends MgModel
{
    protected $table = 'tblstonetransacao';
    protected $primaryKey = 'codstonetransacao';


    protected $fillable = [
        'autorizacao',
        'codstonebandeira',
        'codstonefilial',
        'codstonepretransacao',
        'conciliada',
        'inativo',
        'numerocartao',
        'pagador',
        'parcelas',
        'siclostransactionid',
        'status',
        'stonetransactionid',
        'tipo',
        'valor',
        'valorliquido'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codstonebandeira' => 'integer',
        'codstonefilial' => 'integer',
        'codstonepretransacao' => 'integer',
        'codstonetransacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'conciliada' => 'boolean',
        'parcelas' => 'integer',
        'status' => 'integer',
        'tipo' => 'integer',
        'valor' => 'float',
        'valorliquido' => 'float'
    ];


    // Chaves Estrangeiras
    public function StoneBandeira()
    {
        return $this->belongsTo(StoneBandeira::class, 'codstonebandeira', 'codstonebandeira');
    }

    public function StoneFilial()
    {
        return $this->belongsTo(StoneFilial::class, 'codstonefilial', 'codstonefilial');
    }

    public function StonePreTransacao()
    {
        return $this->belongsTo(StonePreTransacao::class, 'codstonepretransacao', 'codstonepretransacao');
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
    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codstonetransacao', 'codstonetransacao');
    }

    public function StoneTransacaoParcelaS()
    {
        return $this->hasMany(StoneTransacaoParcela::class, 'codstonetransacao', 'codstonetransacao');
    }

}