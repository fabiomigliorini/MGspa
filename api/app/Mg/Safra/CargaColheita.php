<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 22:20:40
 */

namespace Mg\Safra;

use Mg\MgModel;
use Mg\Safra\CargaColheitaPlantio;
use Mg\Safra\Safra;
use Mg\Veiculo\Veiculo;
use Mg\Pessoa\Pessoa;

class CargaColheita extends MgModel
{
    protected $table = 'tblcargacolheita';
    protected $primaryKey = 'codcargacolheita';


    protected $fillable = [
        'avariados',
        'codpessoamotorista',
        'codsafra',
        'codveiculo',
        'data',
        'descontoavariados',
        'descontoimpureza',
        'descontoumidade',
        'etapa',
        'impureza',
        'inativo',
        'motorista',
        'observacao',
        'pesobruto',
        'pesoliquido',
        'pesoliquidoseco',
        'placa',
        'tara',
        'umidade',
        'uuid'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'avariados' => 'float',
        'codcargacolheita' => 'integer',
        'codpessoamotorista' => 'integer',
        'codsafra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculo' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'descontoavariados' => 'float',
        'descontoimpureza' => 'float',
        'descontoumidade' => 'float',
        'impureza' => 'float',
        'inativo' => 'datetime',
        'pesobruto' => 'float',
        'pesoliquido' => 'float',
        'pesoliquidoseco' => 'float',
        'tara' => 'float',
        'umidade' => 'float'
    ];


    // Chaves Estrangeiras
    public function Safra()
    {
        return $this->belongsTo(Safra::class, 'codsafra', 'codsafra');
    }

    public function Veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'codveiculo', 'codveiculo');
    }

    public function PessoaMotorista()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoamotorista', 'codpessoa');
    }


    // Tabelas Filhas
    public function CargaColheitaPlantioS()
    {
        return $this->hasMany(CargaColheitaPlantio::class, 'codcargacolheita', 'codcargacolheita');
    }

}
