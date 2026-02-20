<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Feb/2026 17:00:31
 */

namespace Mg\Colaborador;

use Mg\MgModel;
use Mg\Colaborador\ColaboradorCargo;
use Mg\Pessoa\Calendario\EventoCalendario;
use Mg\Colaborador\Ferias;
use Mg\Rh\Indicador;
use Mg\Rh\PeriodoColaborador;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class Colaborador extends MgModel
{
    protected $table = 'tblcolaborador';
    protected $primaryKey = 'codcolaborador';


    protected $fillable = [
        'codfilial',
        'codpessoa',
        'contratacao',
        'experiencia',
        'googledrivefolderid',
        'numerocontabilidade',
        'numeroponto',
        'observacoes',
        'renovacaoexperiencia',
        'rescisao',
        'vinculo'
    ];

    protected $dates = [
        'alteracao',
        'contratacao',
        'criacao',
        'experiencia',
        'renovacaoexperiencia',
        'rescisao'
    ];

    protected $casts = [
        'codcolaborador' => 'integer',
        'codfilial' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'vinculo' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
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


    // Tabelas Filhas
    public function ColaboradorCargoS()
    {
        return $this->hasMany(ColaboradorCargo::class, 'codcolaborador', 'codcolaborador');
    }

    public function EventoCalendarioS()
    {
        return $this->hasMany(EventoCalendario::class, 'codcolaborador', 'codcolaborador');
    }

    public function FeriasS()
    {
        return $this->hasMany(Ferias::class, 'codcolaborador', 'codcolaborador');
    }

    public function IndicadorS()
    {
        return $this->hasMany(Indicador::class, 'codcolaborador', 'codcolaborador');
    }

    public function PeriodoColaboradorS()
    {
        return $this->hasMany(PeriodoColaborador::class, 'codcolaborador', 'codcolaborador');
    }

}