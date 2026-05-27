<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:33:44
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codibptax' => 'integer',
        'codncm' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'estadual' => 'float',
        'importadosfederal' => 'float',
        'municipal' => 'float',
        'nacionalfederal' => 'float',
        'tipo' => 'integer',
        'vigenciafim' => 'date',
        'vigenciainicio' => 'date'
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
