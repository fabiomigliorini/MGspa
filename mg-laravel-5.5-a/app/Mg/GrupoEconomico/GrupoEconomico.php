<?php

namespace Mg\GrupoEconomico;

use Mg\MgModel;

class GrupoEconomico extends MGModel
{
    protected $table = 'tblgrupoeconomico';
    protected $primaryKey = 'codgrupoeconomico';
    protected $fillable = [
        'grupoeconomico',
        'observacoes',
        'inativo'
    ];
    protected $dates = [
        'inativo',
        'alteracao',
        'criacao'
    ];

    // Chaves Estrangeiras

    // Tabelas Filhas
    public function PedidoS()
    {
        return $this->hasMany(Pedido::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }

    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }

    public function MarcaS()
    {
        return $this->hasMany(Marca::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }


}
