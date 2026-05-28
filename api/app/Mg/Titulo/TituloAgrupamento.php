<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:22:13
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\Titulo;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class TituloAgrupamento extends MgModel
{
    protected $table = 'tbltituloagrupamento';
    protected $primaryKey = 'codtituloagrupamento';


    protected $fillable = [
        'cancelamento',
        'codpessoa',
        'credito',
        'debito',
        'emissao',
        'observacao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'cancelamento' => 'date',
        'codpessoa' => 'integer',
        'codtituloagrupamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'credito' => 'float',
        'criacao' => 'datetime',
        'debito' => 'float',
        'emissao' => 'date'
    ];


    // Chaves Estrangeiras
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
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codtituloagrupamento', 'codtituloagrupamento');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codtituloagrupamento', 'codtituloagrupamento');
    }

}
