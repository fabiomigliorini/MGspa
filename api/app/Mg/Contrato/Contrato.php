<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:34
 */

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\ContratoFixacao;
use Mg\Contrato\ContratoPagamento;
use Mg\Grao\MovimentoGrao;
use Mg\Grao\CargaPonto;
use Mg\Cultura\Cultura;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Pessoa\Pessoa;
use Mg\Safra\Safra;
use Mg\Filial\Filial;
use Mg\Portador\Portador;

class Contrato extends MgModel
{
    protected $table = 'tblcontrato';
    protected $primaryKey = 'codcontrato';



    protected $fillable = [
        'codcultura',
        'codnaturezaoperacao',
        'codpessoa',
        'codpessoanf',
        'codsafra',
        'contrato',
        'dataembarque',
        'inativo',
        'isentofethab',
        'localentrega',
        'moeda',
        'observacao',
        'observacaonf',
        'preco',
        'quantidade',
        'tipo',
        'codfilial',
        'datacontrato',
        'embarqueinicio',
        'embarquefim',
        'codportador',
        'codpessoacorretora',
        'comissaotipo',
        'comissaovalor',
        'comissaototal',
        'viacooperativa',
        'codpessoacooperativa',
        'numerocomprador',
        'numerocorretora',
        'numerocooperativa',
        'operacao',
        'volumeemaberto'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcontrato' => 'integer',
        'codcultura' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codpessoa' => 'integer',
        'codpessoanf' => 'integer',
        'codsafra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'dataembarque' => 'date',
        'inativo' => 'datetime',
        'isentofethab' => 'boolean',
        'preco' => 'float',
        'quantidade' => 'float',
        'codfilial' => 'integer',
        'datacontrato' => 'date',
        'embarqueinicio' => 'date',
        'embarquefim' => 'date',
        'codportador' => 'integer',
        'codpessoacorretora' => 'integer',
        'comissaovalor' => 'float',
        'comissaototal' => 'float',
        'viacooperativa' => 'boolean',
        'codpessoacooperativa' => 'integer',
        'volumeemaberto' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Cultura()
    {
        return $this->belongsTo(Cultura::class, 'codcultura', 'codcultura');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaNf()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoanf', 'codpessoa');
    }

    public function Safra()
    {
        return $this->belongsTo(Safra::class, 'codsafra', 'codsafra');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function Corretora()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoacorretora', 'codpessoa');
    }

    public function Cooperativa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoacooperativa', 'codpessoa');
    }


    // Tabelas Filhas
    public function ContratoFixacaoS()
    {
        return $this->hasMany(ContratoFixacao::class, 'codcontrato', 'codcontrato');
    }

    public function ContratoPagamentoS()
    {
        return $this->hasMany(ContratoPagamento::class, 'codcontrato', 'codcontrato');
    }

    // Entregas/recebimentos deste contrato no extrato de grao (entregue = SUM liquido).
    public function MovimentoGraoS()
    {
        return $this->hasMany(MovimentoGrao::class, 'codcontrato', 'codcontrato');
    }

    // Pontos de carga que apontam p/ este contrato (origem/destino) — fonte das
    // NFs por contrato (valornf) ate a emissao real de NFe existir.
    public function CargaPontoS()
    {
        return $this->hasMany(CargaPonto::class, 'codcontrato', 'codcontrato');
    }

}
