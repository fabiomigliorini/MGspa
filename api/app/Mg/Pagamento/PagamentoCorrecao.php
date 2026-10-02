<?php

namespace Mg\Pagamento;

use Mg\MgModel;
use Mg\Usuario\Usuario;

/**
 * Trilha das correcoes de pagamento feitas na conferencia (M9 doc-3): o que
 * era, o que ficou e por que.
 */
class PagamentoCorrecao extends MgModel
{
    protected $table = 'tblpagamentocorrecao';
    protected $primaryKey = 'codpagamentocorrecao';

    protected $fillable = [
        'codpagamento',
        'antes',
        'depois',
        'justificativa',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'antes' => 'array',
        'codpagamento' => 'integer',
        'codpagamentocorrecao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'depois' => 'array',
    ];

    // Chaves Estrangeiras
    public function Pagamento()
    {
        return $this->belongsTo(Pagamento::class, 'codpagamento', 'codpagamento');
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
