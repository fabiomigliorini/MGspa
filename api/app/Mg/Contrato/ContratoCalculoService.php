<?php

namespace Mg\Contrato;

use Carbon\Carbon;
use Mg\MgService;
use Mg\Cultura\Cultura;
use Mg\Cultura\CulturaTributo;
use Mg\UnidadeReferencia\UnidadeReferenciaValor;

/**
 * Motor fiscal do AGRO — calcula o PRECO LIQUIDO por saca a partir da config
 * de tributos da cultura (tblculturatributo) + historico de unidades de
 * referencia (UPF-MT). O agro e o dono dessa inteligencia; o app `notas` so
 * recebe os valores prontos no faturamento (tblnotafiscalitemtributo).
 *
 * Espelha a planilha de contratos:
 *   liquido = bruto - FETHAB - IAGRO - SENAR - FUNRURAL
 *   base UNIDADE -> %/100 x valorUnidade(competencia) x pesosaca/1000
 *   base VALOR   -> %/100 x bruto
 *   - tributos `grupofethab` sao pulados quando o contrato e isento (cooperativa)
 *   - tributos `funrural` so entram quando o produtor (filial) paga na venda
 */
class ContratoCalculoService extends MgService
{
    /**
     * Calcula o liquido + a quebra por tributo.
     *
     * @param array $p {
     *   codcultura:    int    cultura (define quais tributos incidem)
     *   bruto:         float  preco bruto R$/saca
     *   data:          string data da operacao (ISO) — define a competencia da UPF
     *   isentofethab:  bool   contrato isento de FETHAB (cooperativa)
     *   funruralvenda: bool   produtor (filial) paga Funrural na venda
     *   pesosaca:      float  kg/saca (default da cultura)
     * }
     */
    public static function calcular(array $p): array
    {
        $cultura = Cultura::findOrFail($p['codcultura']);
        $pesosaca = (float) ($p['pesosaca'] ?? $cultura->pesosaca ?: 60);
        $bruto = (float) ($p['bruto'] ?? 0);
        $data = !empty($p['data']) ? Carbon::parse($p['data']) : Carbon::today();
        $isentofethab = !empty($p['isentofethab']);
        $funruralvenda = !empty($p['funruralvenda']);

        $itens = [];
        $totalDeducao = 0.0;
        $unidadeAplicada = null;

        // Modo OVERRIDE: o operador digitou/ajustou as linhas no modal de
        // impostos. Usa exatamente o que veio (alíquota/UPF por linha) — não
        // reconsulta a config nem aplica isenção/funrural (o modal já decidiu
        // quais linhas entram). Mantém o líquido travado igual ao que ele viu.
        $override = !empty($p['tributos']) && is_array($p['tributos']);

        if ($override) {
            foreach ($p['tributos'] as $row) {
                $base = (($row['base'] ?? 'VALOR') === 'UNIDADE') ? 'UNIDADE' : 'VALOR';
                $percentual = (float) ($row['percentual'] ?? 0);
                $upf = $base === 'UNIDADE' ? (float) ($row['upf'] ?? 0) : null;
                $valor = $base === 'UNIDADE'
                    ? round($percentual / 100 * $upf * $pesosaca / 1000, 6)
                    : round($percentual / 100 * $bruto, 6);

                if ($unidadeAplicada === null && $base === 'UNIDADE' && $upf > 0) {
                    $unidadeAplicada = [
                        'codunidadereferencia' => isset($row['codunidadereferencia'])
                            ? (int) $row['codunidadereferencia'] : null,
                        'codigo' => $row['codigo'] ?? null,
                        'competencia' => $row['competencia'] ?? null,
                        'valor' => $upf,
                    ];
                }
                $totalDeducao += $valor;
                $itens[] = [
                    'codculturatributo' => isset($row['codculturatributo'])
                        ? (int) $row['codculturatributo'] : null,
                    'codtributo' => isset($row['codtributo']) ? (int) $row['codtributo'] : null,
                    'codigo' => $row['codigo'] ?? null,
                    'descricao' => $row['descricao'] ?? null,
                    'base' => $base,
                    'percentual' => $percentual,
                    'upf' => $upf !== null ? round($upf, 4) : null,
                    'valor' => round($valor, 4),
                ];
            }
        } else {
            $tributos = CulturaTributo::with('Tributo')
                ->where('codcultura', $cultura->codcultura)
                ->whereNull('inativo')
                ->orderBy('ordem')
                ->get();

            foreach ($tributos as $ct) {
                if ($ct->grupofethab && $isentofethab) {
                    continue;
                }
                if ($ct->funrural && !$funruralvenda) {
                    continue;
                }

                $upf = null;
                if ($ct->base === 'UNIDADE') {
                    $competencia = static::competenciaUnidade($ct, $data);
                    $upf = static::valorUnidade($ct->codunidadereferencia, $competencia);
                    $valor = round($ct->percentual / 100 * $upf * $pesosaca / 1000, 6);
                    // guarda a UPF efetivamente usada (FETHAB) p/ transparencia na UI
                    if ($unidadeAplicada === null && $upf > 0) {
                        $unidadeAplicada = [
                            'codunidadereferencia' => (int) $ct->codunidadereferencia,
                            'codigo' => $ct->Tributo->codigo ?? null,
                            'competencia' => $competencia->toDateString(),
                            'valor' => $upf,
                        ];
                    }
                } else {
                    $valor = round($ct->percentual / 100 * $bruto, 6);
                }

                $totalDeducao += $valor;
                $itens[] = [
                    'codculturatributo' => (int) $ct->codculturatributo,
                    'codtributo' => (int) $ct->codtributo,
                    'codigo' => $ct->Tributo->codigo ?? null,
                    'descricao' => $ct->Tributo->descricao ?? null,
                    'base' => $ct->base,
                    'percentual' => (float) $ct->percentual,
                    'upf' => $upf !== null ? round($upf, 4) : null,
                    'valor' => round($valor, 4),
                ];
            }
        }

        $liquido = round($bruto - $totalDeducao, 4);

        return [
            'codcultura' => (int) $cultura->codcultura,
            'cultura' => $cultura->cultura,
            'pesosaca' => $pesosaca,
            'data' => $data->toDateString(),
            'bruto' => round($bruto, 4),
            'totaldeducao' => round($totalDeducao, 4),
            'percentualdeducao' => $bruto > 0 ? round($totalDeducao / $bruto * 100, 4) : 0,
            'liquido' => $liquido,
            'unidade' => $unidadeAplicada,
            'itens' => $itens,
        ];
    }

