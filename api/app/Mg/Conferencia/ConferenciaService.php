<?php

namespace Mg\Conferencia;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Caixa\CaixaService;
use Mg\Maquineta\MaquinetaLoteService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;

/**
 * Conferencias do que o caixa movimentou (M9 doc-3): lote da maquineta,
 * cheque e vale recebidos, duplicata (confissao), venda
 * desbalanceada e, para o financeiro, PIX manual. Cada uma fecha sozinha.
 */
class ConferenciaService
{
    const TIPO_LOTE = 'lote';
    const TIPO_CHEQUE = 'cheque';
    const TIPO_VALE = 'vale';
    const TIPO_DUPLICATA = 'duplicata';
    const TIPO_VENDA = 'venda';
    const TIPO_PIX = 'pix';

    // conferencia so' do go-live em diante
    public static function inicio(): Carbon
    {
        return Carbon::parse(config('mg.conferencia_inicio'))->startOfDay();
    }

    // saving do Pagamento: cartao no lote, dinheiro na sessao da gaveta
    public static function vincular(Pagamento $pag): void
    {
        $transacao = $pag->transacao ? Carbon::parse($pag->transacao) : Carbon::now();
        if ($transacao->lt(static::inicio())) {
            return;
        }
        MaquinetaLoteService::vincular($pag);
        CaixaService::vincular($pag);
    }

    // filtro de filial: a pedida (se pode) ou as que o usuario confere
    private static function filiais(?int $codfilial): ?array
    {
        if (!empty($codfilial)) {
            ConferenciaAutorizador::autorizar($codfilial);
            return [$codfilial];
        }
        $filiais = ConferenciaAutorizador::filiais();
        if ($filiais !== null && empty($filiais)) {
            abort(403, 'Só Financeiro, Administrador ou Gerente!');
        }
        return $filiais;
    }

    private static function whereFilial(string $coluna, ?array $filiais, array &$params, string $nome = 'f'): string
    {
        if ($filiais === null) {
            return '';
        }
        $marcas = [];
        foreach (array_values($filiais) as $i => $f) {
            $params["{$nome}{$i}"] = $f;
            $marcas[] = ":{$nome}{$i}";
        }
        return " and {$coluna} in (" . implode(',', $marcas) . ')';
    }

