<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:33:50
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codncm' => 'integer',
        'codregulamentoicmsstmt' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
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
