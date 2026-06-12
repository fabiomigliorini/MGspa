<?php

namespace Mg\Safra;

use Mg\MgService;
use Mg\Cultura\TabelaDesconto;
use Mg\Veiculo\Veiculo;
use Mg\Pessoa\Pessoa;

class CargaColheitaService extends MgService
{
    // Relacionamentos sempre carregados ao devolver uma carga
    const WITH = [
        'Safra.Cultura',
        'CargaColheitaPlantioS.Plantio.Talhao',
        'CargaColheitaPlantioS.Plantio.Variedade',
        'Veiculo',
        'PessoaMotorista',
    ];

    const ETAPAS = ['PATIO', 'BRUTO', 'CLASSIFICACAO', 'TARA', 'FINALIZADO'];

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = CargaColheita::query()->with(static::WITH);

        if (!empty($filter['codcargacolheita'])) {
            $qry->where('codcargacolheita', $filter['codcargacolheita']);
        }

        if (!empty($filter['uuid'])) {
            $qry->where('uuid', $filter['uuid']);
        }

        if (!empty($filter['codsafra'])) {
            $qry->where('codsafra', $filter['codsafra']);
        }

        if (!empty($filter['etapa'])) {
            $qry->where('etapa', $filter['etapa']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['-data']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Upsert por uuid — recebe uma carga criada/editada offline e grava (cria
     * ou atualiza), incluindo os talhoes (rateio %). Servidor recalcula peso
     * liquido e descontos (autoridade); frontend replica p/ exibir offline.
     */
    public static function sincronizar(array $data): CargaColheita
    {
        $carga = CargaColheita::firstOrNew(['uuid' => $data['uuid']]);
        $carga->fill($data);
        static::snapshotCaminhaoMotorista($carga);
        static::calcular($carga);
        $carga->save();
        static::sincronizarPlantios($carga, $data['plantios'] ?? []);
        return $carga->fresh(static::WITH);
    }

    /**
     * Mantem o snapshot textual (placa/motorista) coerente com o cadastro:
     * quando a carga vem vinculada a um veiculo/pessoa mas sem o texto, copia
     * da fonte. Preserva o texto livre quando nao ha FK (placa ainda nao
     * cadastrada, motorista digitado no patio).
     */
    protected static function snapshotCaminhaoMotorista(CargaColheita $carga): void
    {
        if (!empty($carga->codveiculo) && empty($carga->placa)) {
            $carga->placa = optional(Veiculo::find($carga->codveiculo))->placa;
        }
        if (!empty($carga->codpessoamotorista) && empty($carga->motorista)) {
            $pessoa = Pessoa::find($carga->codpessoamotorista);
            $nome = $pessoa ? ($pessoa->fantasia ?: $pessoa->pessoa) : null;
            $carga->motorista = $nome ? mb_substr($nome, 0, 60) : null;
        }
    }

    /** Substitui os talhoes da carga pelo conjunto informado (rateio %). */
    protected static function sincronizarPlantios(CargaColheita $carga, array $plantios): void
    {
        CargaColheitaPlantio::where('codcargacolheita', $carga->codcargacolheita)->delete();
        foreach ($plantios as $p) {
            if (empty($p['codplantio'])) {
                continue;
            }
            $cp = new CargaColheitaPlantio();
            $cp->codcargacolheita = $carga->codcargacolheita;
            $cp->codplantio = $p['codplantio'];
            $cp->percentual = $p['percentual'] ?? null;
            $cp->save();
        }
    }

    /**
     * pesoliquido = bruto - tara; descontos (kg) por faixa da cultura da safra;
     * pesoliquidoseco final. Tudo null enquanto faltam os pesos (carga em etapa
     * inicial do patio).
     */
    public static function calcular(CargaColheita $carga): void
    {
        if ($carga->pesobruto !== null && $carga->tara !== null) {
            $carga->pesoliquido = round(((float) $carga->pesobruto) - ((float) $carga->tara), 3);
        } else {
            $carga->pesoliquido = null;
        }

        $liq = $carga->pesoliquido;
        if ($liq === null) {
            $carga->descontoumidade = null;
            $carga->descontoimpureza = null;
            $carga->descontoavariados = null;
            $carga->pesoliquidoseco = null;
            return;
        }

        $codcultura = static::codCultura($carga->codsafra);
        $carga->descontoumidade = static::descontoKg($codcultura, 'UMIDADE', $carga->umidade, $liq);
        $carga->descontoimpureza = static::descontoKg($codcultura, 'IMPUREZA', $carga->impureza, $liq);
        $carga->descontoavariados = static::descontoKg($codcultura, 'AVARIADOS', $carga->avariados, $liq);

        $carga->pesoliquidoseco = round(
            $liq
                - ((float) $carga->descontoumidade)
                - ((float) $carga->descontoimpureza)
                - ((float) $carga->descontoavariados),
            3
        );
    }

    protected static function codCultura(?int $codsafra): ?int
    {
        if (empty($codsafra)) {
            return null;
        }
        return optional(Safra::find($codsafra))->codcultura;
    }

    /**
     * Desconto em kg de um tipo (UMIDADE/IMPUREZA/AVARIADOS) a partir da
     * faixa que contem a leitura. Sem leitura => null; sem faixa => 0.
     */
    public static function descontoKg(?int $codcultura, string $tipo, $leitura, float $pesoliquido): ?float
    {
        if (empty($codcultura) || $leitura === null || $leitura === '') {
            return null;
        }

        $faixa = TabelaDesconto::ativo()
            ->where('codcultura', $codcultura)
            ->where('tipo', $tipo)
            ->where('faixainicio', '<=', $leitura)
            ->where('faixafim', '>=', $leitura)
            ->orderBy('faixainicio', 'desc')
            ->first();

        if (!$faixa) {
            return 0.0;
        }

        return round($pesoliquido * ((float) $faixa->percentualdesconto / 100), 3);
    }
}
