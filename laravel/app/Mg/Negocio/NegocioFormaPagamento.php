<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Dec/2020 16:37:40
 */

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Titulo\Titulo;
use Mg\FormaPagamento\FormaPagamento;
use Mg\Negocio\Negocio;
use Mg\Usuario\Usuario;
use Mg\Lio\LioPedido;
use Mg\Pix\PixCob;

class NegocioFormaPagamento extends MgModel
{
    protected $table = 'tblnegocioformapagamento';
    protected $primaryKey = 'codnegocioformapagamento';


    protected $fillable = [
        'codformapagamento',
        'codliopedido',
        'codnegocio',
        'codpixcob',
        'valorpagamento'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codformapagamento' => 'integer',
        'codliopedido' => 'integer',
        'codnegocio' => 'integer',
        'codnegocioformapagamento' => 'integer',
        'codpixcob' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'valorpagamento' => 'float'
    ];


    // Chaves Estrangeiras
    public function FormaPagamento()
    {
        return $this->belongsTo(FormaPagamento::class, 'codformapagamento', 'codformapagamento');
    }

    public function LioPedido()
    {
        return $this->belongsTo(LioPedido::class, 'codliopedido', 'codliopedido');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function PixCob()
    {
        return $this->belongsTo(PixCob::class, 'codpixcob', 'codpixcob');
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
    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codnegocioformapagamento', 'codnegocioformapagamento');
    }

}