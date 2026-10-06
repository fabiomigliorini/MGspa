<?php

namespace Mg\Grao;

use Mg\MgService;
use Mg\Safra\Safra;
use Mg\Classificacao\ParametroClassificacao;
use Mg\Classificacao\ParametroClassificacaoService;
use Mg\Veiculo\Veiculo;
use Mg\Pessoa\Pessoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Carga = documento operacional do patio (offline-first, upsert por uuid). O
 * servidor e a autoridade: recalcula pesos/descontos, valida o fechamento e
 * GERA o extrato (tblmovimentograo) a partir dos pontos (idempotente).
 */
class CargaService extends MgService
{
    /**
     * CONTRATO DO SYNC OFFLINE — nao enxugar. O patio puxa por GET v1/carga e
     * normalizarCargaDoServidor() (agro/src/utils/carga.js) depende de
     * `classificacao` e `CargaPontoS` completos; tirar relacao daqui quebra o
     * cache offline em silencio. Telas de consulta usam WITH_LISTAGEM.
     */
    const WITH = [
        'Safra.Cultura',
        'Veiculo',
        'PessoaMotorista',
        'CargaClassificacaoS.ParametroClassificacao',
        'CargaPontoS.Plantio.Talhao',
        'CargaPontoS.Plantio.Fazenda',
        'CargaPontoS.Plantio.Variedade',
        'CargaPontoS.UnidadeArmazenadora',
        'CargaPontoS.Contrato.Pessoa',
    ];

    /**
     * Eager load da listagem/relatorio: so o necessario pro rotulo do ponto e
     * pra saca (pesosaca da cultura). Fora ficam CargaClassificacaoS (a listagem
     * nao mostra leitura) e Plantio.Talhao (Plantio ja tem a coluna `talhao`).
     * Veiculo/PessoaMotorista tambem saem: placa e motorista sao snapshot em
     * tblcarga (snapshotCaminhaoMotorista).
     */
    const WITH_LISTAGEM = [
        'Safra.Cultura',
        'CargaPontoS.Plantio.Variedade',
        'CargaPontoS.UnidadeArmazenadora',
        'CargaPontoS.Contrato.Pessoa',
    ];

    // Etapas aceitas (uniao dos fluxos). A ordem por sentido fica no front:
    //  ENTRADA: PBT -> CLASSIFICACAO -> TARA -> FINALIZADO
    //  SAIDA:   TARA -> PBT -> FISCAL -> FINALIZADO
    //  TRANSFERENCIA: PBT -> TARA -> FINALIZADO
    const ETAPAS = ['PBT', 'TARA', 'CLASSIFICACAO', 'FISCAL', 'FINALIZADO'];
    const SENTIDOS = ['ENTRADA', 'SAIDA', 'TRANSFERENCIA'];
    const CONTATIPOS = ['PLANTIO', 'UNIDADE', 'CONTRATO'];
    const ETAPA_FINAL = 'FINALIZADO';

