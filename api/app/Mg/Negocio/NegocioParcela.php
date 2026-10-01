<?php

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

/**
 * O que a venda deixou para depois (M4 do plano doc-3). Vira titulo ao
 * fechar (tbltitulo.codnegocioparcela). Condicoes em NegocioParcelaService.
 * uuidforma = forma do PDV antigo que gerou a parcela (some no M5).
 */
class NegocioParcela extends MgModel
{
    protected $table = 'tblnegocioparcela';
    protected $primaryKey = 'codnegocioparcela';

    protected $fillable = [
        'uuid',
        'codnegocio',
        'condicao',
        'numero',
        'vencimento',
        'valor',
        'juros',
        'codtitulo',
        'uuidforma',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnegocio' => 'integer',
        'codnegocioparcela' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'juros' => 'float',
        'numero' => 'integer',
        'valor' => 'float',
        'vencimento' => 'date',
    ];

    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
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
    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codnegocioparcela', 'codnegocioparcela');
    }
}
