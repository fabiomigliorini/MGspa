<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:25:54
 */

namespace Mg\CupomFiscal;

use Mg\MgModel;
use Mg\CupomFiscal\CupomFiscalProdutoBarra;
use Mg\CupomFiscal\Ecf;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class CupomFiscal extends MgModel
{
    protected $table = 'tblcupomfiscal';
    protected $primaryKey = 'codcupomfiscal';


    protected $fillable = [
        'cancelado',
        'codecf',
        'codpessoa',
        'datamovimento',
        'descontoacrescimo',
        'numero'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'cancelado' => 'boolean',
        'codcupomfiscal' => 'integer',
        'codecf' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'datamovimento' => 'date',
        'descontoacrescimo' => 'float',
        'numero' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Ecf()
    {
        return $this->belongsTo(Ecf::class, 'codecf', 'codecf');
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


    // Tabelas Filhas
    public function CupomFiscalProdutoBarraS()
    {
        return $this->hasMany(CupomFiscalProdutoBarra::class, 'codcupomfiscal', 'codcupomfiscal');
    }

}
