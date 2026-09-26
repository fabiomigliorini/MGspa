<?php

namespace Mg\Vale;

use Illuminate\Support\Facades\DB;

/**
 * Vales compras vendidos (tblnegociovale), ativos e cancelados.
 *
 * Os vales do sistema antigo foram convertidos em negocio
 * (vale_conversao.sql), entao a consulta enxerga o historico inteiro.
 * Cancelado = negocio cancelado ou vale excluido no PDV.
 */
class ValeEmitidoService
{
    const POR_PAGINA = 50;
    const LIMITE_RELATORIO = 500;

    public static function pesquisar(array $filtros, int $limite, int $offset = 0)
    {
        $where = ['n.codnegociostatus in (2, 3)'];
        $bind = [];

        if (!empty($filtros['codvalemodelo'])) {
            $where[] = 'nv.codvalemodelo = :codvalemodelo';
            $bind['codvalemodelo'] = $filtros['codvalemodelo'];
        }
        if (!empty($filtros['codpessoafavorecido'])) {
            $where[] = 'nv.codpessoafavorecido = :codpessoafavorecido';
            $bind['codpessoafavorecido'] = $filtros['codpessoafavorecido'];
        }
        if (!empty($filtros['codnegocio'])) {
            $where[] = 'nv.codnegocio = :codnegocio';
            $bind['codnegocio'] = $filtros['codnegocio'];
        }
        if (isset($filtros['valorde'])) {
            $where[] = 'nv.valorvale >= :valorde';
            $bind['valorde'] = $filtros['valorde'];
        }
        if (isset($filtros['valorate'])) {
            $where[] = 'nv.valorvale <= :valorate';
            $bind['valorate'] = $filtros['valorate'];
        }
        if (!empty($filtros['de'])) {
            $where[] = 'n.lancamento >= :de';
            $bind['de'] = substr($filtros['de'], 0, 10) . ' 00:00:00';
        }
        if (!empty($filtros['ate'])) {
            $where[] = 'n.lancamento <= :ate';
            $bind['ate'] = substr($filtros['ate'], 0, 10) . ' 23:59:59';
        }
        if (!empty($filtros['busca'])) {
            // aluno ou turma; numero procura tambem o vale antigo (o que esta
            // impresso no papel) e o negocio
            $busca = trim($filtros['busca']);
            $cond = 'concat_ws(\' \', nv.aluno, nv.turma) ilike :busca';
            $bind['busca'] = '%' . preg_replace('/\s+/', '%', $busca) . '%';
            if (ctype_digit($busca)) {
                $cond .= ' or nv.codvalecompra = :numerovale or nv.codnegocio = :numeronegocio';
                $bind['numerovale'] = (int) $busca;
                $bind['numeronegocio'] = (int) $busca;
            }
            $where[] = "({$cond})";
        }
        if (!empty($filtros['comsaldo'])) {
            // credito ainda nao gasto: no titulo e saldo negativo
            $where[] = 't.saldo < 0 and t.estornado is null';
        }
        $situacao = $filtros['situacao'] ?? 'ativo';
        if ($situacao === 'ativo') {
            $where[] = 'n.codnegociostatus = 2 and nv.inativo is null';
        } elseif ($situacao === 'cancelado') {
            $where[] = '(n.codnegociostatus = 3 or nv.inativo is not null)';
        }

        $sql = '
            select
                nv.codnegociovale,
                nv.codnegocio,
                nv.codvalecompra,
                nv.codvalemodelo,
                vm.modelo,
                nv.codpessoafavorecido,
                p.fantasia as favorecido,
                nv.aluno,
                nv.turma,
                nv.valorvale,
                nv.valortotal,
                n.lancamento,
                (n.codnegociostatus = 3 or nv.inativo is not null) as cancelado,
                t.codtitulo,
                t.numero,
                t.saldo
            from tblnegociovale nv
            inner join tblnegocio n on (n.codnegocio = nv.codnegocio)
            left join tblvalemodelo vm on (vm.codvalemodelo = nv.codvalemodelo)
            left join tblpessoa p on (p.codpessoa = nv.codpessoafavorecido)
            left join tbltitulo t on (t.codtitulo = nv.codtitulo)
            where ' . implode("\n              and ", $where) . '
            order by n.lancamento desc, nv.codnegociovale desc
            limit ' . (int) $limite . ' offset ' . (int) $offset;

        return DB::select($sql, $bind);
    }
}
