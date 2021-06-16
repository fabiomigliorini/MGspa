<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jun/2021 14:11:23
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;
use Mg\Portador\Portador;

class TituloBoleto extends MgModel
{
    protected $table = 'tbltituloboleto';
    protected $primaryKey = 'codtituloboleto';


    protected $fillable = [
        'barras',
        'codportador',
        'codtitulo',
        'linhadigitavel',
        'nossonumero',
        'qrcodeemv',
        'qrcodetxid',
        'qrcodeurl'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codportador' => 'integer',
        'codtitulo' => 'integer',
        'codtituloboleto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
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