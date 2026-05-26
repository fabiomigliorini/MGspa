<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:17:21
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\Produto\Produto;
// Ncm fica no mesmo namespace Mg\NaturezaOperacao
use Mg\Usuario\Usuario;

class Cest extends MgModel
{
    protected $table = 'tblcest';
    protected $primaryKey = 'codcest';


    protected $fillable = [
        'cest',
        'coddecreto271',
        'codncm',
        'descricao',
        'mva',
        'ncm'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcest' => 'integer',
        'coddecreto271' => 'integer',
        'codncm' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'mva' => 'float'
    ];


    // Chaves Estrangeiras
    public function Ncm()
    {
        return $this->belongsTo(Ncm::class, 'codncm', 'codncm');
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
    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codcest', 'codcest');
    }

}