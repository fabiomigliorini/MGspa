<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:34:47
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

class TituloNfeTerceiro extends MgModel
{
    protected $table = 'tbltitulonfeterceiro';
    protected $primaryKey = 'codtitulonfeterceiro';


    protected $fillable = [
        'codnfeterceiro',
        'codtitulo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnfeterceiro' => 'integer',
        'codtitulo' => 'integer',
        'codtitulonfeterceiro' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function NfeTerceiro()
    {
        return $this->belongsTo(NfeTerceiro::class, 'codnfeterceiro', 'codnfeterceiro');
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
