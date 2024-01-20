<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jan/2024 15:00:51
 */

namespace Mg\Colaborador;

use Mg\MgModel;
use Mg\Colaborador\Cargo;
use Mg\Colaborador\Colaborador;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class ColaboradorCargo extends MgModel
{
    protected $table = 'tblcolaboradorcargo';
    protected $primaryKey = 'codcolaboradorcargo';


    protected $fillable = [
        'codcargo',
        'codcolaborador',
        'codfilial',
        'comissaoloja',
        'comissaovenda',
        'comissaoxerox',
        'fim',
        'gratificacao',
        'inicio',
        'observacoes'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'fim',
        'inicio'
    ];

    protected $casts = [
        'codcargo' => 'integer',
        'codcolaborador' => 'integer',
        'codcolaboradorcargo' => 'integer',
        'codfilial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'comissaoloja' => 'float',
        'comissaovenda' => 'float',
        'comissaoxerox' => 'float',
        'gratificacao' => 'float'
    ];


    // Chaves Estrangeiras
    public function Cargo()
    {
        return $this->belongsTo(Cargo::class, 'codcargo', 'codcargo');
    }

    public function Colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'codcolaborador', 'codcolaborador');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
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