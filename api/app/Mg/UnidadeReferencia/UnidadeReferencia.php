<?php

namespace Mg\UnidadeReferencia;

use Mg\MgModel;
use Mg\Cidade\Estado;
use Mg\Cidade\Cidade;

class UnidadeReferencia extends MgModel
{
    protected $table = 'tblunidadereferencia';
    protected $primaryKey = 'codunidadereferencia';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'codigo',
        'descricao',
        'ente',
        'codestado',
        'codcidade',
        'inativo',
    ];

    protected $casts = [
        'codunidadereferencia' => 'integer',
        'codestado' => 'integer',
        'codcidade' => 'integer',
        'inativo' => 'datetime',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    // Chaves Estrangeiras
    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }

    public function Cidade()
    {
        return $this->belongsTo(Cidade::class, 'codcidade', 'codcidade');
    }

    // Tabelas Filhas
    public function UnidadeReferenciaValorS()
    {
        return $this->hasMany(UnidadeReferenciaValor::class, 'codunidadereferencia', 'codunidadereferencia');
    }
}
