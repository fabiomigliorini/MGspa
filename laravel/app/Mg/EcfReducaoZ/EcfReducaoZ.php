<?php
/**
 * Created by php artisan gerador:model.
 * Date: 13/Jul/2020 15:53:09
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

    protected $dates = [
        'alteracao',
        'criacao',
        'movimento'
    ];

    protected $casts = [
        'codecf' => 'integer',
        'codecfreducaoz' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'coo' => 'integer',
        'cro' => 'integer',
        'crz' => 'integer',
        'grandetotal' => 'float'
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