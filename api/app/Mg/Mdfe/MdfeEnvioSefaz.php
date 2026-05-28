<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:32:03
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codmdfe' => 'integer',
        'codmdfeenviosefaz' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'cstatenvio' => 'integer',
        'cstatretorno' => 'integer',
        'recebimento' => 'datetime'
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
