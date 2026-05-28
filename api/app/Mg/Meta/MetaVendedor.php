<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:38:17
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\Meta;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class MetaVendedor extends MgModel
{
    protected $table = 'tblmetavendedor';
    protected $primaryKey = 'codmetavendedor';


    protected $fillable = [
        'codmeta',
        'codpessoa',
        'valormeta'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codmeta' => 'integer',
        'codmetavendedor' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'valormeta' => 'float'
    ];


    // Chaves Estrangeiras
    public function Meta()
    {
        return $this->belongsTo(Meta::class, 'codmeta', 'codmeta');
    }

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

}
