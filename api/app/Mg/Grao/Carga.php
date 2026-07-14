<?php

namespace Mg\Grao;

use Mg\MgModel;
use Mg\Safra\Safra;
use Mg\Veiculo\Veiculo;
use Mg\Pessoa\Pessoa;
use Mg\Classificacao\TabelaClassificacao;

/**
 * Carga = caminhao no patio (offline-first, sync por uuid). UMA entidade que
 * cobre recebimento (ENTRADA), expedicao (SAIDA) e transferencia entre
 * unidades. As origens/destinos ficam em tblcargaponto; o extrato
 * (tblmovimentograo) e GERADO a partir deles (idempotente) — ver CargaService.
 *
 * Nomenclatura de pesagem: pbt = caminhao+carga; tara = caminhao vazio;
 * bruto = pbt - tara (grao); desconto = classificacao; liquido = bruto - desconto.
 * As leituras da classificacao vivem na filha tblcargaclassificacao (uma linha
 * por parametro), e a tabela usada e codtabelaclassificacao (resolvida do
 * contrato do ponto ou do padrao da cultura).
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
        'codtabelaclassificacao',
        'pbt',
        'tara',
        'bruto',
        'desconto',
        'liquido',
        'aprovado',
        'observacao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'aprovado' => 'datetime',
        'bruto' => 'float',
        'codcarga' => 'integer',
        'codpessoamotorista' => 'integer',
        'codsafra' => 'integer',
        'codtabelaclassificacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculo' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'desconto' => 'float',
        'inativo' => 'datetime',
        'liquido' => 'float',
        'pbt' => 'float',
        'tara' => 'float',
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

    public function TabelaClassificacao()
    {
        return $this->belongsTo(TabelaClassificacao::class, 'codtabelaclassificacao', 'codtabelaclassificacao');
    }

    // Tabelas Filhas
    public function CargaPontoS()
    {
        return $this->hasMany(CargaPonto::class, 'codcarga', 'codcarga');
    }

    public function CargaClassificacaoS()
    {
        return $this->hasMany(CargaClassificacao::class, 'codcarga', 'codcarga');
    }

    public function MovimentoGraoS()
    {
        return $this->hasMany(MovimentoGrao::class, 'codcarga', 'codcarga');
    }
}
