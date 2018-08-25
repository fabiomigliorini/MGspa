<?php

namespace Mg\Transferencia;

use Mg\EstoqueLocal\EstoqueLocal;
use Mg\MgModel;

class TransferenciaRequisicao extends MGModel
{
    const STATUS_PENDENTE = 10;
    const STATUS_SEPARADO = 20;
    const STATUS_CONFERIDO = 30;
    const STATUS_ENTREGUE = 40;
    const STATUS_REJEITADO = 90;

    protected $table = 'tbltransferenciarequisicao';
    protected $primaryKey = 'codtransferenciarequisicao';
    protected $fillable = [
        'codestoquelocalorigem',
        'codestoquelocaldestino',
        'codprodutovariacao',
        'quantidade',
        'indstatus',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];

    // Chaves Estrangeiras
    public function EstoqueLocalOrigem()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocalorigem', 'codestoquelocal');
    }

    public function EstoqueLocalDestino()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocaldestino', 'codestoquelocal');
    }

    public function ProdutoVariacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
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
    public function TransferenciaLoteProdutoBarraS()
    {
        return $this->hasMany(TransferenciaLoteProdutoBarra::class, 'codtransferenciarequisicao', 'codtransferenciarequisicao');
    }

}
