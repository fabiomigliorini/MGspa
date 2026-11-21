<?php

namespace Mg\NotaFiscalServico;

use Mg\MgModel;
use Mg\Cidade\Cidade;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

/**
 * Nota fiscal de servico (TASK-279): uma DPS (serie e numero nossos) que, autorizada
 * pelo Sistema Nacional, vira a NFS-e (nfsechave e nfsenumero dados por ele).
 * Um servico e um valor por nota. Fora da tblnotafiscal de proposito.
 */
class NotaFiscalServico extends MgModel
{
    const STATUS_DIGITACAO = 'DIG';
    const STATUS_ERRO = 'ERR';
    const STATUS_AUTORIZADA = 'AUT';
    const STATUS_CANCELADA = 'CAN';

    const STATUS = [
        self::STATUS_DIGITACAO => 'Em Digitação',
        self::STATUS_ERRO => 'Não Autorizada',
        self::STATUS_AUTORIZADA => 'Autorizada',
        self::STATUS_CANCELADA => 'Cancelada',
    ];

    protected $table = 'tblnotafiscalservico';
    protected $primaryKey = 'codnotafiscalservico';

    protected $fillable = [
        'codfilial',
        'codpessoa',
        'serie',
        'numero',
        'emissao',
        'competencia',
        'codtributacaonacional',
        'codnbs',
        'descricao',
        'valorservico',
        'valordescontoincondicionado',
        'valordescontocondicionado',
        'issaliquota',
        'issretido',
        'tributospercentual',
        'codcidadeprestacao',
        'observacoes',
        'status',
        'nfsechave',
        'nfsenumero',
        'nfseautorizacao',
        'nfsecancelamento',
        'justificativa',
        'mensagem',
        // Substituicao (TASK-279 #5): a nota substituida (codnotafiscalservicosubstituida)
        // so e' ligada pelo Substituir, nunca pelo formulario
        'substituicaomotivo',
        'substituicaojustificativa',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcidadeprestacao' => 'integer',
        'codfilial' => 'integer',
        'codnbs' => 'integer',
        'codnotafiscalservico' => 'integer',
        'codnotafiscalservicosubstituida' => 'integer',
        'codpessoa' => 'integer',
        'codtributacaonacional' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'competencia' => 'date',
        'criacao' => 'datetime',
        'emissao' => 'datetime',
        'issaliquota' => 'float',
        'issretido' => 'boolean',
        'nfseautorizacao' => 'datetime',
        'nfsecancelamento' => 'datetime',
        'nfsenumero' => 'integer',
        'numero' => 'integer',
        'serie' => 'integer',
        'substituicaomotivo' => 'integer',
        'tributospercentual' => 'float',
        'valordescontocondicionado' => 'float',
        'valordescontoincondicionado' => 'float',
        'valorservico' => 'float',
    ];

    // Valores derivados (mesma conta da DPS)

    // Base do ISS: o desconto incondicionado abate, o condicionado nao
    public function getBaseCalculoAttribute(): float
    {
        return round($this->valorservico - ($this->valordescontoincondicionado ?? 0), 2);
    }

    // So com ISS retido: sem retencao o ISS vai no DAS e nao sai na nota
    public function getIssValorAttribute(): ?float
    {
        if (!$this->issretido || $this->issaliquota === null) {
            return null;
        }
        return round($this->base_calculo * $this->issaliquota / 100, 2);
    }

    // Regra do leiaute (Anexo I): vLiq = vServ - vDescCond - vDescIncond - vTotalRet, e o
    // ISS entra no vTotalRet quando e' retido. (A homologacao devolveu vLiq sem descontar
    // o ISS retido, contra a propria regra; o Coplan descontava.)
    public function getValorLiquidoAttribute(): float
    {
        $liquido = $this->valorservico
            - ($this->valordescontoincondicionado ?? 0)
            - ($this->valordescontocondicionado ?? 0)
            - ($this->issretido ? ($this->iss_valor ?? 0) : 0);
        return round($liquido, 2);
    }

    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function TributacaoNacional()
    {
        return $this->belongsTo(TributacaoNacional::class, 'codtributacaonacional', 'codtributacaonacional');
    }

    public function Nbs()
    {
        return $this->belongsTo(Nbs::class, 'codnbs', 'codnbs');
    }

    public function CidadePrestacao()
    {
        return $this->belongsTo(Cidade::class, 'codcidadeprestacao', 'codcidade');
    }

    // NFS-e que esta nota substitui (TASK-279 #5)
    public function NotaFiscalServicoSubstituida()
    {
        return $this->belongsTo(NotaFiscalServico::class, 'codnotafiscalservicosubstituida', 'codnotafiscalservico');
    }

    // Notas que substituem esta: no maximo uma em andamento ou autorizada
    public function NotaFiscalServicoSubstitutaS()
    {
        return $this->hasMany(NotaFiscalServico::class, 'codnotafiscalservicosubstituida', 'codnotafiscalservico');
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
