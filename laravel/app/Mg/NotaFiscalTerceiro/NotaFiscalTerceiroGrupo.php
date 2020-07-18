<?php
/**
 * Created by php artisan gerador:model.
 * Date: 18/Jul/2020 08:19:39
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroItem;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroProdutoBarra;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;

class NotaFiscalTerceiroGrupo extends MgModel
{
    protected $table = 'tblnotafiscalterceirogrupo';
    protected $primaryKey = 'codnotafiscalterceirogrupo';


    protected $fillable = [
        'codnotafiscalterceiro',
        'conferido'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codnotafiscalterceiro' => 'integer',
        'codnotafiscalterceirogrupo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'conferido' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function NotaFiscalTerceiro()
    {
        return $this->belongsTo(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }


    // Tabelas Filhas
    public function NotaFiscalTerceiroItemS()
    {
        return $this->hasMany(NotaFiscalTerceiroItem::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

    public function NotaFiscalTerceiroProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalTerceiroProdutoBarra::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

}