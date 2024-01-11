<?php

namespace Mg\GrupoEconomico;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\MgService;
use Mg\Cidade\Cidade;
use Mg\NFePHP\NFePHPService;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Illuminate\Support\Facades\Http;

class GrupoEconomicoService
{

    public static function index($pesquisa)
    {
        $grupos = GrupoEconomico::orderBy('grupoeconomico', 'asc')
            ->where('grupoeconomico', 'ilike', $pesquisa)->paginate(25);

        return $grupos;
    }

    public static function create($data)
    {

        $pessoa = new GrupoEconomico($data);
        $pessoa->save();
        return $pessoa->refresh();
    }

    public static function update($pessoa, $data)
    {
        $pessoa->fill($data);
        $pessoa->save();
        return $pessoa;
    }


    public static function delete($grupo)
    {
        $pessoasGrupo = Pessoa::where('codgrupoeconomico', $grupo->codgrupoeconomico)->get();

        foreach ($pessoasGrupo as $peg) {
            $peg->codgrupoeconomico = null;
            $peg->update();
        }

        return $grupo->delete();
    }

    public static function buscarPeloCnpjCpf(bool $fisica, string $cnpj)
    {
        $cnpj = trim(numeroLimpo($cnpj));
        if ($fisica) {
            $pessoa = Pessoa::where('cnpj', $cnpj)
                ->where('fisica', $fisica)
                ->whereNotNull('codgrupoeconomico')
                ->orderBy('alteracao', 'desc')
                ->first();
        } else {
            $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
            $raiz = substr($cnpj, 0, 8);
            $pessoa = Pessoa::whereRaw("substring(trim(to_char(cnpj, '00000000000000')), 1, 8) ilike '{$raiz}%'")
                ->where('fisica', $fisica)
                ->whereNotNull('codgrupoeconomico')
                ->orderBy('alteracao', 'desc')
                ->first();
        }
        if ($pessoa) {
            return $pessoa->GrupoEconomico;
        }
        return null;
    }


    public static function removerDoGrupo($pessoa)
    {

        $pessoa->update(['codgrupoeconomico' => null]);
        return $pessoa->refresh();
    }

    public static function inativar(GrupoEconomico $grupo)
    {
        $grupo->update(['inativo' => Carbon::now()]);
        return $grupo->refresh();
    }

    public static function ativar($grupo)
    {
        $grupo->inativo = null;
        $grupo->update();
        return $grupo;
    }

