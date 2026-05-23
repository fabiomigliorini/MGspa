<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Jan/2024 17:28:29
 */

namespace Mg\PagarMe;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\PagarMe\PagarMeBandeira;
use Mg\PagarMe\PagarMePedido;
use Mg\PagarMe\PagarMePos;
use Mg\Usuario\Usuario;
use Mg\Pdv\Pdv;

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
        'codpdv',
        'identificador',
        'idtransacao',
        'jurosloja',
        'nome',
        'nsu',
        'parcelas',
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
        'codpdv' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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

    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
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