<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:28:46
 */

namespace Mg\GrupoEconomico;

use Mg\MgModel;
use Mg\Marca\Marca;
use Mg\Pedido\Pedido;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class GrupoEconomico extends MgModel
{
    protected $table = 'tblgrupoeconomico';
    protected $primaryKey = 'codgrupoeconomico';


    protected $fillable = [
        'grupoeconomico',
        'inativo',
        'observacoes'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codgrupoeconomico' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


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
    public function MarcaS()
    {
        return $this->hasMany(Marca::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }

    public function PedidoS()
    {
        return $this->hasMany(Pedido::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }

    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }

}
