<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Apr/2024 12:20:20
 */

namespace Mg\Cheque;

use Mg\MgModel;
use Mg\Cheque\ChequeEmitente;
use Mg\Cheque\ChequeRepasseCheque;
use Mg\Cobranca\Cobranca;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Banco\Banco;
use Mg\Pessoa\Pessoa;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

class Cheque extends MgModel
{
    protected $table = 'tblcheque';
    protected $primaryKey = 'codcheque';


    protected $fillable = [
        'agencia',
        'cancelamento',
        'cmc7',
        'codbanco',
        'codpessoa',
        'codtitulo',
        'contacorrente',
        'destino',
        'devolucao',
        'emissao',
        'emitente',
        'indstatus',
        'lancamento',
        'motivodevolucao',
        'numero',
        'observacao',
        'repasse',
        'valor',
        'vencimento'
    ];

    protected $dates = [
        'alteracao',
        'cancelamento',
        'criacao',
        'devolucao',
        'emissao',
        'lancamento',
        'repasse',
        'vencimento'
    ];

    protected $casts = [
        'codbanco' => 'integer',
        'codcheque' => 'integer',
        'codpessoa' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'indstatus' => 'integer',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Banco()
    {
        return $this->belongsTo(Banco::class, 'codbanco', 'codbanco');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
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
    public function ChequeEmitenteS()
    {
        return $this->hasMany(ChequeEmitente::class, 'codcheque', 'codcheque');
    }

    public function ChequeRepasseChequeS()
    {
        return $this->hasMany(ChequeRepasseCheque::class, 'codcheque', 'codcheque');
    }

    public function CobrancaS()
    {
        return $this->hasMany(Cobranca::class, 'codcheque', 'codcheque');
    }

    public function LiquidacaoTituloS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codcheque', 'codcheque');
    }

}