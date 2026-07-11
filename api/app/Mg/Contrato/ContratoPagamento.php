<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:42
 */

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\Contrato;
use Mg\Contrato\ContratoFixacao;
use Mg\Portador\Portador;

class ContratoPagamento extends MgModel
{
    protected $table = 'tblcontratopagamento';
    protected $primaryKey = 'codcontratopagamento';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'codcontrato',
        'codcontratofixacao',
        'data',
        'inativo',
        'observacao',
        'valor',
        'forma',
        'modo',
        'sacas',
        'cotacao',
        'cotacaorecebido',
        'datarecebido',
        'valorrecebido',
        'codportador'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcontrato' => 'integer',
        'codcontratofixacao' => 'integer',
        'codcontratopagamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'date',
        'inativo' => 'datetime',
        'valor' => 'float',
        'sacas' => 'float',
        'cotacao' => 'float',
        'cotacaorecebido' => 'float',
        'datarecebido' => 'date',
        'valorrecebido' => 'float',
        'codportador' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }

    // Fixação de origem da parcela (1 fixação : N parcelas). Dirige moeda/preço:
    // em US$ o R$ da parcela é derivado (sacas × fixacao.preco × cotacao).
    public function ContratoFixacao()
    {
        return $this->belongsTo(ContratoFixacao::class, 'codcontratofixacao', 'codcontratofixacao');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

}
