<?php
/**
 * Created by php artisan gerador:model.
 * Date: 19/Nov/2022 12:05:13
 */

namespace Mg\PagarMe;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\PagarMe\PagarMeBandeira;
use Mg\PagarMe\PagarMePedido;
use Mg\PagarMe\PagarMePos;
use Mg\Usuario\Usuario;

class PagarMePagamento extends MgModel
{
    protected $table = 'tblpagarmepagamento';
    protected $primaryKey = 'codpagarmepagamento';


    protected $fillable = [
        'autorizacao',
        'codfilial',
        'codpagarmebandeira',
        'codpagarmepedido',
        'codpagarmepos',
        'identificador',
        'idtransacao',
        'jurosloja',
        'nsu',
        'parcelas',
        'portador',
        'tipo',
        'transacao',
        'valorcancelamento',
        'valorpagamento'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'transacao'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codpagarmebandeira' => 'integer',
        'codpagarmepagamento' => 'integer',
        'codpagarmepedido' => 'integer',
        'codpagarmepos' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'identificador' => 'integer',
        'jurosloja' => 'boolean',
        'parcelas' => 'integer',
        'tipo' => 'integer',
        'valorcancelamento' => 'float',
        'valorpagamento' => 'float'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function PagarMeBandeira()
    {
        return $this->belongsTo(PagarMeBandeira::class, 'codpagarmebandeira', 'codpagarmebandeira');
    }

    public function PagarMePedido()
    {
        return $this->belongsTo(PagarMePedido::class, 'codpagarmepedido', 'codpagarmepedido');
    }

    public function PagarMePos()
    {
        return $this->belongsTo(PagarMePos::class, 'codpagarmepos', 'codpagarmepos');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

}