<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:19:40
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\NaturezaOperacao\Ncm;
use Mg\Usuario\Usuario;

class RegulamentoIcmsStMt extends MgModel
{
    protected $table = 'tblregulamentoicmsstmt';
    protected $primaryKey = 'codregulamentoicmsstmt';


    protected $fillable = [
        'codncm',
        'descricao',
        'icmsstnorte',
        'icmsstsul',
        'ncm',
        'ncmexceto',
        'subitem'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codncm' => 'integer',
        'codregulamentoicmsstmt' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'icmsstnorte' => 'float',
        'icmsstsul' => 'float'
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