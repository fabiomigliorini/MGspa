<?php
/**
 * Created by php artisan gerador:model.
 * Date: 10/Feb/2026 11:12:35
 */

namespace Mg\Pessoa\Calendario;

use Mg\MgModel;
use Mg\Colaborador\Colaborador;
use Mg\Pessoa\Dependente;
use Mg\Colaborador\Ferias;
use Mg\Usuario\Usuario;

class EventoCalendario extends MgModel
{
    protected $table = 'tbleventocalendario';
    protected $primaryKey = 'codeventocalendario';


    protected $fillable = [
        'codcolaborador',
        'coddependente',
        'codferias',
        'dataevento',
        'googleeventid',
        'inativo',
        'observacoes',
        'recorrente',
        'status',
        'tipo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'dataevento',
        'inativo'
    ];

    protected $casts = [
        'codcolaborador' => 'integer',
        'coddependente' => 'integer',
        'codeventocalendario' => 'integer',
        'codferias' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'recorrente' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'codcolaborador', 'codcolaborador');
    }

    public function Dependente()
    {
        return $this->belongsTo(Dependente::class, 'coddependente', 'coddependente');
    }

    public function Ferias()
    {
        return $this->belongsTo(Ferias::class, 'codferias', 'codferias');
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