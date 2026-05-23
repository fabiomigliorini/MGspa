<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/Jan/2021 19:11:20
 */

namespace Mg\Mdfe;

use Mg\MgModel;
use Mg\Mdfe\Mdfe;
use Mg\Usuario\Usuario;

class MdfeEnvioSefaz extends MgModel
{
    protected $table = 'tblmdfeenviosefaz';
    protected $primaryKey = 'codmdfeenviosefaz';


    protected $fillable = [
        'codmdfe',
        'cstatenvio',
        'cstatretorno',
        'recebimento',
        'recibo',
        'xmotivo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'recebimento'
    ];

    protected $casts = [
        'codmdfe' => 'integer',
        'codmdfeenviosefaz' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cstatenvio' => 'integer',
        'cstatretorno' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Mdfe()
    {
        return $this->belongsTo(Mdfe::class, 'codmdfe', 'codmdfe');
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