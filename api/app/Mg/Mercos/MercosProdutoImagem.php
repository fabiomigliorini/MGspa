<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:37:57
 */

namespace Mg\Mercos;

use Mg\MgModel;
use Mg\Imagem\Imagem;
use Mg\Mercos\MercosProduto;
use Mg\Usuario\Usuario;

class MercosProdutoImagem extends MgModel
{
    protected $table = 'tblmercosprodutoimagem';
    protected $primaryKey = 'codmercosprodutoimagem';


    protected $fillable = [
        'codimagem',
        'codmercosproduto'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codimagem' => 'integer',
        'codmercosproduto' => 'integer',
        'codmercosprodutoimagem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Imagem()
    {
        return $this->belongsTo(Imagem::class, 'codimagem', 'codimagem');
    }

    public function MercosProduto()
    {
        return $this->belongsTo(MercosProduto::class, 'codmercosproduto', 'codmercosproduto');
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
