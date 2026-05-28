<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:35:13
 */

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Portador\ExtratoBancarioPortadorMovimento;
use Mg\Titulo\MovimentoTitulo;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Pix\Pix;
use Mg\Portador\Portador;
use Mg\Portador\PortadorTransferencia;

class PortadorMovimento extends MgModel
{
    protected $table = 'tblportadormovimento';
    protected $primaryKey = 'codportadormovimento';


    protected $fillable = [
        'codmovimentotitulo',
        'codnegocioformapagamento',
        'codpix',
        'codportador',
        'codportadortransferencia',
        'conciliado',
        'inativo',
        'lancamento',
        'manual',
        'observacoes',
        'valor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codmovimentotitulo' => 'integer',
        'codnegocioformapagamento' => 'integer',
        'codpix' => 'integer',
        'codportador' => 'integer',
        'codportadormovimento' => 'integer',
        'codportadortransferencia' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'conciliado' => 'boolean',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'lancamento' => 'datetime',
        'manual' => 'boolean',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function MovimentoTitulo()
    {
        return $this->belongsTo(MovimentoTitulo::class, 'codmovimentotitulo', 'codmovimentotitulo');
    }

    public function NegocioFormaPagamento()
    {
        return $this->belongsTo(NegocioFormaPagamento::class, 'codnegocioformapagamento', 'codnegocioformapagamento');
    }

    public function Pix()
    {
        return $this->belongsTo(Pix::class, 'codpix', 'codpix');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function PortadorTransferencia()
    {
        return $this->belongsTo(PortadorTransferencia::class, 'codportadortransferencia', 'codportadortransferencia');
    }


    // Tabelas Filhas
    public function ExtratoBancarioPortadorMovimentoS()
    {
        return $this->hasMany(ExtratoBancarioPortadorMovimento::class, 'codportadormovimento', 'codportadormovimento');
    }

}
