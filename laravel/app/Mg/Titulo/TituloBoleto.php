<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jun/2021 08:40:55
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

class TituloBoleto extends MgModel
{
    protected $table = 'tbltituloboleto';
    protected $primaryKey = 'codtituloboleto';


    protected $fillable = [
        'barras',
        'codtitulo',
        'linhadigitavel',
        'qrcodeemv',
        'qrcodetxid',
        'qrcodeurl'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codtitulo' => 'integer',
        'codtituloboleto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
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