    /**
     * Conveniencia: calcula o liquido de um contrato ja persistido, resolvendo
     * o bruto (media ponderada das fixacoes ativas, senao o preco do contrato),
     * a data (fim do embarque, senao hoje), a isencao de FETHAB e o regime de
     * Funrural (folha/venda) da filial produtora.
     */
    public static function calcularDoContrato(Contrato $contrato): array
    {
        $fixacoes = $contrato->relationLoaded('ContratoFixacaoS')
            ? $contrato->ContratoFixacaoS->whereNull('inativo')
            : $contrato->ContratoFixacaoS()->whereNull('inativo')->get();
        $qtd = (float) $fixacoes->sum('quantidade');
        $bruto = $qtd > 0
            ? (float) $fixacoes->sum(fn ($f) => (float) $f->quantidade * (float) $f->precoreal) / $qtd
            : (float) $contrato->preco;

        $filial = $contrato->codfilial ? $contrato->Filial : null;
        $funruralvenda = $filial ? (bool) $filial->funruralvenda : false;

        $calc = static::calcular([
            'codcultura' => (int) $contrato->codcultura,
            'bruto' => $bruto,
            'data' => $contrato->embarquefim ?: ($contrato->dataembarque ?: null),
            'isentofethab' => (bool) $contrato->isentofethab,
            'funruralvenda' => $funruralvenda,
        ]);

        // Se as fixações têm o snapshot de impostos travado, o líquido do
        // contrato é a média ponderada do líquido EFETIVO de cada fixação (o que
        // o operador travou no modal), não o recalculado pela config. Mantém o
        // KPI (liquidoSc) coerente com o que aparece em cada linha de fixação.
        if ($qtd > 0 && $fixacoes->contains(fn ($f) => $f->precoliquido !== null)) {
            $liqPond = (float) $fixacoes->sum(function ($f) use ($contrato, $funruralvenda) {
                $liq = $f->precoliquido !== null
                    ? (float) $f->precoliquido
                    : static::liquidoFixacao($f, $contrato, $funruralvenda);
                return (float) $f->quantidade * $liq;
            }) / $qtd;
            $calc['liquido'] = round($liqPond, 4);
            $calc['totaldeducao'] = round($calc['bruto'] - $liqPond, 4);
            $calc['percentualdeducao'] = $calc['bruto'] > 0
                ? round($calc['totaldeducao'] / $calc['bruto'] * 100, 4)
                : 0;
        }

        return $calc;
    }

