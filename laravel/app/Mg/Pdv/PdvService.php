<?php

namespace Mg\Pdv;

use DB;

class PdvService
{

    public static function dispositivo(
        $uuid,
        $ip,
        $latitude,
        $longitude,
        $precisao,
        $desktop,
        $navegador,
        $versaonavegador,
        $plataforma
    ) {
        $pdv = Pdv::firstOrNew(['uuid' => $uuid]);
        $pdv->ip = $ip;
        $pdv->latitude = $latitude;
        $pdv->longitude = $longitude;
        $pdv->precisao = $precisao;
        $pdv->desktop = $desktop;
        $pdv->navegador = $navegador;
        $pdv->versaonavegador = $versaonavegador;
        $pdv->plataforma = $plataforma;
        $pdv->save();
        return $pdv;
    }

    public static function podeAcessar($uuid)
    {
        $pdv = Pdv::where('uuid', $uuid)->first();
        if (!$pdv) {
            return false;
        }
        if ($pdv->autorizado) {
            return true;
        }
        return false;
    }

    public static function autoriza($uuid)
    {
        if (!static::podeAcessar($uuid)) {
            abort(403, 'Dispositivo Não Autorizado!');
        }
    }

    public static function produtoCount()
    {
        $sql = '
            select
            	count(pb.codprodutobarra) as count
            from tblprodutobarra pb 
        ';
        $regs = DB::select($sql);
        return $regs[0];
    }

    public static function produto($codprodutobarra, $limite)
    {
        $sincronizado = date('Y-m-d h:i:s');
        $sql = '
            select
            	pb.codprodutobarra,
                p.codproduto,
            	pb.barras,
                p.produto,
                pv.variacao,
                p.abc,
            	coalesce(ume.sigla, um.sigla) as sigla,
            	pe.quantidade,
            	pri.codimagem,
            	coalesce(pe.preco, p.preco * coalesce(pe.quantidade, 1)) as preco,
            	coalesce(pv.inativo, p.inativo) as inativo,
                :sincronizado as sincronizado
            from tblproduto p
            inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
            inner join tblprodutovariacao pv  on (pv.codproduto = p.codproduto)
            inner join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            left join tblunidademedida ume on (ume.codunidademedida = pe.codunidademedida)
            left join tblprodutoimagem pri on (pri.codprodutoimagem = pv.codprodutoimagem)
            where pb.codprodutobarra > :codprodutobarra
            order by pb.codprodutobarra
            limit :limite
        ';
        $regs = DB::select($sql, [
            'codprodutobarra' => $codprodutobarra,
            'limite' => $limite,
            'sincronizado' => $sincronizado
        ]);
        return array_map(function ($item) {
            if ($item->quantidade) {
                $item->quantidade = floatval($item->quantidade);
            }
            $item->preco = floatval($item->preco);
            $item->produto = static::montarDescricaoProduto($item->produto, $item->variacao, $item->sigla, $item->quantidade);
            $item->busca = $item->produto . ' ' . number_format($item->preco, 2, ',', '.');
            $item->buscaArr = array_values(array_unique(explode(' ', $item->busca)));
            return $item;
        }, $regs);
        return $regs;
    }

    public static function montarDescricaoProduto($produto, $variacao, $unidade, $quantidade)
    {
        $descricao = $produto;
        if (!empty($variacao)) {
            $descricao .= ' ' . $variacao;
        }
        $descricao .= ' ' . $unidade;
        if (!empty($quantidade)) {
            $descricao .= ' C/' . intval($quantidade);
        }
        return $descricao;
    }

    public static function pessoaCount()
    {
        $sql = '
            select
            	count(p.codpessoa) as count
            from tblpessoa p
        ';
        $regs = DB::select($sql);
        return $regs[0];
    }

