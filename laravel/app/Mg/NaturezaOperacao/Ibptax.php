<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:19:27
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\NaturezaOperacao\Ncm;
use Mg\Usuario\Usuario;

class Ibptax extends MgModel
{
    protected $table = 'tblibptax';
    protected $primaryKey = 'codibptax';


    protected $fillable = [
        'chave',
        'codigo',
        'codncm',
        'descricao',
        'estadual',
        'ex',
        'fonte',
        'importadosfederal',
        'municipal',
        'nacionalfederal',
        'tipo',
        'versao',
        'vigenciafim',
        'vigenciainicio'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'vigenciafim',
        'vigenciainicio'
    ];

    protected $casts = [
        'codibptax' => 'integer',
        'codncm' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'estadual' => 'float',
        'importadosfederal' => 'float',
        'municipal' => 'float',
        'nacionalfederal' => 'float',
        'tipo' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Ncm()
    {
        return $this->belongsTo(Ncm::class, 'codncm', 'codncm');
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