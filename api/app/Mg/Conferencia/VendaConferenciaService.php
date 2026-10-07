<?php

namespace Mg\Conferencia;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioAcerto;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloService;

/**
 * Venda desbalanceada (M9 doc-3): Σ pagamentos efetivados + Σ parcelas +
 * Σ acertos ≠ total. Nasce de correcao de lancamento (valor errado,
 * registro indevido) e vira pendencia da filial; o gerente ou o financeiro
 * da' o destino da diferenca (acerto).
 */
class VendaConferenciaService
{
    // conta contabil padrao do titulo do acerto (a mesma do M8)
    const CONTA_COLABORADOR = 42; // Despesa Colaboradores
    const CONTA_VENDA = 2;        // Venda Mercadoria

    const CONSUMIDOR_FINAL = 1;

    // o que foi pago pela venda: pagamento de saida (contrario, devolucao)
    // entra negativo, como no fechar do PDV
    const SQL_PAGO = "
        coalesce((select sum(case
                when p.codpagamentoorigem is not null
                    or (p.codportadororigem is not null and p.codportadordestino is null)
                then -p.total else p.total end)
            from tblpagamento p
            where p.codnegocio = n.codnegocio and p.estado = 'E'), 0)
        + coalesce((select sum(np.valor) from tblnegocioparcela np where np.codnegocio = n.codnegocio), 0)
        + coalesce((select sum(a.valor) from tblnegocioacerto a where a.codnegocio = n.codnegocio and a.inativo is null), 0)
    ";

    public static function desbalanceadas(?array $filiais): array
    {
        $params = ['inicio' => ConferenciaService::inicio()->format('Y-m-d H:i:s')];
        $where = '';
        if ($filiais !== null) {
            $marcas = [];
            foreach (array_values($filiais) as $i => $f) {
                $params["f{$i}"] = $f;
                $marcas[] = ":f{$i}";
            }
            $where = ' and n.codfilial in (' . implode(',', $marcas) . ')';
        }
        $sql = "
            select * from (
                select n.codnegocio, n.lancamento, n.codfilial, f.filial, pe.fantasia,
                    round(n.valortotal - (" . static::SQL_PAGO . "), 2) as diferenca
                from tblnegocio n
                inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
                left join tblfilial f on (f.codfilial = n.codfilial)
                left join tblpessoa pe on (pe.codpessoa = n.codpessoa)
                where n.codnegociostatus = 2
                and nat.financeiro
                and n.lancamento >= :inicio
                and exists (select 1 from tblpagamento p where p.codnegocio = n.codnegocio)
                {$where}
            ) x
            where abs(x.diferenca) >= 0.01
            order by x.lancamento
        ";
        return DB::select($sql, $params);
    }

    public static function detalhe(Negocio $negocio): array
    {
        $negocio->load(['Pessoa', 'Pdv', 'Filial', 'NegocioParcelaS.Titulo', 'NegocioAcertoS.Pessoa', 'NegocioAcertoS.Titulo', 'NegocioAcertoS.UsuarioCriacao']);
        return [
            'codnegocio' => $negocio->codnegocio,
            'codfilial' => $negocio->codfilial,
            'filial' => optional($negocio->Filial)->filial,
            'codpessoa' => $negocio->codpessoa,
            'fantasia' => optional($negocio->Pessoa)->fantasia,
            'pdv' => optional($negocio->Pdv)->apelido,
            'lancamento' => $negocio->lancamento,
            'valortotal' => (float) $negocio->valortotal,
            'diferenca' => static::diferenca($negocio),
            'pagamentos' => ConferenciaPagamentoResource::collection(
                $negocio->PagamentoS()->orderBy('transacao')->orderBy('codpagamento')->get()
            ),
            'parcelas' => $negocio->NegocioParcelaS->map(fn ($np) => [
                'codnegocioparcela' => $np->codnegocioparcela,
                'condicao' => $np->condicao,
                'vencimento' => $np->vencimento,
                'valor' => (float) $np->valor,
                'codtitulo' => $np->codtitulo,
                'numero' => optional($np->Titulo)->numero,
            ])->values(),
            'acertos' => $negocio->NegocioAcertoS->map(fn ($a) => [
                'codnegocioacerto' => $a->codnegocioacerto,
                'valor' => $a->valor,
                'destino' => $a->destino,
                'destinodescricao' => NegocioAcerto::DESTINOS[$a->destino] ?? null,
                'codpessoa' => $a->codpessoa,
                'fantasia' => optional($a->Pessoa)->fantasia,
                'codtitulo' => $a->codtitulo,
                'numero' => optional($a->Titulo)->numero,
                'justificativa' => $a->justificativa,
                'inativo' => $a->inativo,
                'criacao' => $a->criacao,
                'usuariocriacao' => $a->usuariocriacao,
            ])->values(),
        ];
    }

