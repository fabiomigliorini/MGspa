<?php

namespace Mg\Ocorrencia;

use Mg\Filial\Filial;
use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\Pdv\Pdv;
use Mg\Usuario\Usuario;

/**
 * Livro de ocorrencias (TASK-205): eventos do PDV que o gerente confere —
 * item removido, quantidade/preco diminuidos, pagamento excluido, negocio
 * cancelado, pagamento/vale estornado, desconto acima do permitido e negocio
 * esquecido. Codigos em OcorrenciaService.
 */
class Ocorrencia extends MgModel
{
    protected $table = 'tblocorrencia';
    protected $primaryKey = 'codocorrencia';

    protected $fillable = [
        'antes',
        'codfilial',
        'codigo',
        'codnegocio',
        'codpdv',
        'codusuario',
        'codusuarioconferencia',
        'conferencia',
        'criacao',
        'depois',
        'descricao',
        'justificativa',
        'motivo',
        'observacao',
        'tabela',
        'tipo',
        'uuid',
        'valor',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'antes' => 'array',
        'codfilial' => 'integer',
        'codigo' => 'integer',
        'codnegocio' => 'integer',
        'codocorrencia' => 'integer',
        'codpdv' => 'integer',
        'codusuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuarioconferencia' => 'integer',
        'codusuariocriacao' => 'integer',
        'conferencia' => 'datetime',
        'criacao' => 'datetime',
        'depois' => 'array',
        'motivo' => 'integer',
        'tipo' => 'integer',
        'valor' => 'float',
    ];

    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuario', 'codusuario');
    }

    public function UsuarioConferencia()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioconferencia', 'codusuario');
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
