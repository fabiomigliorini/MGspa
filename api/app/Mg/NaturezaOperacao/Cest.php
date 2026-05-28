<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:33:31
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\Produto\Produto;
use Mg\NaturezaOperacao\Ncm;
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codcest' => 'integer',
        'coddecreto271' => 'integer',
        'codncm' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
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
