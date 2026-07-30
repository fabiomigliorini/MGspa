<?php

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;

class PeriodoColaboradorAcerto extends MgModel
{
    protected $table = 'tblperiodocolaboradoracerto';
    protected $primaryKey = 'codperiodocolaboradoracerto';

    // Forma de acerto (coluna forma)
    const FORMA_BEE = 'B';       // Recarga no cartão Bee
    const FORMA_DINHEIRO = 'D';  // Dinheiro (Caixa Financeiro)
    const FORMA_FOLHA = 'F';     // Desconto em folha

    const FORMA_DESCRICAO = [
        self::FORMA_BEE => 'Recarga Bee',
        self::FORMA_DINHEIRO => 'Dinheiro',
        self::FORMA_FOLHA => 'Desconto Folha',
    ];

    protected $fillable = [
        'codperiodocolaborador',
        'data',
        'forma',
        'rubricas',
        'creditos',
        'debitos',
        'saldo',
        'observacao',
        'inativo',
    ];

    protected $casts = [
        'codperiodocolaboradoracerto' => 'integer',
        'codperiodocolaborador' => 'integer',
        'data' => 'date',
        'rubricas' => 'float',
        'creditos' => 'float',
        'debitos' => 'float',
        'saldo' => 'float',
        'inativo' => 'datetime',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    protected $appends = ['usuariocriacao', 'usuarioalteracao', 'forma_descricao'];

    public function getFormaDescricaoAttribute()
    {
        return self::FORMA_DESCRICAO[$this->forma] ?? $this->forma;
    }

    // Chaves Estrangeiras
    public function PeriodoColaborador()
    {
        return $this->belongsTo(PeriodoColaborador::class, 'codperiodocolaborador', 'codperiodocolaborador');
    }

    // Movimentos de baixa de vale pendurados neste evento
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codperiodocolaboradoracerto', 'codperiodocolaboradoracerto');
    }
}
