<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalDuplicatas;
use Mg\NotaFiscal\NotaFiscalNegocioService;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\Titulo\BoletoBb\BoletoBbService;

class TituloAgrupamentoService
{
    // copiados do MGsis legado
    const TIPOTITULO_AGRUPAMENTO_CREDITO = 911;
    const TIPOTITULO_AGRUPAMENTO_DEBITO  = 921;
    const CONTACONTABIL_AGRUPAMENTO      = 7;

    public static function listar(array $filtros)
    {
        $q = TituloAgrupamento::query()
            ->select('tbltituloagrupamento.*')
            ->with([
                'Pessoa:codpessoa,fantasia',
                'UsuarioCriacao:codusuario,usuario',
            ]);

        $q->join('tblpessoa as p', 'p.codpessoa', '=', 'tbltituloagrupamento.codpessoa');

        if (!empty($filtros['codtituloagrupamento'])) {
            $q->where('tbltituloagrupamento.codtituloagrupamento', preg_replace('/[^0-9]/', '', (string)$filtros['codtituloagrupamento']));
        }
        if (!empty($filtros['codpessoa'])) {
            $q->where('tbltituloagrupamento.codpessoa', $filtros['codpessoa']);
        }
        if (!empty($filtros['codgrupoeconomico'])) {
            $q->where('p.codgrupoeconomico', $filtros['codgrupoeconomico']);
        }
        if (!empty($filtros['codgrupocliente'])) {
            $valores = is_array($filtros['codgrupocliente'])
                ? $filtros['codgrupocliente'] : [$filtros['codgrupocliente']];
            $q->where(function ($w) use ($valores) {
                $semGrupo = false;
                $cods = [];
                foreach ($valores as $v) {
                    if ((int)$v === -1) $semGrupo = true;
                    else $cods[] = $v;
                }
                if (!empty($cods)) $w->whereIn('p.codgrupocliente', $cods);
                if ($semGrupo) $w->orWhereNull('p.codgrupocliente');
            });
        }

        $estornado = $filtros['estornado'] ?? '0';
        if ((string)$estornado === '0') {
            $q->whereNull('tbltituloagrupamento.cancelamento');
        } elseif ((string)$estornado === '1') {
            $q->whereNotNull('tbltituloagrupamento.cancelamento');
        }

        foreach ([
            'emissao_de'  => ['tbltituloagrupamento.emissao', '>='],
            'emissao_ate' => ['tbltituloagrupamento.emissao', '<='],
            'criacao_de'  => ['tbltituloagrupamento.criacao', '>='],
            'criacao_ate' => ['tbltituloagrupamento.criacao', '<='],
        ] as $key => [$col, $op]) {
            if (!empty($filtros[$key])) {
                $bound = str_ends_with($key, '_de') ? 'startOfDay' : 'endOfDay';
                $q->where($col, $op, Carbon::parse($filtros[$key])->{$bound}()->format('Y-m-d H:i:s'));
            }
        }

        $q->orderBy('tbltituloagrupamento.emissao', 'desc')
          ->orderBy('tbltituloagrupamento.criacao', 'desc')
          ->orderBy('tbltituloagrupamento.codtituloagrupamento', 'desc');

        return $q->paginate(50);
    }

    public static function carregar(int $id): TituloAgrupamento
    {
        return TituloAgrupamento::with([
            'Pessoa:codpessoa,fantasia,codgrupoeconomico',
            'UsuarioCriacao:codusuario,usuario',
            'UsuarioAlteracao:codusuario,usuario',
            'TituloS' => function ($q) {
                $q->orderBy('vencimento')->with([
                    'Filial:codfilial,filial',
                    'Pessoa:codpessoa,fantasia',
                    'Portador:codportador,portador',
                ]);
            },
            'MovimentoTituloS' => function ($q) {
                $q->orderBy('codmovimentotitulo')->with([
                    'Titulo:codtitulo,codpessoa,codfilial,numero,vencimento,fatura,nossonumero,boleto,gerencial,codtituloagrupamento,codportador',
                    'Titulo.Pessoa:codpessoa,fantasia',
                    'Titulo.Filial:codfilial,filial',
                    'Titulo.Portador:codportador,portador',
                    'TipoMovimentoTitulo:codtipomovimentotitulo,tipomovimentotitulo,estorno',
                ]);
            },
        ])->findOrFail($id);
    }

