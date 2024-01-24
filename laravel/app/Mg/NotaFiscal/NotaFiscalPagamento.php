<?php
/**
 * Created by php artisan gerador:model.
 * Date: 24/Jan/2024 14:50:17
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Usuario\Usuario;

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

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

}