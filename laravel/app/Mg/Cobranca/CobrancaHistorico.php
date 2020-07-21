<?php
/**
 * Created by php artisan gerador:model.
 * Date: 21/Jul/2020 11:57:11
 */

namespace Mg\Cobranca;

use Mg\MgModel;
use Mg\Cobranca\CobrancaHistoricoTitulo;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class CobrancaHistorico extends MgModel
{
    protected $table = 'tblcobrancahistorico';
    protected $primaryKey = 'codcobrancahistorico';


    protected $fillable = [
        'codpessoa',
        'emailautomatico',
        'historico'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcobrancahistorico' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'emailautomatico' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function CobrancaHistoricoTituloS()
    {
        return $this->hasMany(CobrancaHistoricoTitulo::class, 'codcobrancahistorico', 'codcobrancahistorico');
    }

}