<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:51:18
 */

namespace Mg\ValeCompra;

use Mg\MgModel;
use Mg\Titulo\Titulo;
use Mg\FormaPagamento\FormaPagamento;
use Mg\Usuario\Usuario;
use Mg\ValeCompra\ValeCompra;

class ValeCompraFormaPagamento extends MgModel
{
    protected $table = 'tblvalecompraformapagamento';
    protected $primaryKey = 'codvalecompraformapagamento';


    protected $fillable = [
        'codformapagamento',
        'codvalecompra',
        'valorpagamento'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codformapagamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalecompra' => 'integer',
        'codvalecompraformapagamento' => 'integer',
        'valorpagamento' => 'float'
    ];


    // Chaves Estrangeiras
    public function FormaPagamento()
    {
        return $this->belongsTo(FormaPagamento::class, 'codformapagamento', 'codformapagamento');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function ValeCompra()
    {
        return $this->belongsTo(ValeCompra::class, 'codvalecompra', 'codvalecompra');
    }


    // Tabelas Filhas
    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codvalecompraformapagamento', 'codvalecompraformapagamento');
    }

}