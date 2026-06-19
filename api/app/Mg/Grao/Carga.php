<?php

namespace Mg\Grao;

use Mg\MgModel;
use Mg\Safra\Safra;
use Mg\Veiculo\Veiculo;
use Mg\Pessoa\Pessoa;

/**
 * Carga = caminhao no patio (offline-first, sync por uuid). UMA entidade que
 * cobre recebimento (ENTRADA), expedicao (SAIDA) e transferencia entre
 * unidades. As origens/destinos ficam em tblcargaponto; o extrato
 * (tblmovimentograo) e GERADO a partir deles (idempotente) — ver CargaService.
 *
 * Nomenclatura de pesagem: pbt = caminhao+carga; tara = caminhao vazio;
 * bruto = pbt - tara (grao); desconto = classificacao; liquido = bruto - desconto.
 */
class Carga extends MgModel
{
    protected $table = 'tblcarga';
    protected $primaryKey = 'codcarga';

    protected $fillable = [
        'uuid',
        'codsafra',
        'sentido',
        'etapa',
        'data',
        'placa',
        'placacarreta',
        'motorista',
        'codveiculo',
        'codpessoamotorista',
        'pbt',
        'tara',
        'bruto',
        'umidade',
        'impureza',
        'avariados',
        'descontoumidade',
        'descontoimpureza',
        'descontoavariados',
        'desconto',
        'liquido',
        'aprovado',
        'observacao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'aprovado' => 'datetime',
        'avariados' => 'float',
        'bruto' => 'float',
        'codcarga' => 'integer',
        'codpessoamotorista' => 'integer',
        'codsafra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculo' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'desconto' => 'float',
        'descontoavariados' => 'float',
        'descontoimpureza' => 'float',
        'descontoumidade' => 'float',
        'impureza' => 'float',
        'inativo' => 'datetime',
        'liquido' => 'float',
        'pbt' => 'float',
        'tara' => 'float',
        'umidade' => 'float',
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
    public function CargaPontoS()
    {
        return $this->hasMany(CargaPonto::class, 'codcarga', 'codcarga');
    }

    public function MovimentoGraoS()
    {
        return $this->hasMany(MovimentoGrao::class, 'codcarga', 'codcarga');
    }
}