    public static function criar(array $dados): TituloAgrupamento
    {
        if (empty($dados['titulos']) || !is_array($dados['titulos'])) {
            throw new \InvalidArgumentException('Selecione ao menos um título!');
        }
        if (empty($dados['vencimentos']) || !is_array($dados['vencimentos']) || empty($dados['valores'])) {
            throw new \InvalidArgumentException('Informe vencimentos e valores das parcelas!');
        }
        if (count($dados['vencimentos']) !== count($dados['valores'])) {
            throw new \InvalidArgumentException('Quantidade de vencimentos diferente da de valores!');
        }

        $codpessoa  = (int)$dados['codpessoa'];
        $codfilial  = (int)$dados['codfilial'];
        $codportador = !empty($dados['codportador']) ? (int)$dados['codportador'] : null;
        $emissao    = Carbon::parse($dados['emissao'])->format('Y-m-d');
        $boleto     = !empty($dados['boleto']);

        // total dos títulos selecionados (CR negativa, DB positiva)
        $totalTitulos = 0.0;
        $gerencial = false;
        $faturas = [];
        foreach ($dados['titulos'] as $t) {
            $titulo = Titulo::findOrFail((int)$t['codtitulo']);
            self::validarLinha($titulo, $t);
            if ($titulo->gerencial) $gerencial = true;
            if (!empty($titulo->fatura)) $faturas[] = $titulo->fatura;

            $total = (float)$t['total'];
            if (MovimentoTituloHelper::operacao($titulo) === 'CR') {
                $totalTitulos -= $total;
            } else {
                $totalTitulos += $total;
            }
        }

        // valida soma das parcelas == total dos títulos
        $somaParcelas = 0.0;
        foreach ($dados['valores'] as $v) {
            $somaParcelas += (float)$v;
        }
        if (abs(abs($totalTitulos) - $somaParcelas) > 0.005) {
            throw new \InvalidArgumentException(
                "Soma das parcelas ({$somaParcelas}) não bate com o total dos títulos (" . abs($totalTitulos) . ")!"
            );
        }

        $debitoTotal = max($totalTitulos, 0);
        $creditoTotal = max(-$totalTitulos, 0);

        $ag = new TituloAgrupamento([
            'codpessoa'  => $codpessoa,
            'emissao'    => $emissao,
            'observacao' => $dados['observacao'] ?? null,
            'debito'     => $debitoTotal,
            'credito'    => $creditoTotal,
        ]);
        $ag->save();

        // baixa títulos selecionados
        foreach ($dados['titulos'] as $t) {
            $titulo = Titulo::findOrFail((int)$t['codtitulo']);
            MovimentoTituloHelper::adicionarMultaJurosDesconto(
                $titulo,
                (float)($t['multa'] ?? 0),
                (float)($t['juros'] ?? 0),
                (float)($t['desconto'] ?? 0),
                $emissao,
                $codportador,
                $ag->codtituloagrupamento,
                null
            );
            MovimentoTituloHelper::liquidar(
                $titulo,
                (float)$t['total'],
                $emissao,
                $codportador,
                $ag->codtituloagrupamento,
                null,
                MovimentoTituloService::TIPO_AGRUPAMENTO
            );
        }

        // gera novos títulos (parcelas)
        $codtipotitulo = $totalTitulos < 0
            ? self::TIPOTITULO_AGRUPAMENTO_CREDITO
            : self::TIPOTITULO_AGRUPAMENTO_DEBITO;

        $totalParcelas = count($dados['vencimentos']);
        $faturaStr = substr(implode(', ', $faturas), 0, 50);

        for ($i = 0; $i < $totalParcelas; $i++) {
            $numero = 'A' . str_pad((string)$ag->codtituloagrupamento, 8, '0', STR_PAD_LEFT)
                . '-' . ($i + 1) . '/' . $totalParcelas;
            $venc = Carbon::parse($dados['vencimentos'][$i])->format('Y-m-d');
            $valor = (float)$dados['valores'][$i];

            $novo = new Titulo([
                'codtituloagrupamento' => $ag->codtituloagrupamento,
                'codtipotitulo'        => $codtipotitulo,
                'codfilial'            => $codfilial,
                'codportador'          => $codportador,
                'codpessoa'            => $codpessoa,
                'codcontacontabil'     => self::CONTACONTABIL_AGRUPAMENTO,
                'numero'               => $numero,
                'fatura'               => $faturaStr,
                'emissao'              => $emissao,
                'transacao'            => $emissao,
                'vencimento'           => $venc,
                'vencimentooriginal'   => $venc,
                'gerencial'            => $gerencial,
                'observacao'           => $dados['observacao'] ?? null,
                'boleto'               => $boleto,
            ]);

            if ($codtipotitulo === self::TIPOTITULO_AGRUPAMENTO_CREDITO) {
                $novo->credito = $valor;
                $novo->debito  = 0;
            } else {
                $novo->credito = 0;
                $novo->debito  = $valor;
            }
            $novo->sistema = Carbon::now()->format('Y-m-d H:i:s');
            $novo->save();
        }

        return self::carregar($ag->codtituloagrupamento);
    }

