<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Apr/2024 12:21:12
 */

namespace Mg\Cheque;

use Mg\MgModel;
use Mg\Cheque\ChequeRepasseCheque;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;

class ChequeRepasse extends MgModel
{
    protected $table = 'tblchequerepasse';
    protected $primaryKey = 'codchequerepasse';


    protected $fillable = [
        'codportador',
        'data',
        'inativo',
        'observacoes'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'data',
        'inativo'
    ];

    protected $casts = [
        'codchequerepasse' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
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
    public function ChequeRepasseChequeS()
    {
        return $this->hasMany(ChequeRepasseCheque::class, 'codchequerepasse', 'codchequerepasse');
    }

}