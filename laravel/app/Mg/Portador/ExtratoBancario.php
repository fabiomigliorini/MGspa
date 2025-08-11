<?php
/**
 * Created by php artisan gerador:model.
 * Date: 13/Oct/2021 14:25:44
 */

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Portador\ExtratoBancarioPortadorMovimento;
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
        'conciliado',
        'fitid',
        'dia',
        'numero',
        'observacoes',
        'valor',
        'indicadortipolancamento',
        'codigoagenciaorigem',
        'numerolote',
        'codigohistorico',
        'textodescricaohistorico',
        'indicadorsinallancamento',
        'textoinformacaocomplementar',
        'numerocpfcnpjcontrapartida',
        'indicadortipopessoacontrapartida',
        'codigobancocontrapartida',
        'numerocontacontrapartida',
        'textodvcontacontrapartida'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'dia',
        'movimento'
    ];

    protected $casts = [
        'codextratobancario' => 'integer',
        'codextratobancariotipomovimento' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'conciliado' => 'boolean',
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


    // Tabelas Filhas
    public function ExtratoBancarioPortadorMovimentoS()
    {
        return $this->hasMany(ExtratoBancarioPortadorMovimento::class, 'codextratobancario', 'codextratobancario');
    }

}