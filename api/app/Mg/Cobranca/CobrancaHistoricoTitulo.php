<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:22:28
 */

namespace Mg\Cobranca;

use Mg\MgModel;
use Mg\Cobranca\CobrancaHistorico;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

class CobrancaHistoricoTitulo extends MgModel
{
    protected $table = 'tblcobrancahistoricotitulo';
    protected $primaryKey = 'codcobrancahistoricotitulo';


    protected $fillable = [
        'codcobrancahistorico',
        'codtitulo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcobrancahistorico' => 'integer',
        'codcobrancahistoricotitulo' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function CobrancaHistorico()
    {
        return $this->belongsTo(CobrancaHistorico::class, 'codcobrancahistorico', 'codcobrancahistorico');
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
