<?php

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Usuario\Usuario;

/**
 * Tentativa de entrega de um negocio (TASK-55, doc-6): entregue, entregue com
 * problema ou nao entregue. O negocio conta como entregue quando tem um
 * registro E ou P ativo (tblnegocio.entregue, gravado pelo NegocioEntregaService).
 */
class NegocioEntrega extends MgModel
{
    const RESULTADO_ENTREGUE = 'E';
    const RESULTADO_PROBLEMA = 'P';
    const RESULTADO_NAO_ENTREGUE = 'N';

    const RESULTADOS = [
        self::RESULTADO_ENTREGUE => 'Entregue',
        self::RESULTADO_PROBLEMA => 'Entregue com problema',
        self::RESULTADO_NAO_ENTREGUE => 'Não entregue',
    ];

    // motivo por resultado
    const MOTIVOS = [
        self::RESULTADO_PROBLEMA => [
            'E' => 'Mercadoria errada',
            'A' => 'Avariada',
            'F' => 'Faltando item',
        ],
        self::RESULTADO_NAO_ENTREGUE => [
            'U' => 'Cliente ausente',
            'R' => 'Recusou',
            'L' => 'Endereço não encontrado',
        ],
    ];

    protected $table = 'tblnegocioentrega';
    protected $primaryKey = 'codnegocioentrega';

    protected $fillable = [
        'codnegocio',
        'data',
        'resultado',
        'motivo',
        'recebedor',
        'observacao',
        'codigoconferido',
        'justificativa',
        'codrequisicao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codigoconferido' => 'boolean',
        'codnegocio' => 'integer',
        'codnegocioentrega' => 'integer',
        'codrequisicao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'inativo' => 'datetime',
    ];

    public function entregou(): bool
    {
        return in_array($this->resultado, [self::RESULTADO_ENTREGUE, self::RESULTADO_PROBLEMA]);
    }

    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
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
