<?php
/**
 * Created by php artisan gerador:model.
 * Date: 25/Jan/2025 11:05:48
 */

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Titulo\Titulo;
use Mg\Portador\PortadorMovimento;
use Mg\Titulo\MovimentoTitulo;
use Mg\FormaPagamento\FormaPagamento;
use Mg\Negocio\Negocio;
use Mg\Usuario\Usuario;
use Mg\Lio\LioPedido;
use Mg\Pix\PixCob;
use Mg\Stone\StoneTransacao;
use Mg\PagarMe\PagarMePedido;
use Mg\Pessoa\Pessoa;
use Mg\Saurus\SaurusPedido;

class NegocioFormaPagamento extends MgModel
{
    protected $table = 'tblnegocioformapagamento';
    protected $primaryKey = 'codnegocioformapagamento';


    protected $fillable = [
        'autorizacao',
        'avista',
        'bandeira',
        'codformapagamento',
        'codliopedido',
        'codnegocio',
        'codpagarmepedido',
        'codpessoa',
        'codpixcob',
        'codsauruspedido',
        'codstonetransacao',
        'codtitulo',
        'integracao',
        'parcelas',
        'tipo',
        'uuid',
        'valorjuros',
        'valorpagamento',
        'valorparcela',
        'valortotal',
        'valortroco'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'avista' => 'boolean',
        'bandeira' => 'integer',
        'codformapagamento' => 'integer',
        'codliopedido' => 'integer',
        'codnegocio' => 'integer',
        'codnegocioformapagamento' => 'integer',
        'codpagarmepedido' => 'integer',
        'codpessoa' => 'integer',
        'codpixcob' => 'integer',
        'codsauruspedido' => 'integer',
        'codstonetransacao' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'integracao' => 'boolean',
        'parcelas' => 'integer',
        'tipo' => 'integer',
        'valorjuros' => 'float',
        'valorpagamento' => 'float',
        'valorparcela' => 'float',
        'valortotal' => 'float',
        'valortroco' => 'float'
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

    public function PagarMePedido()
    {
        return $this->belongsTo(PagarMePedido::class, 'codpagarmepedido', 'codpagarmepedido');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PixCob()
    {
        return $this->belongsTo(PixCob::class, 'codpixcob', 'codpixcob');
    }

    public function SaurusPedido()
    {
        return $this->belongsTo(SaurusPedido::class, 'codsauruspedido', 'codsauruspedido');
    }

    public function StoneTransacao()
    {
        return $this->belongsTo(StoneTransacao::class, 'codstonetransacao', 'codstonetransacao');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
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
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codnegocioformapagamento', 'codnegocioformapagamento');
    }

    public function PortadorMovimentoS()
    {
        return $this->hasMany(PortadorMovimento::class, 'codnegocioformapagamento', 'codnegocioformapagamento');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codnegocioformapagamento', 'codnegocioformapagamento');
    }

}