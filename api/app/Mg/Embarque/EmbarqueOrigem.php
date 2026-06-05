<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:53
 */

namespace Mg\Embarque;

use Mg\MgModel;
use Mg\Embarque\Embarque;
use Mg\Fazenda\Plantio;

class EmbarqueOrigem extends MgModel
{
    protected $table = 'tblembarqueorigem';
    protected $primaryKey = 'codembarqueorigem';


    protected $fillable = [
        'codembarque',
        'codplantio',
        'quantidade',
        'tipo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codembarque' => 'integer',
        'codembarqueorigem' => 'integer',
        'codplantio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'quantidade' => 'float'
    ];


    // Chaves Estrangeiras
    public function Embarque()
    {
        return $this->belongsTo(Embarque::class, 'codembarque', 'codembarque');
    }

    public function Plantio()
    {
        return $this->belongsTo(Plantio::class, 'codplantio', 'codplantio');
    }

}
