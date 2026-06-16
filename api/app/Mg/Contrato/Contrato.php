<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:34
 */

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\ContratoFixacao;
use Mg\Contrato\ContratoPagamento;
use Mg\Embarque\EmbarqueContrato;
use Mg\Cultura\Cultura;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Pessoa\Pessoa;
use Mg\Safra\Safra;

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
        'localentrega',
        'moeda',
        'observacao',
        'observacaonf',
        'preco',
        'quantidade',
        'tipo'
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
        'preco' => 'float',
        'quantidade' => 'float'
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


    // Tabelas Filhas
    public function ContratoFixacaoS()
    {
        return $this->hasMany(ContratoFixacao::class, 'codcontrato', 'codcontrato');
    }

    public function ContratoPagamentoS()
    {
        return $this->hasMany(ContratoPagamento::class, 'codcontrato', 'codcontrato');
    }

    public function EmbarqueContratoS()
    {
        return $this->hasMany(EmbarqueContrato::class, 'codcontrato', 'codcontrato');
    }

}
