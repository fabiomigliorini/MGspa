<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:35:51
 */

namespace Mg\PagarMe;

use Mg\MgModel;
use Mg\PagarMe\PagarMePagamento;
use Mg\PagarMe\PagarMePedido;
use Mg\Maquineta\Maquineta;
use Mg\Usuario\Usuario;

// So' o codigo: ancora de pedido e pagamento PagarMe. Serial, filial (a conta da PagarMe), nome
// e inativo sao da maquineta (TASK-264).
class PagarMePos extends MgModel
{
    protected $table = 'tblpagarmepos';
    protected $primaryKey = 'codpagarmepos';


    protected $fillable = [];

    protected $casts = [
        'alteracao' => 'datetime',
        'codpagarmepos' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
    ];


    // a maquineta deste POS
    public function Maquineta()
    {
        return $this->hasOne(Maquineta::class, 'codpagarmepos', 'codpagarmepos');
    }

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
    public function PagarMePagamentoS()
    {
        return $this->hasMany(PagarMePagamento::class, 'codpagarmepos', 'codpagarmepos');
    }

    public function PagarMePedidoS()
    {
        return $this->hasMany(PagarMePedido::class, 'codpagarmepos', 'codpagarmepos');
    }

}
