<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:52:33
 */

namespace Mg\NfeTerceiro;

use Mg\MgModel;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

class NfeTerceiroDuplicata extends MgModel
{
    protected $table = 'tblnfeterceiroduplicata';
    protected $primaryKey = 'codnfeterceiroduplicata';


    protected $fillable = [
        'codnfeterceiro',
        'codtitulo',
        'dvenc',
        'ndup',
        'vdup'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'dvenc'
    ];

    protected $casts = [
        'codnfeterceiro' => 'integer',
        'codnfeterceiroduplicata' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'vdup' => 'float'
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