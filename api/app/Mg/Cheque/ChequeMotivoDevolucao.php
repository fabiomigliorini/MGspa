<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:37:44
 */

namespace Mg\Cheque;

use Mg\MgModel;
use Mg\Cheque\ChequeDevolucao;
use Mg\Usuario\Usuario;

class ChequeMotivoDevolucao extends MgModel
{
    protected $table = 'tblchequemotivodevolucao';
    protected $primaryKey = 'codchequemotivodevolucao';


    protected $fillable = [
        'chequemotivodevolucao',
        'numero'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codchequemotivodevolucao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'numero' => 'integer'
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
    public function ChequeDevolucaoS()
    {
        return $this->hasMany(ChequeDevolucao::class, 'codchequemotivodevolucao', 'codchequemotivodevolucao');
    }

}
