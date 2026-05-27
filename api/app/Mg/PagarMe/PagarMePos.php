<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:35:51
 */

namespace Mg\PagarMe;

use Mg\MgModel;
use Mg\PagarMe\PagarMePagamento;
use Mg\PagarMe\PagarMePedido;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class PagarMePos extends MgModel
{
    protected $table = 'tblpagarmepos';
    protected $primaryKey = 'codpagarmepos';


    protected $fillable = [
        'apelido',
        'codfilial',
        'inativo',
        'serial'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codfilial' => 'integer',
        'codpagarmepos' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
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
    public function PagarMePagamentoS()
    {
        return $this->hasMany(PagarMePagamento::class, 'codpagarmepos', 'codpagarmepos');
    }

    public function PagarMePedidoS()
    {
        return $this->hasMany(PagarMePedido::class, 'codpagarmepos', 'codpagarmepos');
    }

}
