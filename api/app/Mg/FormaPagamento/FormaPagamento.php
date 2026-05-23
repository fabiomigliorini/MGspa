<?php

namespace Mg\FormaPagamento;

use App\Models\Usuario;
use Mg\MgModel;

class FormaPagamento extends MgModel
{
    protected $table = 'tblformapagamento';
    protected $primaryKey = 'codformapagamento';

    protected $fillable = [
        'avista', 'boleto', 'diasentreparcelas', 'entrega', 'fechamento',
        'formapagamento', 'formapagamentoecf', 'inativo', 'integracao',
        'lio', 'notafiscal', 'parcelas', 'pix', 'safrapay', 'stone', 'valecompra',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'avista' => 'boolean',
        'boleto' => 'boolean',
        'codformapagamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'diasentreparcelas' => 'integer',
        'entrega' => 'boolean',
        'fechamento' => 'boolean',
        'integracao' => 'boolean',
        'lio' => 'boolean',
        'notafiscal' => 'boolean',
        'parcelas' => 'integer',
        'pix' => 'boolean',
        'safrapay' => 'boolean',
        'stone' => 'boolean',
        'valecompra' => 'boolean',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }
}
