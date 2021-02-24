<?php
/**
 * Created by php artisan gerador:model.
 * Date: 24/Feb/2021 19:11:36
 */

namespace Mg\Veiculo;

use Mg\MgModel;
use Mg\Mdfe\MdfeVeiculo;
use Mg\Veiculo\VeiculoConjuntoVeiculo;
use Mg\Cidade\Estado;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;
use Mg\Veiculo\VeiculoTipo;

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
        'veiculo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'capacidade' => 'integer',
        'capacidadem3' => 'integer',
        'codestado' => 'integer',
        'codpessoaproprietario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculo' => 'integer',
        'codveiculotipo' => 'integer',
        'tara' => 'integer',
        'tipoproprietario' => 'integer'
    ];


    // Chaves Estrangeiras
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


    // Tabelas Filhas
    public function MdfeVeiculoS()
    {
        return $this->hasMany(MdfeVeiculo::class, 'codveiculo', 'codveiculo');
    }

    public function VeiculoConjuntoVeiculoS()
    {
        return $this->hasMany(VeiculoConjuntoVeiculo::class, 'codveiculo', 'codveiculo');
    }

}