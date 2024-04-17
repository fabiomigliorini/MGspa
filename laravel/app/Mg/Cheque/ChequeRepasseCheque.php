<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Apr/2024 12:21:21
 */

namespace Mg\Cheque;

use Mg\MgModel;
use Mg\Cheque\ChequeDevolucao;
use Mg\Cheque\Cheque;
use Mg\Cheque\ChequeRepasse;
use Mg\Usuario\Usuario;

class ChequeRepasseCheque extends MgModel
{
    protected $table = 'tblchequerepassecheque';
    protected $primaryKey = 'codchequerepassecheque';


    protected $fillable = [
        'codcheque',
        'codchequerepasse',
        'compensacao'
    ];

    protected $dates = [
        'alteracao',
        'compensacao',
        'criacao'
    ];

    protected $casts = [
        'codcheque' => 'integer',
        'codchequerepasse' => 'integer',
        'codchequerepassecheque' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Cheque()
    {
        return $this->belongsTo(Cheque::class, 'codcheque', 'codcheque');
    }

    public function ChequeRepasse()
    {
        return $this->belongsTo(ChequeRepasse::class, 'codchequerepasse', 'codchequerepasse');
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
    public function ChequeDevolucaoS()
    {
        return $this->hasMany(ChequeDevolucao::class, 'codchequerepassecheque', 'codchequerepassecheque');
    }

}