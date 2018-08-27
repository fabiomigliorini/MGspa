<?php

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;

class NotaFiscalTerceiroGrupo extends MGModel
{
    protected $table = 'tblnotafiscalterceirogrupo';
    protected $primaryKey = 'codnotafiscalterceirogrupo';
    protected $fillable = [
        'codnotafiscalterceiro'
    ];
    protected $dates = [
        'criacao',
        'alteracao',
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
