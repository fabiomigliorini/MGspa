<?php
/**
 * Created by php artisan gerador:model.
 * Date: 18/Jul/2020 13:36:41
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroItem;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroProdutoBarra;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\Usuario\Usuario;

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

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
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