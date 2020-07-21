<?php
/**
 * Created by php artisan gerador:model.
 * Date: 21/Jul/2020 11:57:56
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Pessoa\Cargo;
use Mg\Meta\MetaFilial;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class MetaFilialPessoa extends MgModel
{
    protected $table = 'tblmetafilialpessoa';
    protected $primaryKey = 'codmetafilialpessoa';


    protected $fillable = [
        'codcargo',
        'codmetafilial',
        'codpessoa'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcargo' => 'integer',
        'codmetafilial' => 'integer',
        'codmetafilialpessoa' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Cargo()
    {
        return $this->belongsTo(Cargo::class, 'codcargo', 'codcargo');
    }

    public function MetaFilial()
    {
        return $this->belongsTo(MetaFilial::class, 'codmetafilial', 'codmetafilial');
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