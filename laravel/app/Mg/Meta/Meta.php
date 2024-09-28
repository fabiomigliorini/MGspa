<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Sep/2024 15:08:44
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\MetaFilial;
use Mg\Usuario\Usuario;

class Meta extends MgModel
{
    protected $table = 'tblmeta';
    protected $primaryKey = 'codmeta';


    protected $fillable = [
        'observacoes',
        'percentualcomissaosubgerentemeta',
        'percentualcomissaovendedor',
        'percentualcomissaovendedormeta',
        'percentualcomissaoxerox',
        'periodofinal',
        'periodoinicial',
        'premioprimeirovendedorfilial'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'periodofinal',
        'periodoinicial'
    ];

    protected $casts = [
        'codmeta' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'percentualcomissaosubgerentemeta' => 'float',
        'percentualcomissaovendedor' => 'float',
        'percentualcomissaovendedormeta' => 'float',
        'percentualcomissaoxerox' => 'float',
        'premioprimeirovendedorfilial' => 'float'
    ];


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function MetaFilialS()
    {
        return $this->hasMany(MetaFilial::class, 'codmeta', 'codmeta');
    }

}