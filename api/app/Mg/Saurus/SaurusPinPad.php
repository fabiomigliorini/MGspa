<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:41:08
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codfilial' => 'integer',
        'codsauruspdv' => 'integer',
        'codsauruspinpad' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
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
