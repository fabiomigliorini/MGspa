<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jul/2020 15:27:10
 */

namespace Mg\NFePHP;

use Mg\MgModel;
use Mg\Cidade\Estado;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class IbptCache extends MgModel
{
    protected $table = 'tblibptcache';
    protected $primaryKey = 'codibptcache';


    protected $fillable = [
        'chave',
        'codestado',
        'codfilial',
        'descricao',
        'estadual',
        'extarif',
        'fonte',
        'importado',
        'municipal',
        'nacional',
        'ncm',
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
        'codestado' => 'integer',
        'codfilial' => 'integer',
        'codibptcache' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'estadual' => 'float',
        'extarif' => 'integer',
        'importado' => 'float',
        'municipal' => 'float',
        'nacional' => 'float',
        'tipo' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
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