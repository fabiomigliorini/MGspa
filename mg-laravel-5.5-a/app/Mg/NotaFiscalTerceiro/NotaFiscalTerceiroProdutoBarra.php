<?php

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;

class NotaFiscalTerceiroProdutoBarra extends MGModel
{
    protected $table = 'tblnotafiscalterceiroprodutobarra';
    protected $primaryKey = 'codnotafiscalterceiroprodutobarra';
    protected $fillable = [
        'codnotafiscalterceirogrupo',
        'codprodutobarra',
        'margem',
        'complemento',
        'quantidade',
        'valorproduto'
    ];
    protected $dates = [
        'criacao',
        'alteracao'
    ];

    // Chaves Estrangeiras
    public function NotaFiscalTerceiroGrupo()
    {
        return $this->belongsTo(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
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


}
