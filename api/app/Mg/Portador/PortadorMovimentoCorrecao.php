<?php

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Usuario\Usuario;

/**
 * Trilha da data alterada no movimento do portador (TASK-204): ajuste,
 * transferencia, item e bordero da maquineta de parceiro. O que era, o que
 * ficou e por que, como a tblpagamentocorrecao no pagamento.
 */
class PortadorMovimentoCorrecao extends MgModel
{
    protected $table = 'tblportadormovimentocorrecao';
    protected $primaryKey = 'codportadormovimentocorrecao';

    protected $fillable = [
        'codportadormovimento',
        'antes',
        'depois',
        'justificativa',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'antes' => 'array',
        'codportadormovimento' => 'integer',
        'codportadormovimentocorrecao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'depois' => 'array',
    ];

    // Chaves Estrangeiras
    public function PortadorMovimento()
    {
        return $this->belongsTo(PortadorMovimento::class, 'codportadormovimento', 'codportadormovimento');
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
