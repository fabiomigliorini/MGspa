<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:26:59
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Colaborador\Cargo;
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
        'codpessoa',
        'descricaovalorfixo',
        'percentualcaixa',
        'percentualsubgerente',
        'percentualvenda',
        'percentualxerox',
        'valorfixo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcargo' => 'integer',
        'codmetafilial' => 'integer',
        'codmetafilialpessoa' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'percentualcaixa' => 'float',
        'percentualsubgerente' => 'float',
        'percentualvenda' => 'float',
        'percentualxerox' => 'float',
        'valorfixo' => 'float'
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
