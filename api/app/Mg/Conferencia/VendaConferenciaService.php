<?php

namespace Mg\Conferencia;

use Illuminate\Support\Facades\DB;
use Mg\Negocio\Negocio;

/**
 * Venda com diferenca (M9 doc-3): Σ pagamentos efetivados + Σ parcelas ≠
 * total. Nasce de correcao de lancamento (valor errado, registro indevido)
 * e vira pendencia da filial. Venda nao fica com diferenca: ou e' pagamento
 * ou e' duplicata; o conserto e' no proprio negocio.
 */
class VendaConferenciaService
{
    // o que foi pago pela venda: pagamento de saida (contrario, devolucao)
    // entra negativo, como no fechar do PDV
    const SQL_PAGO = "
        coalesce((select sum(case
                when p.codpagamentoorigem is not null
                    or (p.codportadororigem is not null and p.codportadordestino is null)
                then -p.total else p.total end)
            from tblpagamento p
            where p.codnegocio = n.codnegocio and p.estado = 'E'), 0)
        + coalesce((select sum(np.valor) from tblnegocioparcela np where np.codnegocio = n.codnegocio and np.inativo is null), 0)
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
        $negocio->load(['Pessoa', 'Pdv', 'Filial', 'NegocioParcelaS.Titulo']);
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
}
