<?php

namespace Mg\Veiculo;

use Mg\Usuario\Usuario;
use Mg\Cidade\Estado;
use Mg\MgModel;
use Mg\Pessoa\Pessoa;

class Veiculo extends MgModel
{
    protected $table = 'tblveiculo';
    protected $primaryKey = 'codveiculo';

    protected $fillable = [
        'capacidade',
        'capacidadem3',
        'codestado',
        'codpessoaproprietario',
        'codveiculotipo',
        'inativo',
        'placa',
        'renavam',
        'tara',
        'tipoproprietario',
        'veiculo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'capacidade' => 'integer',
        'capacidadem3' => 'integer',
        'codestado' => 'integer',
        'codpessoaproprietario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculo' => 'integer',
        'codveiculotipo' => 'integer',
        'tara' => 'integer',
        'tipoproprietario' => 'integer',
    ];

    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }

    public function PessoaProprietario()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoaproprietario', 'codpessoa');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function VeiculoTipo()
    {
        return $this->belongsTo(VeiculoTipo::class, 'codveiculotipo', 'codveiculotipo');
    }

    public function VeiculoConjuntoVeiculoS()
    {
        return $this->hasMany(VeiculoConjuntoVeiculo::class, 'codveiculo', 'codveiculo');
    }
}
