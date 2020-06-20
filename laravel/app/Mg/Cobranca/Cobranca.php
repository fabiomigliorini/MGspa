<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:45:27
 */

namespace Mg\Cobranca;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;
use Mg\Cheque\Cheque;
use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

class Cobranca extends MgModel
{
    protected $table = 'tblcobranca';
    protected $primaryKey = 'codcobranca';


    protected $fillable = [
        'acertado',
        'agendamento',
        'codcheque',
        'codportador',
        'codtitulo',
        'creditoacerto',
        'debitoacerto',
        'observacoes',
        'posicao',
        'reagendamento'
    ];

    protected $dates = [
        'agendamento',
        'alteracao',
        'criacao',
        'reagendamento'
    ];

    protected $casts = [
        'acertado' => 'boolean',
        'codcheque' => 'integer',
        'codcobranca' => 'integer',
        'codportador' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'creditoacerto' => 'float',
        'debitoacerto' => 'float'
    ];


    // Chaves Estrangeiras
    public function Cheque()
    {
        return $this->belongsTo(Cheque::class, 'codcheque', 'codcheque');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
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
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codcobranca', 'codcobranca');
    }

}