    // tudo que esta' pendente de conferencia na filial (ou nas filiais do
    // usuario). Valores do sistema nao vao para a lista: a conferencia e'
    // as cegas.
    public static function pendencias(?int $codfilial): array
    {
        $filiais = static::filiais($codfilial);
        $inicio = static::inicio()->format('Y-m-d H:i:s');
        $ret = [];

        // lotes de maquineta abertos com movimento (as da filial e as
        // compartilhadas, que aparecem em todas)
        $params = [];
        $where = '';
        if ($filiais !== null) {
            $where = ' and (m.compartilhada or ' . substr(static::whereFilial('m.codfilial', $filiais, $params), 5) . ')';
        }
        foreach (DB::select("
            select l.codmaquinetalote, l.abertura, m.codmaquineta, m.apelido, m.codfilial, f.filial, pe.fantasia as adquirente,
                (select count(*) from tblpagamento p where p.codmaquinetalote = l.codmaquinetalote) as quantidade,
                (select string_agg(distinct coalesce(pdv.apelido, 'Escritório'), ', ')
                    from tblpagamento p left join tblpdv pdv on (pdv.codpdv = p.codpdv)
                    where p.codmaquinetalote = l.codmaquinetalote) as pdvs
            from tblmaquinetalote l
            inner join tblmaquineta m on (m.codmaquineta = l.codmaquineta)
            left join tblfilial f on (f.codfilial = m.codfilial)
            left join tblpessoa pe on (pe.codpessoa = m.codpessoa)
            where l.fechamento is null
            and (
                exists (select 1 from tblpagamento p where p.codmaquinetalote = l.codmaquinetalote)
                or exists (select 1 from tblpagamento p where p.codmaquinetalotecancelamento = l.codmaquinetalote)
            )
            {$where}
            order by l.abertura
        ", $params) as $r) {
            $ret[] = [
                'tipo' => static::TIPO_LOTE,
                'id' => $r->codmaquinetalote,
                'titulo' => "{$r->apelido} ({$r->adquirente})",
                'subtitulo' => "{$r->quantidade} lançamentos desde " . Carbon::parse($r->abertura)->format('d/m H:i') . ($r->pdvs ? " · {$r->pdvs}" : ''),
                'data' => $r->abertura,
                'codfilial' => $r->codfilial,
                'filial' => $r->filial,
                'conferivel' => true,
            ];
        }

        // cheque e vale recebidos no PDV, item a item
        $params = ['inicio' => $inicio];
        $where = static::whereFilial('p.codfilial', $filiais, $params);
        foreach (DB::select("
            select p.codpagamento, p.meio, p.total, p.transacao, p.codfilial, f.filial, p.codnegocio,
                coalesce(pn.fantasia, pe.fantasia) as fantasia, pdv.apelido as pdv
            from tblpagamento p
            left join tblfilial f on (f.codfilial = p.codfilial)
            left join tblpdv pdv on (pdv.codpdv = p.codpdv)
            left join tblnegocio n on (n.codnegocio = p.codnegocio)
            left join tblpessoa pn on (pn.codpessoa = n.codpessoa)
            left join tblpessoa pe on (pe.codpessoa = p.codpessoa)
            where p.meio in (2, 12)
            and p.estado = 'E'
            and p.codpdv is not null
            and p.codpagamentoorigem is null
            and p.conferencia is null
            and p.transacao >= :inicio
            {$where}
            order by p.transacao
        ", $params) as $r) {
            $ret[] = [
                'tipo' => $r->meio == PagamentoService::MEIO_CHEQUE ? static::TIPO_CHEQUE : static::TIPO_VALE,
                'id' => $r->codpagamento,
                'titulo' => ($r->meio == PagamentoService::MEIO_CHEQUE ? 'Cheque' : 'Vale') . ' de ' . ($r->fantasia ?? '—'),
                'subtitulo' => 'R$ ' . number_format($r->total, 2, ',', '.') . ' · ' . ($r->pdv ?? '') . ' · ' . Carbon::parse($r->transacao)->format('d/m H:i'),
                'data' => $r->transacao,
                'codfilial' => $r->codfilial,
                'filial' => $r->filial,
                'conferivel' => true,
            ];
        }

        // duplicatas: venda a prazo sem a confissao assinada escaneada
        $params = ['inicio' => $inicio];
        $where = static::whereFilial('n.codfilial', $filiais, $params);
        foreach (DB::select("
            select n.codnegocio, n.lancamento, n.valortotal, n.codfilial, f.filial, pe.fantasia, pdv.apelido as pdv,
                (select sum(np.valor) from tblnegocioparcela np where np.codnegocio = n.codnegocio
                    and np.condicao in ('F', 'P', 'B')) as valorprazo
            from tblnegocio n
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
            left join tblfilial f on (f.codfilial = n.codfilial)
            left join tblpessoa pe on (pe.codpessoa = n.codpessoa)
            left join tblpdv pdv on (pdv.codpdv = n.codpdv)
            where n.codnegociostatus = 2
            and nat.venda
            and n.confissao is null
            and n.lancamento >= :inicio
            and exists (
                select 1 from tblnegocioparcela np
                where np.codnegocio = n.codnegocio
                and np.condicao in ('F', 'P', 'B')
            )
            {$where}
            order by n.lancamento
        ", $params) as $r) {
            $ret[] = [
                'tipo' => static::TIPO_DUPLICATA,
                'id' => $r->codnegocio,
                'titulo' => 'Duplicata de ' . ($r->fantasia ?? '—'),
                'subtitulo' => "Venda #{$r->codnegocio} · R$ " . number_format($r->valorprazo, 2, ',', '.') . ' a prazo · ' . ($r->pdv ?? '') . ' · ' . Carbon::parse($r->lancamento)->format('d/m H:i'),
                'data' => $r->lancamento,
                'codfilial' => $r->codfilial,
                'filial' => $r->filial,
                'conferivel' => true,
            ];
        }

        // vendas desbalanceadas
        foreach (VendaConferenciaService::desbalanceadas($filiais) as $r) {
            $ret[] = [
                'tipo' => static::TIPO_VENDA,
                'id' => $r->codnegocio,
                'titulo' => 'Venda #' . $r->codnegocio . ' de ' . ($r->fantasia ?? '—'),
                'subtitulo' => ($r->diferenca > 0 ? 'Faltou ' : 'Sobrou ') . 'R$ ' . number_format(abs($r->diferenca), 2, ',', '.') . ' · ' . Carbon::parse($r->lancamento)->format('d/m H:i'),
                'data' => $r->lancamento,
                'codfilial' => $r->codfilial,
                'filial' => $r->filial,
                'conferivel' => true,
            ];
        }

        // PIX por chave e deposito a receber: so' o financeiro, contra o
        // extrato (baixa o titulo no contas)
        if (ConferenciaAutorizador::irrestrito()) {
            $params = ['inicio' => $inicio];
            $where = static::whereFilial('t.codfilial', $filiais, $params);
            foreach (DB::select("
                select t.codtitulo, t.numero, t.saldo, t.vencimento, t.codfilial, f.filial, pe.fantasia, np.codnegocio
                from tblnegocioparcela np
                inner join tbltitulo t on (t.codtitulo = np.codtitulo)
                inner join tblnegocio n on (n.codnegocio = np.codnegocio)
                left join tblfilial f on (f.codfilial = t.codfilial)
                left join tblpessoa pe on (pe.codpessoa = t.codpessoa)
                where np.condicao = 'X'
                and t.saldo <> 0
                and n.lancamento >= :inicio
                {$where}
                order by t.vencimento
            ", $params) as $r) {
                $ret[] = [
                    'tipo' => static::TIPO_PIX,
                    'id' => $r->codtitulo,
                    'titulo' => 'PIX/Depósito de ' . ($r->fantasia ?? '—'),
                    'subtitulo' => "Título {$r->numero} · R$ " . number_format(abs($r->saldo), 2, ',', '.') . " · venda #{$r->codnegocio}",
                    'data' => $r->vencimento,
                    'codfilial' => $r->codfilial,
                    'filial' => $r->filial,
                    'conferivel' => true,
                ];
            }
        }

        return $ret;
    }

    // cheque e vale recebido: conferido item a item
    public static function conferirPagamento(Pagamento $pag): Pagamento
    {
        if (!in_array($pag->meio, [PagamentoService::MEIO_CHEQUE, PagamentoService::MEIO_VALE])) {
            abort(422, 'Só cheque e vale recebido se conferem um a um.');
        }
        $pag->conferencia = Carbon::now();
        $pag->codusuarioconferencia = Auth::user()->codusuario;
        $pag->save();
        return $pag;
    }

    public static function reabrirPagamento(Pagamento $pag): Pagamento
    {
        $pag->conferencia = null;
        $pag->codusuarioconferencia = null;
        $pag->save();
        return $pag;
    }

    // filial a que um pagamento pertence para a conferencia
    public static function filialDoPagamento(Pagamento $pag): ?int
    {
        return $pag->codfilial ?? optional($pag->Negocio)->codfilial;
    }
}