    public static function registrarBoletos(TituloAgrupamento $ag): void
    {
        foreach ($ag->TituloS as $titulo) {
            if (!$titulo->boleto) continue;
            if (!empty($titulo->nossonumero)) continue;
            try {
                BoletoBbService::registrar($titulo);
            } catch (\Throwable $th) {
                // não interrompe; falhas individuais ficam para reprocessamento manual
            }
        }
    }

    public static function atualizar(TituloAgrupamento $ag, array $dados): TituloAgrupamento
    {
        $codpessoa  = (int)$dados['codpessoa'];
        $emissao    = Carbon::parse($dados['emissao'])->format('Y-m-d');
        $observacao = $dados['observacao'] ?? null;

        $mudouPessoa  = (int)$ag->codpessoa !== $codpessoa;
        $mudouEmissao = Carbon::parse($ag->emissao)->format('Y-m-d') !== $emissao;

        $ag->codpessoa  = $codpessoa;
        $ag->emissao    = $emissao;
        $ag->observacao = $observacao;
        $ag->save();

        if ($mudouPessoa || $mudouEmissao) {
            foreach ($ag->TituloS as $titulo) {
                if ($mudouPessoa) {
                    $titulo->codpessoa = $codpessoa;
                }
                if ($mudouEmissao) {
                    $titulo->emissao = $emissao;
                }
                $titulo->save();
            }
        }

        return self::carregar($ag->codtituloagrupamento);
    }

    public static function estornar(TituloAgrupamento $ag): TituloAgrupamento
    {
        if (!empty($ag->cancelamento)) {
            throw new \Exception('Agrupamento já estornado!', 1);
        }

        // estorna todos os movimentos vinculados
        foreach ($ag->MovimentoTituloS as $mov) {
            if (optional($mov->TipoMovimentoTitulo)->estorno) continue;
            MovimentoTituloService::estornar($mov);
        }

        $ag->cancelamento = Carbon::today()->format('Y-m-d');
        $ag->save();

        return self::carregar($ag->codtituloagrupamento);
    }

