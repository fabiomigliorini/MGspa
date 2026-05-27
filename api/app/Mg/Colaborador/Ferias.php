<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:37:13
 */

namespace Mg\Colaborador;

use Mg\MgModel;
use Mg\Pessoa\Calendario\EventoCalendario;
use Mg\Colaborador\Colaborador;
use Mg\Usuario\Usuario;

class Ferias extends MgModel
{
    protected $table = 'tblferias';
    protected $primaryKey = 'codferias';


    protected $fillable = [
        'aquisitivofim',
        'aquisitivoinicio',
        'codcolaborador',
        'dias',
        'diasabono',
        'diasdescontados',
        'diasgozo',
        'gozofim',
        'gozoinicio',
        'observacoes',
        'prevista'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'aquisitivofim' => 'date',
        'aquisitivoinicio' => 'date',
        'codcolaborador' => 'integer',
        'codferias' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'dias' => 'integer',
        'diasabono' => 'integer',
        'diasdescontados' => 'integer',
        'diasgozo' => 'integer',
        'gozofim' => 'date',
        'gozoinicio' => 'date',
        'prevista' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'codcolaborador', 'codcolaborador');
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
    public function EventoCalendarioS()
    {
        return $this->hasMany(EventoCalendario::class, 'codferias', 'codferias');
    }

}
