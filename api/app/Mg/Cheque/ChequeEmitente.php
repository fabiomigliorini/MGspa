<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Apr/2024 12:20:47
 */

namespace Mg\Cheque;

use Mg\MgModel;
use Mg\Cheque\Cheque;
use Mg\Usuario\Usuario;

class ChequeEmitente extends MgModel
{
    protected $table = 'tblchequeemitente';
    protected $primaryKey = 'codchequeemitente';


    protected $fillable = [
        'cnpj',
        'codcheque',
        'emitente'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'cnpj' => 'float',
        'codcheque' => 'integer',
        'codchequeemitente' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Cheque()
    {
        return $this->belongsTo(Cheque::class, 'codcheque', 'codcheque');
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