    /**
     * Líquido on-the-fly de uma fixação sem snapshot (config + competência da
     * UPF na data da fixação). Usado no agregado do contrato pra ponderar com as
     * fixações que já têm o snapshot travado.
     */
    protected static function liquidoFixacao(
        ContratoFixacao $f,
        Contrato $contrato,
        bool $funruralvenda
    ): float {
        $calc = static::calcular([
            'codcultura' => (int) $contrato->codcultura,
            'bruto' => (float) $f->precoreal,
            'data' => $f->data,
            'isentofethab' => (bool) $contrato->isentofethab,
            'funruralvenda' => $funruralvenda,
        ]);
        return (float) $calc['liquido'];
    }

    /**
     * Valor da unidade de referencia (UPF) vigente numa competencia: o registro
     * mais recente com competencia <= a procurada.
     */
    public static function valorUnidade(?int $codunidadereferencia, Carbon $competencia): float
    {
        if (empty($codunidadereferencia)) {
            return 0.0;
        }
        $reg = UnidadeReferenciaValor::where('codunidadereferencia', $codunidadereferencia)
            ->where('competencia', '<=', $competencia->toDateString())
            ->orderByDesc('competencia')
            ->first();
        return $reg ? (float) $reg->valor : 0.0;
    }

    /**
     * Competencia da unidade de referencia p/ um tributo numa data.
     * Tributos do grupo FETHAB seguem a regra legal de defasagem semestral
     * (Lei 13.002/2025); os demais usam a competencia da propria data.
     */
    public static function competenciaUnidade(CulturaTributo $ct, Carbon $data): Carbon
    {
        if ($ct->grupofethab) {
            return static::competenciaFethab($data);
        }
        return $data->copy()->startOfMonth();
    }

    /**
     * Regra legal do FETHAB (Lei nº 13.002/2025, MT) p/ a UPF aplicavel:
     *   - 1o semestre (jan-jun): UPF de JANEIRO do ANO ANTERIOR
     *   - 2o semestre (jul-dez): UPF de JULHO  do ANO ANTERIOR
     *   - excecao 2025: o ano todo usa a UPF de janeiro/2025
     * Antes a UPF era a "vigente"; a defasagem deu previsibilidade ao produtor.
     *
     * ATENCAO: a planilha legada usava a UPF de JANEIRO DO PROPRIO ANO
     * (ex.: jan/2026 = 254,36). Esta funcao segue a LEI (jan do ano anterior).
     * Para voltar ao comportamento da planilha, troque ($ano - 1) por ($ano).
     */
    public static function competenciaFethab(Carbon $data): Carbon
    {
        $ano = (int) $data->year;
        if ($ano <= 2025) {
            return Carbon::create(2025, 1, 1);
        }
        $mes = $data->month <= 6 ? 1 : 7;
        return Carbon::create($ano - 1, $mes, 1);
    }
}
