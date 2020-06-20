<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:48:14
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Titulo\Titulo;
use Mg\Titulo\TipoMovimentoTitulo;
use Mg\Usuario\Usuario;

class TipoTitulo extends MgModel
{
    protected $table = 'tbltipotitulo';
    protected $primaryKey = 'codtipotitulo';


    protected $fillable = [
        'codtipomovimentotitulo',
        'credito',
        'debito',
        'observacoes',
        'pagar',
        'receber',
        'tipotitulo'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codtipomovimentotitulo' => 'integer',
        'codtipotitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'credito' => 'boolean',
        'debito' => 'boolean',
        'pagar' => 'boolean',
        'receber' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function TipoMovimentoTitulo()
    {
        return $this->belongsTo(TipoMovimentoTitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
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
    public function NaturezaOperacaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codtipotitulo', 'codtipotitulo');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codtipotitulo', 'codtipotitulo');
    }

}