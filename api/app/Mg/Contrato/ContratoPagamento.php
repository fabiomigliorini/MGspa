<?php

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\ContratoFixacao;
use Mg\Portador\Portador;

/**
 * Recebimento de uma fixação (dinheiro que ENTROU): 1 fixação : N recebimentos.
 * O "a receber" é o líquido da fixação; cada recebimento dá baixa (total ou
 * parcial). A quitação (encerrar mesmo com diferencinha) vive na fixação
 * (tblcontratofixacao.quitado).
 */
class ContratoPagamento extends MgModel
{
    protected $table = 'tblcontratopagamento';
    protected $primaryKey = 'codcontratopagamento';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'codcontratofixacao',
        'data',
        'valor',
        'codportador',
        'observacao',
        'inativo',
    ];

    protected $casts = [
        'codcontratopagamento' => 'integer',
        'codcontratofixacao' => 'integer',
        'data' => 'date',
        'valor' => 'float',
        'codportador' => 'integer',
        'inativo' => 'datetime',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    // Chaves Estrangeiras
    public function ContratoFixacao()
    {
        return $this->belongsTo(ContratoFixacao::class, 'codcontratofixacao', 'codcontratofixacao');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }
}