    /**
     * @param array|null $with Relacoes do eager load. Default = WITH (contrato do
     *                         sync). A listagem/relatorio passa WITH_LISTAGEM.
     */
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null, ?array $with = null)
    {
        $qry = Carga::query()->with($with ?? static::WITH);
        $qry = static::qryFiltros($qry, $filter);
        $qry = self::qryOrdem($qry, $sort ?: ['-data']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Filtros da carga — fonte unica de WHERE, compartilhada pela listagem
     * paginada e pelo relatorio PDF (e o que faz "imprimir o que estou vendo"
     * ser verdade).
     */
    public static function qryFiltros($qry, ?array $filter = null)
    {
        if (!empty($filter['codcarga'])) {
            $qry->where('codcarga', $filter['codcarga']);
        }
        if (!empty($filter['uuid'])) {
            $qry->where('uuid', $filter['uuid']);
        }
        if (!empty($filter['codsafra'])) {
            $qry->where('codsafra', $filter['codsafra']);
        }
        if (!empty($filter['sentido'])) {
            $qry->where('sentido', $filter['sentido']);
        }
        if (!empty($filter['etapa'])) {
            $qry->where('etapa', $filter['etapa']);
        }
        if (!empty($filter['inativo'])) {
            // ATENCAO: sem a chave nenhum scope roda e as CANCELADAS vem junto.
            // 1 = ativas, 2 = canceladas, 9 = todas (MgModel::scopeAtivoInativo).
            $qry->AtivoInativo($filter['inativo']);
        }
        if (!empty($filter['data'])) {
            // Coluna e timestamp (chegada no patio); whereDate trunca a parte de
            // data pra o dia inteiro entrar, sem cortar o que vem apos a meia-noite.
            // Usado pelo pull do patio — nao trocar por range.
            $qry->whereDate('data', $filter['data']);
        }
        if (!empty($filter['data_inicio'])) {
            $qry->where('data', '>=', $filter['data_inicio'] . ' 00:00:00');
        }
        if (!empty($filter['data_fim'])) {
            $qry->where('data', '<=', $filter['data_fim'] . ' 23:59:59');
        }
        if (!empty($filter['codcultura'])) {
            $qry->whereHas('Safra', fn ($q) => $q->where('codcultura', $filter['codcultura']));
        }
        if (!empty($filter['codveiculo'])) {
            $qry->where('codveiculo', $filter['codveiculo']);
        }
        if (!empty($filter['codpessoamotorista'])) {
            $qry->where('codpessoamotorista', $filter['codpessoamotorista']);
        }
        foreach (['placa', 'placacarreta', 'placacarreta2', 'motorista'] as $col) {
            if (!empty($filter[$col])) {
                $qry->where($col, 'ilike', '%' . $filter[$col] . '%');
            }
        }

        static::qryFiltrosPonto($qry, $filter);

        return $qry;
    }

    /**
     * Filtros de origem/destino, que vivem em tblcargaponto (N:N com a carga).
     *
     * `whereHas` (EXISTS correlacionado), NUNCA join: o join duplica a linha da
     * carga quando ela tem 2+ pontos, e ai `->paginate()` conta errado — a pagina
     * de 50 entrega 47 cargas distintas e o meta.total mente.
     *
     * E UM whereHas POR FILTRO, nunca um so pros tres: `contatipo` e excludente
     * (sincronizarPontos grava apenas uma das tres FKs por linha), entao pedir
     * unidade+talhao no mesmo EXISTS exigiria a MESMA linha sendo UNIDADE e
     * PLANTIO — resultado sempre vazio. Separados, a semantica e "a carga tocou
     * o silo 1 E tocou o talhao 12", que e o que se espera.
     *
     * `papel` e opcional: por padrao casa nos dois lados, porque a mesma unidade
     * e DESTINO num recebimento e ORIGEM numa expedicao — quem filtra "Silo 1"
     * quer o movimento inteiro.
     */
    protected static function qryFiltrosPonto($qry, ?array $filter = null): void
    {
        $papel = $filter['papel'] ?? null;
        $papel = in_array($papel, ['ORIGEM', 'DESTINO'], true) ? $papel : null;

        $ponto = function (callable $where) use ($qry, $papel) {
            $qry->whereHas('CargaPontoS', function ($q) use ($where, $papel) {
                $where($q);
                if ($papel) {
                    $q->where('papel', $papel);
                }
            });
        };

        // A FK basta: codunidadearmazenadora so e preenchida quando contatipo =
        // UNIDADE (idem plantio/contrato), entao nao precisa filtrar contatipo
        // junto — e assim o indice parcial e usado.
        foreach (['codunidadearmazenadora', 'codplantio', 'codcontrato'] as $col) {
            if (!empty($filter[$col])) {
                $ponto(fn ($q) => $q->where($col, $filter[$col]));
            }
        }

        if (!empty($filter['codpessoacontrato'])) {
            $ponto(fn ($q) => $q->whereHas(
                'Contrato',
                fn ($c) => $c->where('codpessoa', $filter['codpessoacontrato'])
            ));
        }
    }

    /**
     * Totais do recorte INTEIRO (nao da pagina) — uma query agregada, sem eager
     * load e sem ordem. E o que a barra de totais da listagem mostra: quem
     * filtrou "setembro" quer o liquido de setembro, nao o das 50 primeiras.
     *
     * `sacas` e calculada carga a carga — liquido / pesosaca da cultura da
     * safra DAQUELA carga (60 quando vazio ou zero, como o front) — e so depois
     * somada: assim o total fecha com a soma da coluna Sacas da listagem mesmo
     * com culturas misturadas.
     *
     * `sentidos` separa recebido, expedido e transferido SEMPRE sem as
     * canceladas, mesmo com "Cancelados"/"Todos" no filtro: somar kg de
     * romaneio cancelado no total do que entrou mentiria o estoque. O `qtd` do
     * topo continua contando o recorte inteiro (a guarda de linhas do
     * CargaRelatorioService depende dele).
     */
    public static function totais(?array $filter = null): array
    {
        // Subquery correlacionada, nao join: qryFiltros usa whereHas e um join
        // aqui deixaria ambigua a coluna codsafra.
        $pesosaca = '(select nullif(c.pesosaca, 0) from tblsafra s'
            . ' join tblcultura c on c.codcultura = s.codcultura'
            . ' where s.codsafra = tblcarga.codsafra)';

        $soma = 'count(*) as qtd'
            . ', coalesce(sum(bruto), 0) as bruto'
            . ', coalesce(sum(desconto), 0) as desconto'
            . ', coalesce(sum(liquido), 0) as liquido'
            . ", coalesce(sum(liquido / coalesce({$pesosaca}, 60)), 0) as sacas";

        $row = static::qryFiltros(Carga::query(), $filter)->selectRaw($soma)->first();

        $ativas = array_merge($filter ?? [], ['inativo' => 1]);
        $porSentido = static::qryFiltros(Carga::query(), $ativas)
            ->selectRaw('sentido, ' . $soma)
            ->groupBy('sentido')
            ->get()
            ->keyBy('sentido');

        $sentidos = [];
        foreach (static::SENTIDOS as $sentido) {
            $s = $porSentido->get($sentido);
            $sentidos[$sentido] = [
                'qtd' => (int) ($s->qtd ?? 0),
                'bruto' => (float) ($s->bruto ?? 0),
                'desconto' => (float) ($s->desconto ?? 0),
                'liquido' => (float) ($s->liquido ?? 0),
                'sacas' => (float) ($s->sacas ?? 0),
            ];
        }

        return [
            'qtd' => (int) ($row->qtd ?? 0),
            'bruto' => (float) ($row->bruto ?? 0),
            'desconto' => (float) ($row->desconto ?? 0),
            'liquido' => (float) ($row->liquido ?? 0),
            'sacas' => (float) ($row->sacas ?? 0),
            'sentidos' => $sentidos,
        ];
    }

    /**
     * Upsert por uuid (offline). Grava a carga + os pontos + as leituras de
     * classificacao, recalcula pesos/descontos, valida o fechamento e regera o
     * extrato.
     */
    public static function sincronizar(array $data): Carga
    {
        return DB::transaction(function () use ($data) {
            // Trava consultiva pelo uuid: serializa ate dois envios da MESMA carga
            // chegando ao mesmo tempo (libera sozinha no commit/rollback). So depois
            // dela e seguro ler o estado — antes, os dois liam a linha antiga e cada
            // um gravava so os campos "sujos" dele (TASK-180).
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ['carga:' . $data['uuid']]);

            $carga = Carga::firstOrNew(['uuid' => $data['uuid']]);

            // Concorrencia otimista. `versao` ausente/null = aparelho que nunca
            // recebeu resposta desta carga (criacao reenviada) ou app antigo:
            // aplica, como sempre foi.
            $versaoCliente = $data['versao'] ?? null;
            if ($carga->exists && $versaoCliente !== null && (int) $versaoCliente !== (int) $carga->versao) {
                throw new CargaConflitoException($carga->fresh(static::WITH));
            }

            $carga->fill($data);
            $carga->versao = $carga->exists ? ((int) $carga->versao + 1) : 1;
            static::snapshotCaminhaoMotorista($carga);
            $carga->save();
            static::sincronizarPontos($carga, $data['pontos'] ?? []);
            static::sincronizarClassificacao($carga, $data['classificacao'] ?? []);
            $carga->load('CargaPontoS');
            static::calcular($carga);
            $carga->save();
            static::validar($carga);
            static::gerarMovimento($carga);
            return $carga->fresh(static::WITH);
        }, 3); // retentativa em deadlock/serialization failure do Postgres
    }

    /** Dados do motorista SEM cadastro — com codpessoamotorista ficam NULL. */
    const CAMPOS_MOTORISTA_SEM_CADASTRO = [
        'cpfmotorista',
        'telefonemotorista',
        'cepmotorista',
        'enderecomotorista',
        'bairromotorista',
        'codcidademotorista',
    ];

    /**
     * Mantem o snapshot textual (placa/motorista) coerente com o cadastro
     * quando a carga vem com a FK mas sem o texto. Preserva o texto livre.
     * Motorista cadastrado: os dados do "sem cadastro" nao valem mais (vivem
     * em tblpessoa) — zera pra nao ficar CPF/endereco velho na carga.
     */
    protected static function snapshotCaminhaoMotorista(Carga $carga): void
    {
        if (!empty($carga->codpessoamotorista)) {
            foreach (static::CAMPOS_MOTORISTA_SEM_CADASTRO as $campo) {
                $carga->$campo = null;
            }
        }
        if (!empty($carga->codveiculo) && empty($carga->placa)) {
            $carga->placa = optional(Veiculo::find($carga->codveiculo))->placa;
        }
        if (!empty($carga->codpessoamotorista) && empty($carga->motorista)) {
            $pessoa = Pessoa::find($carga->codpessoamotorista);
            $nome = $pessoa ? ($pessoa->fantasia ?: $pessoa->pessoa) : null;
            $carga->motorista = $nome ? mb_substr($nome, 0, 60) : null;
        }
    }

    /** Substitui os pontos da carga pelo conjunto informado. */
    protected static function sincronizarPontos(Carga $carga, array $pontos): void
    {
        CargaPonto::where('codcarga', $carga->codcarga)->delete();
        foreach ($pontos as $p) {
            $tipo = $p['contatipo'] ?? null;
            $papel = $p['papel'] ?? null;
            if (!in_array($tipo, static::CONTATIPOS, true) || !in_array($papel, ['ORIGEM', 'DESTINO'], true)) {
                continue;
            }
            // Ponto sem conta valida e ignorado (linha vazia da UI).
            $conta = static::contaDoPonto($tipo, $p);
            if ($conta === null) {
                continue;
            }
            $cp = new CargaPonto();
            $cp->codcarga = $carga->codcarga;
            $cp->papel = $papel;
            $cp->contatipo = $tipo;
            $cp->codplantio = $tipo === 'PLANTIO' ? $conta : null;
            $cp->codunidadearmazenadora = $tipo === 'UNIDADE' ? $conta : null;
            $cp->codcontrato = $tipo === 'CONTRATO' ? $conta : null;
            $cp->liquido = $p['liquido'] ?? null;
            $cp->numeronf = $p['numeronf'] ?? null;
            $cp->valornf = $p['valornf'] ?? null;
            $cp->chavenf = $p['chavenf'] ?? null;
            $cp->save();
        }
    }

    /** Codigo da conta do ponto conforme o tipo (UNIDADE aceita null = silo? nao). */
    protected static function contaDoPonto(string $tipo, array $p): ?int
    {
        return match ($tipo) {
            'PLANTIO' => $p['codplantio'] ?? null,
            'UNIDADE' => $p['codunidadearmazenadora'] ?? null,
            'CONTRATO' => $p['codcontrato'] ?? null,
            default => null,
        };
    }

    /** Substitui as leituras de classificacao da carga (uma linha por parametro medido). */
    protected static function sincronizarClassificacao(Carga $carga, array $leituras): void
    {
        CargaClassificacao::where('codcarga', $carga->codcarga)->delete();
        foreach ($leituras as $l) {
            $codparam = $l['codparametroclassificacao'] ?? null;
            $leitura = $l['leitura'] ?? null;
            if (empty($codparam) || $leitura === null || $leitura === '') {
                continue; // linha sem parametro/leitura e ignorada
            }
            $cc = new CargaClassificacao();
            $cc->codcarga = $carga->codcarga;
            $cc->codparametroclassificacao = $codparam;
            $cc->leitura = $leitura;
            $cc->desconto = null;
            $cc->save();
        }
    }

    /**
     * bruto = pbt - tara; descontos (kg) pela FORMULA EM CASCATA da tabela
     * resolvida (impureza -> umidade -> defeitos); desconto = soma; liquido =
     * bruto - desconto. Grava o desconto (kg) em cada linha de classificacao.
     * Tudo null enquanto faltam os pesos (carga em etapa inicial do patio).
     */
    public static function calcular(Carga $carga): void
    {
        if ($carga->pbt !== null && $carga->tara !== null) {
            $carga->bruto = round(((float) $carga->pbt) - ((float) $carga->tara), 3);
        } else {
            $carga->bruto = null;
        }

        $carga->loadMissing('CargaClassificacaoS');
        $leituras = $carga->CargaClassificacaoS;

        $bruto = $carga->bruto;
        if ($bruto === null) {
            foreach ($leituras as $cc) {
                $cc->desconto = null;
                $cc->saveQuietly();
            }
            $carga->desconto = null;
            $carga->liquido = null;
            return;
        }

        // parametros da cultura da safra, na ordem da cascata
        $parametros = static::parametrosDaCarga($carga);
        $leiturasPorParam = $leituras->keyBy('codparametroclassificacao');

        // zera descontos (parametros de outra cultura / sem leitura ficam 0)
        foreach ($leituras as $cc) {
            $cc->desconto = 0.0;
        }

        $base = (float) $bruto;
        $total = 0.0;
        foreach ($parametros as $p) {
            $cc = $leiturasPorParam->get($p->codparametroclassificacao);
            $leitura = $cc?->leitura;
            if ($leitura === null || $leitura === '') {
                continue; // sem leitura -> desconto 0, base inalterada
            }
            $desc = round($base * static::percentualDesconto($p, (float) $leitura), 3);
            if ($cc) {
                $cc->desconto = $desc;
            }
            $total += $desc;
            if ($p->reduzbase) {
                $base -= $desc;
            }
        }

        foreach ($leituras as $cc) {
            $cc->saveQuietly();
        }

        $carga->desconto = round($total, 3);
        $carga->liquido = round((float) $bruto - $total, 3);
    }

    /**
     * Percentual de desconto (fracao) de um parametro conforme o seu metodo.
     *
     * NORMALIZADO (padrao): (leitura-tol)/(100-tol) x (100-desagio)/100 — e a
     * formula da IN MAPA 11/2007 (soja) e 60/2011 (milho), a mesma PDI/PDU da
     * Cartilha da Aprosoja e as eq. 02/05 do boletim AGAIS 01/09.
     * FATOR: (leitura-tol) x fator/100 — taxa comercial por ponto (secagem).
     * Abaixo da tolerancia -> 0 (nao ha bonus por grao mais seco/limpo).
     */
    protected static function percentualDesconto(ParametroClassificacao $p, float $leitura): float
    {
        $tol = (float) $p->tolerancia;
        $excesso = $leitura - $tol;
        if ($excesso <= 0) {
            return 0.0;
        }
        if ($p->metodo === 'FATOR') {
            return $excesso * ((float) $p->fator) / 100.0;
        }
        $den = 100.0 - $tol;
        if ($den <= 0) {
            return 0.0;
        }
        return ($excesso / $den) * (100.0 - (float) $p->desagio) / 100.0;
    }

    /** Parametros ATIVOS da cultura da safra da carga, em ordem de cascata. */
    protected static function parametrosDaCarga(Carga $carga)
    {
        return ParametroClassificacaoService::daCultura(static::codculturaDaCarga($carga));
    }

    /** Cultura da carga = cultura da safra. Unica origem, sem cascata. */
    public static function codculturaDaCarga(Carga $carga): ?int
    {
        $cod = optional(Safra::find($carga->codsafra))->codcultura;
        return empty($cod) ? null : (int) $cod;
    }

    /**
     * Validacoes (autoridade do servidor): fechamento do rateio no FINALIZADO
     * (antes disso o kanban aceita parcial). Contrato alem do saldo NAO bloqueia
     * (D12, 29/09): o caminhao completa a carga e o patio so avisa.
     */
    protected static function validar(Carga $carga): void
    {
        if ($carga->etapa !== static::ETAPA_FINAL) {
            return;
        }
        $liq = (float) $carga->liquido;
        if ($liq <= 0) {
            throw ValidationException::withMessages([
                'liquido' => 'Peso liquido invalido (pbt - tara - desconto deve ser > 0) para finalizar.',
            ]);
        }
        foreach (['ORIGEM', 'DESTINO'] as $papel) {
            $pontos = $carga->CargaPontoS->where('papel', $papel);
            if ($pontos->isEmpty()) {
                throw ValidationException::withMessages([
                    'pontos' => "Informe ao menos uma " . ($papel === 'ORIGEM' ? 'origem' : 'destino')
                        . " para finalizar a carga.",
                ]);
            }
            $soma = (float) $pontos->sum('liquido');
            if (abs($soma - $liq) > 1) {
                throw ValidationException::withMessages([
                    'pontos' => "A soma das " . ($papel === 'ORIGEM' ? 'origens' : 'destinos')
                        . " (" . round($soma) . " kg) deve fechar com o liquido (" . round($liq) . " kg).",
                ]);
            }
        }
    }

    // ===================== Geracao do extrato (idempotente) ==============

    /**
     * (Re)gera as linhas AUTOMATICAS do extrato desta carga a partir dos pontos.
     * Idempotente: apaga as automaticas e recria (manuais nunca sao tocadas). So
     * realiza carga ATIVA + FINALIZADA + com liquido; senao apenas limpa.
     */
    public static function gerarMovimento(Carga $carga): void
    {
        MovimentoGrao::where('codcarga', $carga->codcarga)->where('manual', false)->delete();

        if ($carga->inativo !== null) {
            return;
        }
        if ($carga->etapa !== static::ETAPA_FINAL) {
            return;
        }
        $liquido = (float) $carga->liquido;
        $bruto = (float) $carga->bruto;
        if ($liquido <= 0) {
            return;
        }

        $carga->loadMissing('CargaPontoS');
        foreach (['ORIGEM', 'DESTINO'] as $papel) {
            $pontos = $carga->CargaPontoS->where('papel', $papel)->values();
            $pesos = $pontos->map(fn ($p) => (float) $p->liquido)->all();
            $somaPesos = array_sum($pesos);
            if ($somaPesos <= 0) {
                continue;
            }
            $liqShares = static::ratear($liquido, $pesos);
            $brutoShares = static::ratear($bruto, $pesos);
            foreach ($pontos as $i => $p) {
                $sinal = static::sinal($p->contatipo, $papel);
                $liq = round($liqShares[$i] * $sinal, 3);
                $bru = round($brutoShares[$i] * $sinal, 3);
                $des = round($bru - $liq, 3);
                MovimentoGrao::create([
                    'codcarga' => $carga->codcarga,
                    'manual' => false,
                    'codsafra' => $carga->codsafra,
                    'data' => $carga->data,
                    'papel' => $papel,
                    'contatipo' => $p->contatipo,
                    'codplantio' => $p->codplantio,
                    'codunidadearmazenadora' => $p->codunidadearmazenadora,
                    'codcontrato' => $p->codcontrato,
                    'bruto' => $bru,
                    'desconto' => $des,
                    'liquido' => $liq,
                ]);
            }
        }
    }

    /**
     * Sinal do saldo: UNIDADE e a unica conta-saldo (+entrada/-saida); PLANTIO
     * (producao) e CONTRATO (entregue/recebido) sao contadores (sempre +).
     */
    public static function sinal(string $contatipo, string $papel): int
    {
        if ($contatipo === 'UNIDADE') {
            return $papel === 'DESTINO' ? 1 : -1;
        }
        return 1;
    }

    /** Rateia $total proporcional aos $pesos; o resto de arredondamento vai no ultimo. */
    protected static function ratear(float $total, array $pesos): array
    {
        $soma = array_sum($pesos);
        $n = count($pesos);
        if ($soma <= 0 || $n === 0) {
            return array_fill(0, $n, 0.0);
        }
        $res = [];
        $acc = 0.0;
        for ($i = 0; $i < $n; $i++) {
            if ($i < $n - 1) {
                $v = round($total * $pesos[$i] / $soma, 3);
                $res[$i] = $v;
                $acc += $v;
            } else {
                $res[$i] = round($total - $acc, 3);
            }
        }
        return $res;
    }

    /**
     * Cancelar/reativar, com a mesma trava e versao do sincronizar (TASK-180):
     * hoje o MgService::ativar/inativar nem estava em transacao, e se o
     * gerarMovimento falhasse no meio a carga ficava cancelada/reativada com o
     * extrato velho.
     */
    public static function inativar($model, $date = null)
    {
        return DB::transaction(function () use ($model, $date) {
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ['carga:' . $model->uuid]);
            $model = Carga::where('codcarga', $model->codcarga)->lockForUpdate()->firstOrFail();
            $model->versao++;
            $model = parent::inativar($model, $date);
            // Carga inativada some do extrato (estorno) — mantem os saldos coerentes.
            static::gerarMovimento($model->fresh('CargaPontoS'));
            return $model;
        }, 3);
    }

    public static function ativar($model)
    {
        return DB::transaction(function () use ($model) {
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ['carga:' . $model->uuid]);
            $model = Carga::where('codcarga', $model->codcarga)->lockForUpdate()->firstOrFail();
            $model->versao++;
            $model = parent::ativar($model);
            static::gerarMovimento($model->fresh('CargaPontoS'));
            return $model;
        }, 3);
    }
}
