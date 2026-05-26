<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Oct/2024 14:46:59
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codmeta' => 'integer',
        'codmetavendedor' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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