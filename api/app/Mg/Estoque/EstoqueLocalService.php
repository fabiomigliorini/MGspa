<?php

namespace Mg\Estoque;

use Illuminate\Support\Facades\DB;
use Mg\MgService;

class EstoqueLocalService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = EstoqueLocal::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['controlaestoque'])) {
            $qry->where('controlaestoque', $filter['controlaestoque']);
        }

        if (!empty($filter['estoquelocal'])) {
            $qry->palavras('estoquelocal', $filter['estoquelocal']);
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function salvar(array $dados, ?EstoqueLocal $local = null): EstoqueLocal
    {
        $local = $local ?? new EstoqueLocal();
        $local->fill($dados);
        $local->save();
        return $local;
    }

    // o que as telas mostram alem das colunas: nome da filial e da impressora
    public static function detalhar(EstoqueLocal $local): array
    {
        $ret = $local->toArray();
        unset($ret['filial'], $ret['impressora']);
        $ret['filial'] = $local->Filial->filial ?? null;
        $ret['impressora'] = $local->Impressora->descricao ?? null;
        $ret['impressoratipo'] = $local->Impressora->tipo ?? null;
        return $ret;
    }

    // Indicadores de qualidade do estoque do local como requisitado (TASK-55, etapa 11):
    // os itens das requisicoes que ele atendeu (separado total, com divergencias ou
    // entregue) criadas no periodo. Conta por item; tudo de colunas gravadas.
    public static function indicadores(int $codestoquelocal, string $inicio, string $fim): array
    {
        $itens = DB::select("
            select
                r.codrequisicao, r.criacao, r.status, r.codestoquelocalrequisitante,
                elr.sigla as siglarequisitante,
                ri.codrequisicaoitem, ri.codprodutobarra, ri.quantidade,
                ri.quantidadeatendida / coalesce(pe.quantidade, 1) as atendido,
                coalesce(ume.sigla, ump.sigla) as sigla,
                ri.motivo, ri.observacao, ri.codprodutobarrasubstituto,
                p.codproduto, p.produto, pv.variacao, pb.barras,
                ps.produto as produtosubstituto, pvs.variacao as variacaosubstituto,
                u.usuario as usuariojustificou
            from tblrequisicao r
            inner join tblestoquelocal elr on (elr.codestoquelocal = r.codestoquelocalrequisitante)
            inner join tblrequisicaoitem ri on (ri.codrequisicao = r.codrequisicao)
            inner join tblprodutobarra pb on (pb.codprodutobarra = ri.codprodutobarra)
            inner join tblproduto p on (p.codproduto = pb.codproduto)
            inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
            inner join tblunidademedida ump on (ump.codunidademedida = p.codunidademedida)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            left join tblunidademedida ume on (ume.codunidademedida = pe.codunidademedida)
            left join tblprodutobarra pbs on (pbs.codprodutobarra = ri.codprodutobarrasubstituto)
            left join tblproduto ps on (ps.codproduto = pbs.codproduto)
            left join tblprodutovariacao pvs on (pvs.codprodutovariacao = pbs.codprodutovariacao)
            left join tblusuario u on (u.codusuario = ri.codusuarioalteracao)
            where r.codestoquelocalrequisitada = :codestoquelocal
            and r.status in ('T', 'D', 'E')
            and r.criacao >= :inicio
            and r.criacao < (cast(:fim as date) + 1)
            order by r.criacao, r.codrequisicao, ri.codrequisicaoitem
        ", ['codestoquelocal' => $codestoquelocal, 'inicio' => $inicio, 'fim' => $fim]);

        $pedidos = array_filter($itens, fn ($i) => $i->quantidade > 0);
        $bateu = fn ($i) => abs($i->quantidade - $i->atendido) < 0.0005 && empty($i->motivo) && empty($i->codprodutobarrasubstituto);
        $motivos = [];
        foreach (\Mg\Requisicao\RequisicaoItem::MOTIVOS as $cod => $nome) {
            $motivos[$cod] = count(array_filter($itens, fn ($i) => $i->motivo === $cod));
        }
        $atendidos = count(array_filter($pedidos, $bateu));
        return [
            'resumo' => [
                'requisicoes' => count(array_unique(array_map(fn ($i) => $i->codrequisicao, $itens))),
                'itens' => count($pedidos),
                'atendidos' => $atendidos,
                'percentual' => count($pedidos) ? round(100 * $atendidos / count($pedidos), 1) : null,
                'motivos' => $motivos,
                'substituidos' => count(array_filter($itens, fn ($i) => !empty($i->codprodutobarrasubstituto))),
            ],
            'divergentes' => array_values(array_filter($itens, fn ($i) => !$bateu($i))),
        ];
    }

    public static function autocompletar($params)
    {
        $qry = static::pesquisar($params)
            ->select('codestoquelocal', 'estoquelocal')
            ->take(10);

        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->estoquelocal,
                'value' => $item->estoquelocal,
                'id' => $item->codestoquelocal,
            ];
        }

        return $ret;
    }

    public static function lojas()
    {
        return EstoqueLocal::ativo()
            ->where('codfilial', '!=', 199)
            ->where('deposito', false)
            ->orderBy('codestoquelocal')
            ->get();
    }

    public static function deposito()
    {
        return EstoqueLocal::ativo()
            ->where('deposito', true)
            ->orderBy('codestoquelocal')
            ->limit(1)
            ->get()[0] ?? null;
    }
}