    public static function notasDoAgrupamento(int $codtituloagrupamento): array
    {
        $sql = "
            select distinct nfpb.codnotafiscal
            from tbltituloagrupamento ta
            inner join tblmovimentotitulo mt on (mt.codtituloagrupamento = ta.codtituloagrupamento)
            inner join tbltitulo t on (t.codtitulo = mt.codtitulo)
            inner join tblnegocioformapagamento nfp on (nfp.codnegocioformapagamento = t.codnegocioformapagamento)
            inner join tblnegocioprodutobarra npb on (npb.codnegocio = nfp.codnegocio)
            inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnegocioprodutobarra = npb.codnegocioprodutobarra)
            where ta.codtituloagrupamento = :codtituloagrupamento
        ";
        $rows = DB::select($sql, ['codtituloagrupamento' => $codtituloagrupamento]);
        $codnotas = array_map(fn($r) => (int)$r->codnotafiscal, $rows);
        if (empty($codnotas)) {
            return [];
        }
        return NotaFiscal::whereIn('codnotafiscal', $codnotas)
            ->with([
                'Filial:codfilial,filial',
                'Pessoa:codpessoa,fantasia',
                'NaturezaOperacao:codnaturezaoperacao,naturezaoperacao',
            ])
            ->orderBy('codnotafiscal', 'desc')
            ->get()
            ->all();
    }

    public static function gerarNotaFiscal(TituloAgrupamento $ag, int $modelo, bool $todos = false): NotaFiscal
    {
        if (!empty($ag->cancelamento)) {
            throw new \Exception('Agrupamento estornado!', 1);
        }
        if (!in_array($modelo, [NotaFiscalService::MODELO_NFE, NotaFiscalService::MODELO_NFCE], true)) {
            throw new \InvalidArgumentException('Modelo de Nota Fiscal inválido!');
        }

        $codnegocios = DB::select('
            select distinct nfp.codnegocio
            from tbltituloagrupamento ta
            inner join tblmovimentotitulo mt on (mt.codtituloagrupamento = ta.codtituloagrupamento)
            inner join tbltitulo t on (t.codtitulo = mt.codtitulo)
            inner join tblnegocioformapagamento nfp on (nfp.codnegocioformapagamento = t.codnegocioformapagamento)
            where ta.codtituloagrupamento = :codtituloagrupamento
        ', ['codtituloagrupamento' => $ag->codtituloagrupamento]);

        if (empty($codnegocios)) {
            throw new \Exception('Não foi possível localizar nenhum negócio vinculado ao fechamento!', 1);
        }

        $nota = null;
        foreach ($codnegocios as $row) {
            $negocio = Negocio::findOrFail((int)$row->codnegocio);
            $nota = NotaFiscalNegocioService::gerarNotaFiscalDoNegocio($negocio, $modelo, false, $nota, $todos);
        }

        if (empty($nota) || empty($nota->codnotafiscal)) {
            throw new \Exception('Erro ao gerar Nota Fiscal!', 1);
        }

        // duplicatas a partir dos títulos gerados pelo agrupamento
        foreach ($ag->TituloS as $tit) {
            $valor = abs((float)$tit->credito - (float)$tit->debito);
            $dupl = new NotaFiscalDuplicatas([
                'codnotafiscal' => $nota->codnotafiscal,
                'fatura'        => $tit->numero,
                'vencimento'    => $tit->vencimento,
                'valor'         => $valor,
            ]);
            $dupl->save();
        }

        return $nota;
    }

    public static function pendentes(array $filtros): array
    {
        $params = [];
        $sql = "
            select
                ge.codgrupoeconomico,
                ge.grupoeconomico,
                p.codpessoa,
                p.fantasia,
                gc.codgrupocliente,
                gc.grupocliente,
                fp.formapagamento,
                sum(t.saldo) as saldo,
                min(t.vencimento) as vencimento
            from tbltitulo t
            inner join tblnegocioformapagamento nfp on (nfp.codnegocioformapagamento = t.codnegocioformapagamento)
            inner join tblnegocio n on (n.codnegocio = nfp.codnegocio)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
            inner join tblpessoa p on (p.codpessoa = t.codpessoa)
            left join tblgrupocliente gc on (gc.codgrupocliente = p.codgrupocliente)
            left join tblgrupoeconomico ge on (ge.codgrupoeconomico = p.codgrupoeconomico)
            left join tblformapagamento fp on (fp.codformapagamento = p.codformapagamento)
            where t.saldo != 0
              and t.boleto = false
              and (nat.venda or nat.vendadevolucao)
        ";

        if (!empty($filtros['codportador']) && is_array($filtros['codportador'])) {
            $cods = array_map('intval', $filtros['codportador']);
            $sql .= ' and coalesce(t.codportador, -1) in (' . implode(',', $cods) . ') ';
        }
        if (!empty($filtros['codgrupocliente']) && is_array($filtros['codgrupocliente'])) {
            $cods = array_map('intval', $filtros['codgrupocliente']);
            $sql .= ' and coalesce(p.codgrupocliente, -1) in (' . implode(',', $cods) . ') ';
        }
        if (!empty($filtros['codgrupoeconomico'])) {
            $sql .= ' and p.codgrupoeconomico = :codgrupoeconomico ';
            $params['codgrupoeconomico'] = (int)$filtros['codgrupoeconomico'];
        }
        if (!empty($filtros['codpessoa'])) {
            $sql .= ' and p.codpessoa = :codpessoa ';
            $params['codpessoa'] = (int)$filtros['codpessoa'];
        }
        if (!empty($filtros['codtipotitulo'])) {
            $sql .= ' and t.codtipotitulo = :codtipotitulo ';
            $params['codtipotitulo'] = (int)$filtros['codtipotitulo'];
        }
        if (!empty($filtros['codformapagamento'])) {
            $sql .= ' and p.codformapagamento = :codformapagamento ';
            $params['codformapagamento'] = (int)$filtros['codformapagamento'];
        }
        if (!empty($filtros['vencimento_de'])) {
            $sql .= ' and t.vencimento >= :vencimento_de ';
            $params['vencimento_de'] = Carbon::parse($filtros['vencimento_de'])->format('Y-m-d');
        }
        if (!empty($filtros['vencimento_ate'])) {
            $sql .= ' and t.vencimento <= :vencimento_ate ';
            $params['vencimento_ate'] = Carbon::parse($filtros['vencimento_ate'])->format('Y-m-d');
        }

        $sql .= '
            group by gc.codgrupocliente, gc.grupocliente, ge.codgrupoeconomico, ge.grupoeconomico,
                     p.codpessoa, p.fantasia, fp.formapagamento
            having sum(t.saldo) > 0
        ';
        if (isset($filtros['valor_de']) && $filtros['valor_de'] !== '' && $filtros['valor_de'] !== null) {
            $sql .= ' and sum(t.saldo) >= :valor_de ';
            $params['valor_de'] = (float)$filtros['valor_de'];
        }
        if (isset($filtros['valor_ate']) && $filtros['valor_ate'] !== '' && $filtros['valor_ate'] !== null) {
            $sql .= ' and sum(t.saldo) <= :valor_ate ';
            $params['valor_ate'] = (float)$filtros['valor_ate'];
        }

        $sql .= '
            order by gc.grupocliente nulls first, coalesce(ge.grupoeconomico, p.fantasia), p.fantasia
        ';

        $regs = DB::select($sql, $params);
        return array_map(function ($r) {
            return [
                'codgrupocliente'   => $r->codgrupocliente,
                'grupocliente'      => $r->grupocliente,
                'codgrupoeconomico' => $r->codgrupoeconomico,
                'grupoeconomico'    => $r->grupoeconomico,
                'codpessoa'         => (int)$r->codpessoa,
                'fantasia'          => $r->fantasia,
                'formapagamento'    => $r->formapagamento,
                'saldo'             => (float)$r->saldo,
                'vencimento'        => $r->vencimento,
            ];
        }, $regs);
    }

    private static function validarLinha(Titulo $titulo, array $t): void
    {
        $saldo    = (float)($t['saldo'] ?? 0);
        $multa    = (float)($t['multa'] ?? 0);
        $juros    = (float)($t['juros'] ?? 0);
        $desconto = (float)($t['desconto'] ?? 0);
        $total    = (float)($t['total'] ?? 0);

        $calculado = $saldo + $multa + $juros - $desconto;
        if (abs($calculado - $total) > 0.005) {
            throw new \InvalidArgumentException(
                "Total incorreto para o título #{$titulo->codtitulo}! ({$calculado} != {$total})"
            );
        }

        if ($saldo > abs((float)$titulo->saldo) + 0.005) {
            throw new \InvalidArgumentException(
                "Saldo informado ({$saldo}) maior que saldo atual do título #{$titulo->codtitulo}!"
            );
        }
    }
}
