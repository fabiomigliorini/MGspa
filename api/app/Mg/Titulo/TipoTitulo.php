<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:22:06
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

class TipoTitulo extends MgModel
{
    protected $table = 'tbltipotitulo';
    protected $primaryKey = 'codtipotitulo';


    // o sinal com que o título nasce: a receber é positivo, a pagar é negativo
    const NATUREZA_RECEBER = 'R';
    const NATUREZA_PAGAR = 'P';

    protected $fillable = [
        'inativo',
        'movimentaportador',
        'natureza',
        'observacoes',
        'pagar',
        'receber',
        'tipotitulo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codtipotitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'movimentaportador' => 'boolean',
        'pagar' => 'boolean',
        'receber' => 'boolean'
    ];


    public function ehReceber(): bool
    {
        return $this->natureza === self::NATUREZA_RECEBER;
    }


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function NaturezaOperacaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codtipotitulo', 'codtipotitulo');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codtipotitulo', 'codtipotitulo');
    }

}
