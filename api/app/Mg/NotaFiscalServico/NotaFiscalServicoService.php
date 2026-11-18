<?php

namespace Mg\NotaFiscalServico;

use Illuminate\Support\Facades\DB;
use Mg\Filial\Filial;

/**
 * Cadastro da nota fiscal de servico (TASK-279). A conversa com o Sistema Nacional
 * fica no NfseService.
 */
class NotaFiscalServicoService
{
    const RELACOES = [
        'Filial.Pessoa.Cidade.Estado',
        'Pessoa.Cidade.Estado',
        'Pessoa.PessoaEmailS',
        'TributacaoNacional',
        'Nbs',
        'CidadePrestacao.Estado',
    ];

    public static function query(array $filtros)
    {
        $qry = NotaFiscalServico::query();
        if (!empty($filtros['codfilial'])) {
            $qry->where('codfilial', $filtros['codfilial']);
        }
        if (!empty($filtros['codpessoa'])) {
            $qry->where('codpessoa', $filtros['codpessoa']);
        }
        if (!empty($filtros['status'])) {
            $status = is_array($filtros['status']) ? $filtros['status'] : explode(',', $filtros['status']);
            $qry->whereIn('status', $status);
        }
        if (!empty($filtros['emissao_inicio'])) {
            $qry->whereRaw('coalesce(emissao, criacao) >= ?', [$filtros['emissao_inicio'] . ' 00:00:00']);
        }
        if (!empty($filtros['emissao_fim'])) {
            $qry->whereRaw('coalesce(emissao, criacao) <= ?', [$filtros['emissao_fim'] . ' 23:59:59']);
        }
        if (!empty($filtros['numero'])) {
            $qry->where('numero', (int) $filtros['numero']);
        }
        if (!empty($filtros['nfsenumero'])) {
            $qry->where('nfsenumero', (int) $filtros['nfsenumero']);
        }
        return $qry;
    }

    /**
     * Filiais que emitem NFS-e, com a cidade (padrao do local da prestacao).
     */
    public static function filiais(): array
    {
        $sql = "
            select f.codfilial, f.filial, f.codfilial as value, f.filial as label, f.nfseserie,
                c.codcidade, c.cidade, e.sigla as uf
            from tblfilial f
            join tblpessoa p on p.codpessoa = f.codpessoa
            left join tblcidade c on c.codcidade = p.codcidade
            left join tblestado e on e.codestado = c.codestado
            where f.emitenfse = true
            and f.inativo is null
            order by f.codfilial
        ";
        return DB::select($sql);
    }

    public static function editavel(NotaFiscalServico $nfs): bool
    {
        return in_array($nfs->status, [NotaFiscalServico::STATUS_DIGITACAO, NotaFiscalServico::STATUS_ERRO]);
    }

    public static function verificarEditavel(NotaFiscalServico $nfs): void
    {
        if (!static::editavel($nfs)) {
            abort(422, 'Não é possível alterar uma nota ' . NotaFiscalServico::STATUS[$nfs->status] . '.');
        }
    }

    public static function verificarFilial(int $codfilial): Filial
    {
        $filial = Filial::findOrFail($codfilial);
        if (!$filial->emitenfse) {
            abort(422, "A filial {$filial->filial} não está configurada para emitir NFS-e.");
        }
        if (empty($filial->nfseserie)) {
            abort(422, "A filial {$filial->filial} não tem a série da DPS configurada.");
        }
        return $filial;
    }

    /**
     * Sem ISS retido a aliquota nao vai na nota (Simples, ISS no DAS): nao guarda.
     */
    protected static function normalizar(array $dados): array
    {
        if (empty($dados['issretido'])) {
            $dados['issaliquota'] = null;
        }
        return $dados;
    }

    public static function criar(array $dados): NotaFiscalServico
    {
        $dados = static::normalizar($dados);
        $filial = static::verificarFilial($dados['codfilial']);
        $nfs = new NotaFiscalServico($dados);
        $nfs->serie = $filial->nfseserie;
        $nfs->status = NotaFiscalServico::STATUS_DIGITACAO;
        $nfs->save();
        return $nfs;
    }

    public static function alterar(NotaFiscalServico $nfs, array $dados): NotaFiscalServico
    {
        static::verificarEditavel($nfs);
        $dados = static::normalizar($dados);
        // Depois de numerada a DPS nao troca de filial (o numero e' da serie dela)
        if (!empty($nfs->numero) && $dados['codfilial'] != $nfs->codfilial) {
            abort(422, 'A DPS já foi numerada: não dá para trocar a filial.');
        }
        if (empty($nfs->numero) && $dados['codfilial'] != $nfs->codfilial) {
            $filial = static::verificarFilial($dados['codfilial']);
            $nfs->serie = $filial->nfseserie;
        }
        $nfs->fill($dados);
        // A recusa era da versao anterior da nota. O status fica ERR: a DPS numerada
        // pode ter sido autorizada sem a resposta chegar (o Enviar consulta antes)
        $nfs->mensagem = null;
        $nfs->save();
        return $nfs;
    }

    public static function excluir(NotaFiscalServico $nfs): void
    {
        // So em digitacao: ERR pode ter sido autorizada sem resposta (consultar antes)
        if ($nfs->status != NotaFiscalServico::STATUS_DIGITACAO) {
            abort(422, 'Só dá para excluir nota em digitação.');
        }
        $nfs->delete();
    }

    public static function duplicar(NotaFiscalServico $original): NotaFiscalServico
    {
        $filial = static::verificarFilial($original->codfilial);
        $nova = new NotaFiscalServico($original->only([
            'codfilial',
            'codpessoa',
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
        ]));
        $nova->serie = $filial->nfseserie;
        $nova->competencia = now()->format('Y-m-d');
        $nova->status = NotaFiscalServico::STATUS_DIGITACAO;
        $nova->save();
        return $nova;
    }
}
