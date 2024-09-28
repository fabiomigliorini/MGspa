<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Sep/2024 15:09:14
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\MetaFilialPessoa;
use Mg\Filial\Filial;
use Mg\Meta\Meta;
use Mg\Usuario\Usuario;

class MetaFilial extends MgModel
{
    protected $table = 'tblmetafilial';
    protected $primaryKey = 'codmetafilial';


    protected $fillable = [
        'codfilial',
        'codmeta',
        'observacoes',
        'valormetafilial',
        'valormetavendedor'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codmeta' => 'integer',
        'codmetafilial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'valormetafilial' => 'float',
        'valormetavendedor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Meta()
    {
        return $this->belongsTo(Meta::class, 'codmeta', 'codmeta');
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
    public function MetaFilialPessoaS()
    {
        return $this->hasMany(MetaFilialPessoa::class, 'codmetafilial', 'codmetafilial');
    }

}