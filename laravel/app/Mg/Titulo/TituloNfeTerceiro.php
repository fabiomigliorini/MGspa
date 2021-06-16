<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jun/2021 08:42:33
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codnfeterceiro' => 'integer',
        'codtitulo' => 'integer',
        'codtitulonfeterceiro' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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