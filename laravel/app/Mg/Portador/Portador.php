<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 12:25:31
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
use Mg\Banco\Banco;
use Mg\Filial\Filial;

class Portador extends MgModel
{
    protected $table = 'tblportador';
    protected $primaryKey = 'codportador';


    protected $fillable = [
        'agencia',
        'agenciadigito',
        'carteira',
        'codbanco',
        'codfilial',
        'conta',
        'contadigito',
        'convenio',
        'diretorioremessa',
        'diretorioretorno',
        'emiteboleto',
        'portador'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'agencia' => 'integer',
        'agenciadigito' => 'integer',
        'carteira' => 'integer',
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

    public function LiquidacaoTituloS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codportador', 'codportador');
    }

    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codportador', 'codportador');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codportador', 'codportador');
    }

    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codportador', 'codportador');
    }

}