    // positivo = faltou pagar; negativo = pagou a mais
    public static function diferenca(Negocio $negocio): float
    {
        $r = DB::selectOne("
            select round(n.valortotal - (" . static::SQL_PAGO . "), 2) as diferenca
            from tblnegocio n where n.codnegocio = :codnegocio
        ", ['codnegocio' => $negocio->codnegocio]);
        return (float) $r->diferenca;
    }

    public static function acertar(Negocio $negocio, string $destino, ?int $codpessoa, string $justificativa): NegocioAcerto
    {
        $diferenca = static::diferenca($negocio);
        if (abs($diferenca) < 0.01) {
            abort(422, 'Os pagamentos desta venda já batem com o total.');
        }
        $permitidos = $diferenca > 0
            ? [NegocioAcerto::DESTINO_PERDAO, NegocioAcerto::DESTINO_COLABORADOR, NegocioAcerto::DESTINO_DUPLICATA]
            : [NegocioAcerto::DESTINO_PERDAO, NegocioAcerto::DESTINO_CREDITO];
        if (!in_array($destino, $permitidos)) {
            abort(422, ($diferenca > 0 ? 'Faltou pagar' : 'Pagou a mais') . ': destino não permitido.');
        }
        $justificativa = trim($justificativa);
        if (mb_strlen($justificativa) < 5) {
            abort(422, 'Informe a justificativa do acerto.');
        }

        $titulo = null;
        switch ($destino) {
            case NegocioAcerto::DESTINO_COLABORADOR:
                if (empty($codpessoa)) {
                    abort(422, 'Informe o colaborador.');
                }
                $titulo = static::titulo($negocio, TituloService::TIPO_VALE_COLABORADOR, $codpessoa, static::CONTA_COLABORADOR, abs($diferenca), 30, $justificativa);
                break;
            case NegocioAcerto::DESTINO_DUPLICATA:
            case NegocioAcerto::DESTINO_CREDITO:
                $codpessoa = $codpessoa ?: $negocio->codpessoa;
                if ($codpessoa == static::CONSUMIDOR_FINAL) {
                    abort(422, 'Venda para consumidor final: informe o cliente.');
                }
                $tipo = $destino == NegocioAcerto::DESTINO_DUPLICATA
                    ? TituloService::TIPO_DUPLICATA_RECEBER
                    : TituloService::TIPO_CREDITO_CLIENTE;
                $dias = $destino == NegocioAcerto::DESTINO_DUPLICATA ? 30 : 365;
                $titulo = static::titulo($negocio, $tipo, $codpessoa, static::CONTA_VENDA, abs($diferenca), $dias, $justificativa);
                break;
            default:
                $codpessoa = null;
        }

        return NegocioAcerto::create([
            'codnegocio' => $negocio->codnegocio,
            'valor' => $diferenca,
            'destino' => $destino,
            'codpessoa' => $codpessoa,
            'codtitulo' => optional($titulo)->codtitulo,
            'justificativa' => mb_substr($justificativa, 0, 300),
        ]);
    }

    private static function titulo(Negocio $negocio, int $tipo, int $codpessoa, int $conta, float $valor, int $dias, string $justificativa): Titulo
    {
        $hoje = Carbon::today();
        return TituloService::criar([
            'codtipotitulo' => $tipo,
            'codfilial' => $negocio->codfilial,
            'codpessoa' => $codpessoa,
            'codcontacontabil' => $conta,
            'transacao' => $hoje->format('Y-m-d'),
            'emissao' => $hoje->format('Y-m-d'),
            'vencimento' => $hoje->copy()->addDays($dias)->format('Y-m-d'),
            'observacao' => mb_substr("Acerto da venda #{$negocio->codnegocio}: {$justificativa}", 0, 255),
            'valor' => $valor,
        ]);
    }

    // desfaz o acerto (a venda volta a ficar desbalanceada); titulo ja'
    // movimentado nao se desfaz
    public static function desfazer(NegocioAcerto $acerto): NegocioAcerto
    {
        if (!empty($acerto->inativo)) {
            return $acerto;
        }
        if ($acerto->Titulo && empty($acerto->Titulo->estornado)) {
            TituloService::estornar($acerto->Titulo, "Acerto da venda #{$acerto->codnegocio} desfeito");
        }
        $acerto->inativo = Carbon::now();
        $acerto->save();
        return $acerto;
    }
}
