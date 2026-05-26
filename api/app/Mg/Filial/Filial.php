<?php

namespace Mg\Filial;

use Mg\Usuario\Usuario;
use Mg\MgModel;
use Mg\Pessoa\Pessoa;

class Filial extends MgModel
{
    public const CRT_SIMPLES = 1;
    public const CRT_SIMPLES_EXCESSO = 2;
    public const CRT_REGIME_NORMAL = 3;

    public const NFEAMBIENTE_PRODUCAO = 1;
    public const NFEAMBIENTE_HOMOLOGACAO = 2;

    protected $table = 'tblfilial';
    protected $primaryKey = 'codfilial';

    protected $fillable = [
        'acbrnfemonitorbloqueado',
        'acbrnfemonitorcaminho',
        'acbrnfemonitorcaminhorede',
        'acbrnfemonitorcodusuario',
        'acbrnfemonitorip',
        'acbrnfemonitorporta',
        'codempresa',
        'codpessoa',
        'crt',
        'dfe',
        'emitenfe',
        'empresadominio',
        'filial',
        'inativo',
        'nfcetoken',
        'nfcetokenid',
        'nfeambiente',
        'nfeserie',
        'odbcnumeronotafiscal',
        'pagarmeid',
        'pagarmesk',
        'senhacertificado',
        'stonecode',
        'tokenibpt',
        'ultimonsu',
        'validadecertificado',
    ];

    protected $casts = [
        'acbrnfemonitorcodusuario' => 'integer',
        'acbrnfemonitorporta' => 'integer',
        'codempresa' => 'integer',
        'codfilial' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'crt' => 'integer',
        'dfe' => 'boolean',
        'emitenfe' => 'boolean',
        'empresadominio' => 'float',
        'nfeambiente' => 'integer',
        'nfeserie' => 'integer',
        'stonecode' => 'float',
        'ultimonsu' => 'integer',
        'acbrnfemonitorbloqueado' => 'datetime',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'validadecertificado' => 'datetime',
    ];

    public function Empresa()
    {
        return $this->belongsTo(Empresa::class, 'codempresa', 'codempresa');
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