    public static function pessoa($codpessoa, $limite)
    {
        $sincronizado = date('Y-m-d h:i:s');
        $sql = '
            select 
                p.codpessoa,
                p.pessoa,
                p.fantasia,
                case when p.fisica then to_char(p.cnpj, \'FM00000000000\') else to_char(p.cnpj, \'FM00000000000000\') end as cnpj,
                p.ie,
                p.fisica,
                p.endereco,
                p.numero,
                p.bairro,
                p.complemento,
                c.cidade,
                e.sigla as uf,
                p.vendedor,
                p.inativo,
                p.mensagemvenda,
                p.codformapagamento,
                p.desconto,
                :sincronizado as sincronizado
            from tblpessoa p
            left join tblcidade c on (c.codcidade = p.codcidade)
            left join tblestado e on (e.codestado = c.codestado)
            where codpessoa > :codpessoa
            order by codpessoa
            limit :limite
        ';
        $regs = DB::select($sql, [
            'codpessoa' => $codpessoa,
            'limite' => $limite,
            'sincronizado' => $sincronizado
        ]);
        $regs = array_map(function ($item) {
            $busca = "{$item->pessoa} . {$item->fantasia} . {$item->cnpj} " . str_pad($item->codpessoa, 8, "0", STR_PAD_LEFT);
            $busca = trim(preg_replace('/[^A-Za-z0-9 ]/', '', $busca));
            $busca = preg_replace('/\s+/', ' ', $busca);
            $item->busca = $busca;
            $item->buscaArr = array_values(array_unique(explode(' ', $item->busca)));
            return $item;
        }, $regs);
        return $regs;
    }

    public static function naturezaOperacao()
    {
        $sincronizado = date('Y-m-d h:i:s');
        $sql = '
            select 
                nat.codnaturezaoperacao, 
                nat.naturezaoperacao, 
                nat.codoperacao, 
                nat.estoque, 
                nat.compra, 
                nat.venda, 
                nat.vendadevolucao, 
                nat.financeiro,
                nat.codnaturezaoperacaodevolucao,
                :sincronizado as sincronizado
            from tblnaturezaoperacao nat 
            ';
        $regs = DB::select($sql, [
            'sincronizado' => $sincronizado
        ]);
        return $regs;
    }

    public static function estoqueLocal()
    {
        $sincronizado = date('Y-m-d h:i:s');
        $sql = '
            select 
                t.codestoquelocal,
                t.estoquelocal,
                t.deposito,
                t.inativo,
                t.sigla,
                f.codfilial,
                f.filial,
                f.codpessoa,
                f.codempresa,
                :sincronizado as sincronizado
            from tblestoquelocal t 
            inner join tblfilial f on (f.codfilial = t.codfilial)
            ';
        $regs = DB::select($sql, [
            'sincronizado' => $sincronizado
        ]);
        return $regs;
    }

    public static function formaPagamento()
    {
        $sincronizado = date('Y-m-d h:i:s');
        $sql = '
            select 
                fp.codformapagamento,
                fp.formapagamento,
                fp.boleto,
                fp.fechamento,
                fp.notafiscal,
                fp.parcelas,
                fp.diasentreparcelas,
                fp.avista,
                fp.valecompra,
                fp.lio,
                fp.pix,
                fp.stone,
                fp.integracao,
                :sincronizado as sincronizado
            from tblformapagamento fp
            ';
        $regs = DB::select($sql, [
            'sincronizado' => $sincronizado
        ]);
        return $regs;
    }

    public static function impressora()
    {
        $printers = json_decode(file_get_contents(base_path('printers.json')), true);
        $ret = [];
        $sincronizado = date('Y-m-d h:i:s');
        $codimpressora = 0;
        foreach ($printers as $impressora => $nome) {
            $codimpressora++;
            $ret[] = [
                'codimpressora' => $codimpressora,
                'impressora' => $impressora,
                'nome' => $nome,
                'sincronizado' => $sincronizado,
            ];
        }
        return $ret;
    }
}
