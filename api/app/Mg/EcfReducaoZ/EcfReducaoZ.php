<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:26:09
 */

namespace Mg\EcfReducaoZ;

use Mg\MgModel;
use Mg\CupomFiscal\Ecf;
use Mg\Usuario\Usuario;

class EcfReducaoZ extends MgModel
{
    protected $table = 'tblecfreducaoz';
    protected $primaryKey = 'codecfreducaoz';


    protected $fillable = [
        'codecf',
        'coo',
        'cro',
        'crz',
        'grandetotal',
        'movimento',
        'observacoes'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codecf' => 'integer',
        'codecfreducaoz' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'coo' => 'integer',
        'criacao' => 'datetime',
        'cro' => 'integer',
        'crz' => 'integer',
        'grandetotal' => 'float',
        'movimento' => 'date'
    ];


    // Chaves Estrangeiras
    public function Ecf()
    {
        return $this->belongsTo(Ecf::class, 'codecf', 'codecf');
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
