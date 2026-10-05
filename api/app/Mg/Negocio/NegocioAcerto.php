<?php

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

/**
 * Destino da diferenca de uma venda desbalanceada (M9 doc-3). valor com
 * sinal: positivo = faltou pagar; negativo = pagou a mais. Destino P perdao,
 * C vale do colaborador, D duplicata do cliente, R credito do cliente.
 */
class NegocioAcerto extends MgModel
{
    const DESTINO_PERDAO = 'P';
    const DESTINO_COLABORADOR = 'C';
    const DESTINO_DUPLICATA = 'D';
    const DESTINO_CREDITO = 'R';

    const DESTINOS = [
        self::DESTINO_PERDAO => 'Perdão',
        self::DESTINO_COLABORADOR => 'Vale do colaborador',
        self::DESTINO_DUPLICATA => 'Duplicata do cliente',
        self::DESTINO_CREDITO => 'Crédito do cliente',
    ];

    protected $table = 'tblnegocioacerto';
    protected $primaryKey = 'codnegocioacerto';

    protected $fillable = [
        'codnegocio',
        'valor',
        'destino',
        'codpessoa',
        'codtitulo',
        'justificativa',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnegocio' => 'integer',
        'codnegocioacerto' => 'integer',
        'codpessoa' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'valor' => 'float',
    ];

    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }
}
