<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:23:03
 */

namespace Mg\FormaPagamento;

use Mg\MgModel;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Pessoa\Pessoa;
use Mg\ValeCompra\ValeCompraFormaPagamento;
use Mg\Usuario\Usuario;

class FormaPagamento extends MgModel
{
    protected $table = 'tblformapagamento';
    protected $primaryKey = 'codformapagamento';


    protected $fillable = [
        'avista',
        'boleto',
        'diasentreparcelas',
        'entrega',
        'fechamento',
        'formapagamento',
        'formapagamentoecf',
        'inativo',
        'integracao',
        'lio',
        'notafiscal',
        'parcelas',
        'pix',
        'safrapay',
        'stone',
        'valecompra'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'avista' => 'boolean',
        'boleto' => 'boolean',
        'codformapagamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'diasentreparcelas' => 'integer',
        'entrega' => 'boolean',
        'fechamento' => 'boolean',
        'inativo' => 'datetime',
        'integracao' => 'boolean',
        'lio' => 'boolean',
        'notafiscal' => 'boolean',
        'parcelas' => 'integer',
        'pix' => 'boolean',
        'safrapay' => 'boolean',
        'stone' => 'boolean',
        'valecompra' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codformapagamento', 'codformapagamento');
    }

    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codformapagamento', 'codformapagamento');
    }

    public function ValeCompraFormaPagamentoS()
    {
        return $this->hasMany(ValeCompraFormaPagamento::class, 'codformapagamento', 'codformapagamento');
    }

}
