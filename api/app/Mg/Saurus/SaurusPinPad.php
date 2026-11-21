<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:41:08
 */

namespace Mg\Saurus;

use Mg\MgModel;
use Mg\Saurus\SaurusPagamento;
use Mg\Usuario\Usuario;
use Mg\Saurus\SaurusPdv;

// Aparelho pareado a um PDV Saurus: id = uuid do aparelho na Saurus. Um PDV Saurus tem varios ao
// longo do tempo, um cobrando por vez: inativo = aparelho substituido (outro foi pareado no mesmo
// PDV Saurus, ou este foi pareado em outro). Nome, serial e filial sao da maquineta (TASK-264).
class SaurusPinPad extends MgModel
{
    protected $table = 'tblsauruspinpad';
    protected $primaryKey = 'codsauruspinpad';


    protected $fillable = [
        'codsauruspdv',
        'id',
        'inativo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codsauruspdv' => 'integer',
        'codsauruspinpad' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
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
