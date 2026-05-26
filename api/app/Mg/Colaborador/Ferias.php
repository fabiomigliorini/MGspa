<?php
/**
 * Created by php artisan gerador:model.
 * Date: 10/Feb/2026 11:13:52
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

    protected $dates = [
        'alteracao',
        'aquisitivofim',
        'aquisitivoinicio',
        'criacao',
        'gozofim',
        'gozoinicio'
    ];

    protected $casts = [
        'codcolaborador' => 'integer',
        'codferias' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'dias' => 'integer',
        'diasabono' => 'integer',
        'diasdescontados' => 'integer',
        'diasgozo' => 'integer',
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