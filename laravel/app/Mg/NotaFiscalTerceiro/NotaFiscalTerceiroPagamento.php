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