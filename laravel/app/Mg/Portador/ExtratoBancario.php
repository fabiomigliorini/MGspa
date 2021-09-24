<?php
/**
 * Created by php artisan gerador:model.
 * Date: 24/Sep/2021 15:12:31
 */

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Portador\ExtratoBancarioTipoMovimento;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;

class ExtratoBancario extends MgModel
{
    protected $table = 'tblextratobancario';
    protected $primaryKey = 'codextratobancario';


    protected $fillable = [
        'codextratobancariotipomovimento',
        'codportador',
        'fitid',
        'lancamento',
        'numero',
        'observacoes',
        'valor'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'lancamento'
    ];

    protected $casts = [
        'codextratobancario' => 'integer',
        'codextratobancariotipomovimento' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function ExtratoBancarioTipoMovimento()
    {
        return $this->belongsTo(ExtratoBancarioTipoMovimento::class, 'codextratobancariotipomovimento', 'codextratobancariotipomovimento');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
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