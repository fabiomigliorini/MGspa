<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:52:02
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcobrancahistorico' => 'integer',
        'codcobrancahistoricotitulo' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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