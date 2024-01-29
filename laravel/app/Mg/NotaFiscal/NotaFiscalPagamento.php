<?php
/**
 * Created by php artisan gerador:model.
 * Date: 24/Jan/2024 15:40:53
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Usuario\Usuario;
use Mg\Pessoa\Pessoa;

class NotaFiscalPagamento extends MgModel
{
    protected $table = 'tblnotafiscalpagamento';
    protected $primaryKey = 'codnotafiscalpagamento';


    protected $fillable = [
        'autorizacao',
        'avista',
        'bandeira',
        'codnotafiscal',
        'codpessoa',
        'integracao',
        'tipo',
        'troco',
        'valorpagamento'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'avista' => 'boolean',
        'bandeira' => 'integer',
        'codnotafiscal' => 'integer',
        'codnotafiscalpagamento' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'integracao' => 'boolean',
        'tipo' => 'integer',
        'troco' => 'float',
        'valorpagamento' => 'float'
    ];


    // Chaves Estrangeiras
    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
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