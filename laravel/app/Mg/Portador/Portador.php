<?php
/**
 * Created by php artisan gerador:model.
 * Date: 19/May/2025 15:57:17
 */

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Boleto\BoletoRetorno;
use Mg\Cheque\ChequeRepasse;
use Mg\Cobranca\Cobranca;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;
use Mg\Pix\PixCob;
use Mg\Pix\Pix;
use Mg\Titulo\TituloBoleto;
use Mg\Portador\ExtratoBancario;
use Mg\Portador\PortadorMovimento;
use Mg\Portador\PortadorTransferencia;
use Mg\Pdv\Pdv;
use Mg\Portador\PortadorSaldo;
use Mg\Banco\Banco;
use Mg\Filial\Filial;

class Portador extends MgModel
{
    // TODO: passar constante para Service
    const CARTEIRA = 999;

    protected $table = 'tblportador';
    protected $primaryKey = 'codportador';


    protected $fillable = [
        'agencia',
        'agenciadigito',
        'bbclientid',
        'bbclientsecret',
        'bbdevappkey',
        'bbtoken',
        'bbtokenexpiracao',
        'carteira',
        'carteiravariacao',
        'codbanco',
        'codfilial',
        'conta',
        'contadigito',
        'convenio',
        'diretorioremessa',
        'diretorioretorno',
        'emiteboleto',
        'inativo',
        'pixdict',
        'portador'
    ];

    protected $dates = [
        'alteracao',
        'bbtokenexpiracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'agencia' => 'integer',
        'agenciadigito' => 'integer',
        'carteira' => 'integer',
        'carteiravariacao' => 'integer',
        'codbanco' => 'integer',
        'codfilial' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'conta' => 'integer',
        'contadigito' => 'integer',
        'convenio' => 'float',
        'emiteboleto' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Banco()
    {
        return $this->belongsTo(Banco::class, 'codbanco', 'codbanco');
    }

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
    public function BoletoRetornoS()
    {
        return $this->hasMany(BoletoRetorno::class, 'codportador', 'codportador');
    }

    public function ChequeRepasseS()
    {
        return $this->hasMany(ChequeRepasse::class, 'codportador', 'codportador');
    }

    public function CobrancaS()
    {
        return $this->hasMany(Cobranca::class, 'codportador', 'codportador');
    }

    public function ExtratoBancarioS()
    {
        return $this->hasMany(ExtratoBancario::class, 'codportador', 'codportador');
    }

    public function LiquidacaoTituloS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codportador', 'codportador');
    }

    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codportador', 'codportador');
    }

    public function PdvS()
    {
        return $this->hasMany(Pdv::class, 'codportador', 'codportador');
    }

    public function PixS()
    {
        return $this->hasMany(Pix::class, 'codportador', 'codportador');
    }

    public function PixCobS()
    {
        return $this->hasMany(PixCob::class, 'codportador', 'codportador');
    }

    public function PortadorMovimentoS()
    {
        return $this->hasMany(PortadorMovimento::class, 'codportador', 'codportador');
    }

    public function PortadorSaldoS()
    {
        return $this->hasMany(PortadorSaldo::class, 'codportador', 'codportador');
    }

    public function PortadorTransferenciaDestinoS()
    {
        return $this->hasMany(PortadorTransferencia::class, 'codportadordestino', 'codportador');
    }

    public function PortadorTransferenciaOrigemS()
    {
        return $this->hasMany(PortadorTransferencia::class, 'codportadororigem', 'codportador');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codportador', 'codportador');
    }

    public function TituloBoletoS()
    {
        return $this->hasMany(TituloBoleto::class, 'codportador', 'codportador');
    }

    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codportador', 'codportador');
    }

}