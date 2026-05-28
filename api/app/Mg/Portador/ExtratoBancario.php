<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:35:06
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
        'codigoagenciacontrapartida',
        'codigoagenciaorigem',
        'codigobancocontrapartida',
        'codigohistorico',
        'codportador',
        'conciliado',
        'fitid',
        'indicadorsinallancamento',
        'indicadortipolancamento',
        'indicadortipopessoacontrapartida',
        'lancamento',
        'movimento',
        'numero',
        'numerocontacontrapartida',
        'numerocpfcnpjcontrapartida',
        'numerolote',
        'observacoes',
        'textodescricaohistorico',
        'textodvcontacontrapartida',
        'textoinformacaocomplementar',
        'valor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codextratobancario' => 'integer',
        'codextratobancariotipomovimento' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'conciliado' => 'boolean',
        'criacao' => 'datetime',
        'lancamento' => 'datetime',
        'movimento' => 'datetime',
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
