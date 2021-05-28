<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:22:51
 */

namespace Mg\Prancheta;

use Mg\MgModel;
use Mg\Prancheta\Prancheta;
use Mg\Produto\Produto;
use Mg\Usuario\Usuario;

class PranchetaProduto extends MgModel
{
    protected $table = 'tblpranchetaproduto';
    protected $primaryKey = 'codpranchetaproduto';


    protected $fillable = [
        'codprancheta',
        'codproduto',
        'inativo',
        'observacoes'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codprancheta' => 'integer',
        'codpranchetaproduto' => 'integer',
        'codproduto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Prancheta()
    {
        return $this->belongsTo(Prancheta::class, 'codprancheta', 'codprancheta');
    }

    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

}