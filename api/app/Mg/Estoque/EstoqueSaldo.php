<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:39:14
 */

namespace Mg\Estoque;

use Mg\MgModel;
use Mg\Estoque\EstoqueMes;
use Mg\Estoque\EstoqueSaldoConferencia;
use Mg\Estoque\EstoqueLocalProdutoVariacao;
use Mg\Usuario\Usuario;

class EstoqueSaldo extends MgModel
{
    protected $table = 'tblestoquesaldo';
    protected $primaryKey = 'codestoquesaldo';


    protected $fillable = [
        'codestoquelocalprodutovariacao',
        'customedio',
        'dataentrada',
        'fiscal',
        'saldoquantidade',
        'saldovalor',
        'ultimaconferencia'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codestoquelocalprodutovariacao' => 'integer',
        'codestoquesaldo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'customedio' => 'float',
        'dataentrada' => 'datetime',
        'fiscal' => 'boolean',
        'saldoquantidade' => 'float',
        'saldovalor' => 'float',
        'ultimaconferencia' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function EstoqueLocalProdutoVariacao()
    {
        return $this->belongsTo(EstoqueLocalProdutoVariacao::class, 'codestoquelocalprodutovariacao', 'codestoquelocalprodutovariacao');
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
    public function EstoqueMesS()
    {
        return $this->hasMany(EstoqueMes::class, 'codestoquesaldo', 'codestoquesaldo');
    }

    public function EstoqueSaldoConferenciaS()
    {
        return $this->hasMany(EstoqueSaldoConferencia::class, 'codestoquesaldo', 'codestoquesaldo');
    }


    // Customizado
    public function scopeFiscal($query) {
        $query->where("{$this->table}.fiscal", true);
    }

    public function scopeFisico($query) {
        $query->where("{$this->table}.fiscal", false);
    }
}
