<?php
/**
 * Created by php artisan gerador:model.
 * Date: 18/Jul/2020 13:36:10
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\Usuario\Usuario;

class NotaFiscalTerceiroPagamento extends MgModel
{
    const FORMA_DINHEIRO = 1;
    const FORMA_CHEQUE = 2;
    const FORMA_CARTAO_CREDITO = 3;
    const FORMA_CARTAO_DEBITO = 4;
    const FORMA_CREDITO_LOJA = 5;
    const FORMA_VALE_ALIMENTACAO = 10;
    const FORMA_VALE_REFEICAO = 11;
    const FORMA_VALE_PRESENTE = 12;
    const FORMA_VALE_COMBUSTIVEL = 13;
    const FORMA_OUTROS = 99;

    const BANDEIRA_VISA = 1;
    const BANDEIRA_MASTERCARD = 2;
    const BANDEIRA_AMERICAN_EXPRESS = 3;
    const BANDEIRA_SOROCRED = 4;
    const BANDEIRA_OUTROS = 99;

    protected $table = 'tblnotafiscalterceiropagamento';
    protected $primaryKey = 'codnotafiscalterceiropagamento';

    protected $fillable = [
        'autorizacao',
        'bandeira',
        'cnpj',
        'codnotafiscalterceiro',
        'forma',
        'valor'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'bandeira' => 'integer',
        'cnpj' => 'float',
        'codnotafiscalterceiro' => 'integer',
        'codnotafiscalterceiropagamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'forma' => 'integer',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function NotaFiscalTerceiro()
    {
        return $this->belongsTo(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
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
