<?php
/**
 * Created by php artisan gerador:model.
 * Date: 13/Oct/2021 14:29:32
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Portador\PortadorMovimento;
use Mg\Boleto\BoletoRetorno;
use Mg\Cobranca\Cobranca;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Portador\Portador;
use Mg\Titulo\TipoMovimentoTitulo;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloAgrupamento;
use Mg\Usuario\Usuario;
use Mg\Titulo\TituloBoleto;

class MovimentoTitulo extends MgModel
{
    protected $table = 'tblmovimentotitulo';
    protected $primaryKey = 'codmovimentotitulo';


    protected $fillable = [
        'codboletoretorno',
        'codcobranca',
        'codliquidacaotitulo',
        'codportador',
        'codtipomovimentotitulo',
        'codtitulo',
        'codtituloagrupamento',
        'codtituloboleto',
        'codtitulorelacionado',
        'credito',
        'debito',
        'historico',
        'sistema',
        'transacao'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'sistema',
        'transacao'
    ];

    protected $casts = [
        'codboletoretorno' => 'integer',
        'codcobranca' => 'integer',
        'codliquidacaotitulo' => 'integer',
        'codmovimentotitulo' => 'integer',
        'codportador' => 'integer',
        'codtipomovimentotitulo' => 'integer',
        'codtitulo' => 'integer',
        'codtituloagrupamento' => 'integer',
        'codtituloboleto' => 'integer',
        'codtitulorelacionado' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'credito' => 'float',
        'debito' => 'float'
    ];


    // Chaves Estrangeiras
    public function BoletoRetorno()
    {
        return $this->belongsTo(BoletoRetorno::class, 'codboletoretorno', 'codboletoretorno');
    }

    public function Cobranca()
    {
        return $this->belongsTo(Cobranca::class, 'codcobranca', 'codcobranca');
    }

    public function LiquidacaoTitulo()
    {
        return $this->belongsTo(LiquidacaoTitulo::class, 'codliquidacaotitulo', 'codliquidacaotitulo');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function TipoMovimentoTitulo()
    {
        return $this->belongsTo(TipoMovimentoTitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function TituloAgrupamento()
    {
        return $this->belongsTo(TituloAgrupamento::class, 'codtituloagrupamento', 'codtituloagrupamento');
    }

    public function TituloBoleto()
    {
        return $this->belongsTo(TituloBoleto::class, 'codtituloboleto', 'codtituloboleto');
    }

    public function TituloRelacionado()
    {
        return $this->belongsTo(Titulo::class, 'codtitulorelacionado', 'codtitulo');
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
    public function PortadorMovimentoS()
    {
        return $this->hasMany(PortadorMovimento::class, 'codmovimentotitulo', 'codmovimentotitulo');
    }

}