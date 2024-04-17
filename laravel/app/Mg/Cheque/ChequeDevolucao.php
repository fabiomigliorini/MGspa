<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Apr/2024 12:20:30
 */

namespace Mg\Cheque;

use Mg\MgModel;
use Mg\Cheque\ChequeMotivoDevolucao;
use Mg\Cheque\ChequeRepasseCheque;
use Mg\Usuario\Usuario;

class ChequeDevolucao extends MgModel
{
    protected $table = 'tblchequedevolucao';
    protected $primaryKey = 'codchequedevolucao';


    protected $fillable = [
        'codchequemotivodevolucao',
        'codchequerepassecheque',
        'data',
        'observacoes'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'data'
    ];

    protected $casts = [
        'codchequedevolucao' => 'integer',
        'codchequemotivodevolucao' => 'integer',
        'codchequerepassecheque' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function ChequeMotivoDevolucao()
    {
        return $this->belongsTo(ChequeMotivoDevolucao::class, 'codchequemotivodevolucao', 'codchequemotivodevolucao');
    }

    public function ChequeRepasseCheque()
    {
        return $this->belongsTo(ChequeRepasseCheque::class, 'codchequerepassecheque', 'codchequerepassecheque');
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