<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Dec/2020 16:37:48
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
        'lio',
        'notafiscal',
        'parcelas',
        'pix',
        'valecompra'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'avista' => 'boolean',
        'boleto' => 'boolean',
        'codformapagamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'diasentreparcelas' => 'integer',
        'entrega' => 'boolean',
        'fechamento' => 'boolean',
        'lio' => 'boolean',
        'notafiscal' => 'boolean',
        'parcelas' => 'integer',
        'pix' => 'boolean',
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