<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:37:00
 */

namespace Mg\Pdv;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\PagarMe\PagarMePagamento;
use Mg\PagarMe\PagarMePedido;
use Mg\Pix\Pix;
use Mg\Pix\PixCob;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;
use Mg\Portador\Portador;
use Mg\Filial\Setor;
use Mg\Estoque\EstoqueLocal;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Maquineta\Maquineta;

class Pdv extends MgModel
{
    protected $table = 'tblpdv';
    protected $primaryKey = 'codpdv';


    protected $fillable = [
        'alocacao',
        'apelido',
        'codestoquelocal',
        'codfilial',
        'codmaquineta',
        'codnaturezaoperacao',
        'codportador',
        'codportadorpix',
        'codsetor',
        'desktop',
        'impressora',
        'inativo',
        'minutosesquecido',
        'monitoramento',
        'navegador',
        'observacoes',
        'plataforma',
        'uuid',
        'versaonavegador'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codestoquelocal' => 'integer',
        'codfilial' => 'integer',
        'codmaquineta' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codpdv' => 'integer',
        'codportador' => 'integer',
        'codportadorpix' => 'integer',
        'codsetor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'desktop' => 'boolean',
        'inativo' => 'datetime',
        'sincronizacaocompleta' => 'datetime',
        'minutosesquecido' => 'integer',
        'monitoramento' => 'date:Y-m-d'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function Maquineta()
    {
        return $this->belongsTo(Maquineta::class, 'codmaquineta', 'codmaquineta');
    }

    public function PortadorPix()
    {
        return $this->belongsTo(Portador::class, 'codportadorpix', 'codportador');
    }

    public function Setor()
    {
        return $this->belongsTo(Setor::class, 'codsetor', 'codsetor');
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

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codpdv', 'codpdv');
    }

    public function PagarMePagamentoS()
    {
        return $this->hasMany(PagarMePagamento::class, 'codpdv', 'codpdv');
    }

    public function PagarMePedidoS()
    {
        return $this->hasMany(PagarMePedido::class, 'codpdv', 'codpdv');
    }

    public function PixS()
    {
        return $this->hasMany(Pix::class, 'codpdv', 'codpdv');
    }

    public function PixCobS()
    {
        return $this->hasMany(PixCob::class, 'codpdv', 'codpdv');
    }

    // IP e localizacao de cada sincronizacao; o atual e' a ultima
    public function PdvLocalizacaoS()
    {
        return $this->hasMany(PdvLocalizacao::class, 'codpdv', 'codpdv');
    }

    public function UltimaLocalizacao()
    {
        return $this->hasOne(PdvLocalizacao::class, 'codpdv', 'codpdv')->latestOfMany('codpdvlocalizacao');
    }

}
