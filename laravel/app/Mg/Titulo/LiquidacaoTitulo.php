<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Feb/2026 15:29:00
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;
use Mg\Pessoa\Pessoa;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;
use Mg\Cheque\Cheque;
use Mg\PagarMe\PagarMePedido;
use Mg\Pdv\Pdv;
use Mg\Pix\Pix;
use Mg\Rh\Periodo;

class LiquidacaoTitulo extends MgModel
{
    protected $table = 'tblliquidacaotitulo';
    protected $primaryKey = 'codliquidacaotitulo';


    protected $fillable = [
        'autorizacao',
        'bandeira',
        'codcheque',
        'codpagarmepedido',
        'codpdv',
        'codperiodo',
        'codpessoa',
        'codpessoacartao',
        'codpix',
        'codportador',
        'codusuario',
        'codusuarioestorno',
        'credito',
        'debito',
        'estornado',
        'integracao',
        'observacao',
        'parcelas',
        'sistema',
        'tipo',
        'transacao',
        'valortotal'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'estornado',
        'sistema',
        'transacao'
    ];

    protected $casts = [
        'bandeira' => 'integer',
        'codcheque' => 'integer',
        'codliquidacaotitulo' => 'integer',
        'codpagarmepedido' => 'integer',
        'codpdv' => 'integer',
        'codperiodo' => 'integer',
        'codpessoa' => 'integer',
        'codpessoacartao' => 'integer',
        'codpix' => 'integer',
        'codportador' => 'integer',
        'codusuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuarioestorno' => 'integer',
        'credito' => 'float',
        'debito' => 'float',
        'integracao' => 'boolean',
        'parcelas' => 'integer',
        'tipo' => 'integer',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function Cheque()
    {
        return $this->belongsTo(Cheque::class, 'codcheque', 'codcheque');
    }

    public function PagarMePedido()
    {
        return $this->belongsTo(PagarMePedido::class, 'codpagarmepedido', 'codpagarmepedido');
    }

    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
    }

    public function Periodo()
    {
        return $this->belongsTo(Periodo::class, 'codperiodo', 'codperiodo');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaCartao()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoacartao', 'codpessoa');
    }

    public function Pix()
    {
        return $this->belongsTo(Pix::class, 'codpix', 'codpix');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuario', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioEstorno()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioestorno', 'codusuario');
    }


    // Tabelas Filhas
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codliquidacaotitulo', 'codliquidacaotitulo');
    }

}