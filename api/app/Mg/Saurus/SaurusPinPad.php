<?php
/**
 * Created by php artisan gerador:model.
 * Date: 25/Jan/2025 11:05:21
 */

namespace Mg\Saurus;

use Mg\MgModel;
use Mg\Saurus\SaurusPagamento;
use Mg\Usuario\Usuario;
use Mg\Filial\Filial;
use Mg\Saurus\SaurusPdv;

class SaurusPinPad extends MgModel
{
    protected $table = 'tblsauruspinpad';
    protected $primaryKey = 'codsauruspinpad';


    protected $fillable = [
        'apelido',
        'codfilial',
        'codsauruspdv',
        'id',
        'inativo',
        'observacoes',
        'serial'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codsauruspdv' => 'integer',
        'codsauruspinpad' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function SaurusPdv()
    {
        return $this->belongsTo(SaurusPdv::class, 'codsauruspdv', 'codsauruspdv');
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
    public function SaurusPagamentoS()
    {
        return $this->hasMany(SaurusPagamento::class, 'codsauruspinpad', 'codsauruspinpad');
    }

}