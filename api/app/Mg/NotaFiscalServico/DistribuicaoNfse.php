<?php

namespace Mg\NotaFiscalServico;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

/**
 * Caixa de DF-e da NFS-e (TASK-279): o que o ADN distribuiu para a filial (NFS-e em que
 * ela e' emitente, tomadora ou intermediaria, e os eventos delas), uma linha por NSU.
 * O XML fica em disco (NFePHPPathService::pathNfseDfe).
 */
class DistribuicaoNfse extends MgModel
{
    const TIPO_NFSE = 'NFSE';
    const TIPO_EVENTO = 'EVENTO';

    protected $table = 'tbldistribuicaonfse';
    protected $primaryKey = 'coddistribuicaonfse';

    protected $fillable = [
        'codfilial',
        'nsu',
        'tipodocumento',
        'tipoevento',
        'nfsechave',
        'data',
        'nfsenumero',
        'emitentecnpj',
        'emitente',
        'tomadorcnpj',
        'tomador',
        'valorservico',
        'codnotafiscalservico',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codfilial' => 'integer',
        'coddistribuicaonfse' => 'integer',
        'codnotafiscalservico' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'nfsenumero' => 'integer',
        'nsu' => 'integer',
        'valorservico' => 'float',
    ];

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NotaFiscalServico()
    {
        return $this->belongsTo(NotaFiscalServico::class, 'codnotafiscalservico', 'codnotafiscalservico');
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
