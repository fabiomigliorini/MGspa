<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:40:43
 */

namespace Mg\Saurus;

use Mg\MgModel;
use Mg\Saurus\SaurusPedido;
use Mg\Saurus\SaurusPinPad;
use Mg\Maquineta\Maquineta;
use Mg\Usuario\Usuario;

// PDV Saurus: a credencial na Saurus (uuid, numero, chaves, contrato); a chavepublica vira o QR
// Code que o aparelho le. E' 1:1 com a maquineta, que guarda nome, filial e inativo (TASK-264). Os
// aparelhos pareados ficam em SaurusPinPadS.
class SaurusPdv extends MgModel
{
    protected $table = 'tblsauruspdv';
    protected $primaryKey = 'codsauruspdv';


    protected $fillable = [
        'autorizacao',
        'chavepublica',
        'contratoid',
        'id',
        'numero',
        'vencimento'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codsauruspdv' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'numero' => 'integer',
        'vencimento' => 'datetime'
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


    // a maquineta deste PDV Saurus
    public function Maquineta()
    {
        return $this->hasOne(Maquineta::class, 'codsauruspdv', 'codsauruspdv');
    }

    // Tabelas Filhas
    public function SaurusPedidoS()
    {
        return $this->hasMany(SaurusPedido::class, 'codsauruspdv', 'codsauruspdv');
    }

    public function SaurusPinPadS()
    {
        return $this->hasMany(SaurusPinPad::class, 'codsauruspdv', 'codsauruspdv');
    }

}
