<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Apr/2024 12:21:02
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codchequemotivodevolucao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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