    public static function totaisNegocios($data, $codgrupoeconomico)
    {

        $sql = 'select 
        prod.codproduto, 
        prod.produto, 
        pv.variacao,
        max(n.lancamento) as lancamento, 
        max(n.codnegocio) as codnegocio, 
        count(npb.codnegocioprodutobarra) as negocios,
        sum(npb.quantidade * case when nat.vendadevolucao = \'1\' then -1 else 1 end) as quantidade,
	    sum(npb.valortotal * case when nat.vendadevolucao = \'1\' then -1 else 1 end) as valortotal
        from tblpessoa p
        inner join tblnegocio n on (p.codpessoa = n.codpessoa)
        inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
        inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
        inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
        inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
        inner join tblproduto prod on (prod.codproduto  = pb.codproduto)
        where n.codnegociostatus = 2
        and (nat.venda = true or nat.compra = true or nat.vendadevolucao = true)';


        if (empty($data['codpessoa'])) {
            $sql .= ' and p.codgrupoeconomico = :codgrupoeconomico';

            $params['codgrupoeconomico'] = $codgrupoeconomico;
        }

        if (!empty($data['codpessoa'])) {
            $sql .= ' and p.codpessoa = :codpessoa';
            $params['codpessoa'] = $data['codpessoa'];
        }

        if (!empty($data['desde'])) {
            $sql .= ' and lancamento >= :desde';
            $params['desde'] = $data['desde'];
        }

        $sql .= '
            group by 
            prod.codproduto, 
            prod.produto,
            pv.variacao
            order by 6 desc, 8 desc';

        $result = DB::select($sql, $params);

        return $result;
    }

    public static function titulosAbertos($data, $codgrupoeconomico)
    {

        $sql = 'select 
        t.codtitulo,
        t.numero,
        t.fatura,
        tipo.tipotitulo,
        conta.contacontabil,
        t.saldo,
        t.emissao,
        t.vencimento
        from tblpessoa p
        inner join tbltitulo t on (t.codpessoa = p.codpessoa)
        left join tbltipotitulo tipo on (tipo.codtipotitulo = t.codtipotitulo)
        left join tblcontacontabil conta on (conta.codcontacontabil = t.codcontacontabil)
        where t.saldo !=0';

        if (empty($data['codpessoa'])) {
            $sql .= ' and p.codgrupoeconomico = :codgrupoeconomico';

            $params['codgrupoeconomico'] = $codgrupoeconomico;
        }

        if (!empty($data['codpessoa'])) {
            $sql .= ' and p.codpessoa = :codpessoa';
            $params['codpessoa'] = $data['codpessoa'];
        }

        $sql .= '
            order by t.vencimento asc';

        $result = DB::select($sql, $params);

        return $result;
    }


    public static function nfeTerceiro($data, $codgrupoeconomico)
    {

        $sql = 'select 
        nft.codnfeterceiro,
        nft.nfechave,
        nft.serie,
        nft.numero,
        nft.emissao,
        nft.entrada,
        nft.indsituacao,
        nft.indmanifestacao,
        nft.valortotal 
        from tblpessoa p
        inner join tblnfeterceiro nft on (nft.codpessoa = p.codpessoa)
        where p.codgrupoeconomico = :codgrupoeconomico';

        $params['codgrupoeconomico'] = $codgrupoeconomico;

        if (!empty($data['codpessoa'])) {
            $sql .= ' and p.codpessoa = :codpessoa';
            $params['codpessoa'] = $data['codpessoa'];
        }

        if (!empty($data['desde'])) {
            $sql .= ' and nft.emissao >= :desde';
            $params['desde'] = $data['desde'];
        }

        $sql .= ' order by nft.emissao, nft.numero  desc';

        $result = DB::select($sql, $params);

        return $result;
    }


    public static function negocios($codpessoa, $codgrupoeconomico)
    {

        $sql = '
        select nat.naturezaoperacao, date_trunc(\'month\', n.lancamento) as mes, sum(n.valortotal * case when n.codoperacao = 1 then -1 else 1 end) as valortotal
        from tblpessoa p
        inner join tblnegocio n on (p.codpessoa = n.codpessoa)
        inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
        where p.codgrupoeconomico = :codgrupoeconomico
        and n.codnegociostatus = 2';


        $params['codgrupoeconomico'] = $codgrupoeconomico;

        if (!empty($codpessoa)) {

            $sql .= ' and p.codpessoa = :codpessoa';
            $params['codpessoa'] = $codpessoa;
        }


        $sql .= ' group by nat.naturezaoperacao, date_trunc(\'month\', n.lancamento) 
        order by 1, 2';

        $result = DB::select($sql, $params);


        return $result;
    }

    public static function topProdutos($codpessoa, $codgrupoeconomico, $desde)
    {

        $sql = '
        select prod.codproduto, prod.produto, sum(npb.valortotal * case when nat.vendadevolucao = \'1\' then -1 else 1 end) as valortotal
        from tblpessoa p
        inner join tblnegocio n on (p.codpessoa = n.codpessoa)
        inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
        inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
        inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
        inner join tblproduto prod on (prod.codproduto  = pb.codproduto)
        where n.codnegociostatus = 2
        and (nat.venda = true or nat.compra = true or nat.vendadevolucao = true)
        and p.codgrupoeconomico = :codgrupoeconomico';

        $params['codgrupoeconomico'] = $codgrupoeconomico;

        if (!empty($codpessoa)) {

            $sql .= ' and p.codpessoa = :codpessoa';
            $params['codpessoa'] = $codpessoa;
        }

        if (!empty($desde)) {
            $sql .= ' and lancamento >= :desde';
            $params['desde'] = $desde;
        }

        $sql .= ' group by prod.codproduto, prod.produto 
        order by 3 desc
        limit 10';



        $result = DB::select($sql, $params);

        return $result;
    }
}
