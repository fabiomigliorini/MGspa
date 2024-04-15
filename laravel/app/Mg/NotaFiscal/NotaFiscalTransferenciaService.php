<?php

namespace Mg\NotaFiscal;

use Illuminate\Support\Facades\DB;
use Mg\MgModel;
use Carbon\Carbon;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\NfeTerceiro\NfeTerceiro;

class NotaFiscalTransferenciaService extends MGModel
{
    public static function ListarNotasTransf($data)
    {
        $sql = '
        with lancadas as (
            select
                f2.filial as origem,
                f.filial as destino,
                nat.naturezaoperacao as natureza,
                nat.codnaturezaoperacao,
                count(*) as qtd,
                sum(nf.valortotal) as valor
            from tblnotafiscal nf
            inner join tblfilial f on (f.codfilial = nf.codfilial)
            inner join tblfilial f2 on (f2.codpessoa = nf.codpessoa)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
            where nf.emitida = false
            and nf.emissao >= :data
            and nf.codpessoa in (select f2.codpessoa from tblfilial f2)
            and nf.nfeinutilizacao is null
            and nf.nfecancelamento is null
            group by f.filial, f2.filial, nat.naturezaoperacao, nat.codnaturezaoperacao
            order by 1, 2, 3, 4
        ),
        emitidas as (
            select
                f.filial as origem,
                f2.filial as destino,
                nat.naturezaoperacao as natureza,
                nat.codnaturezaoperacao,
                nat.codnaturezaoperacaodevolucao,
                count(*) as qtd,
                sum(nf.valortotal) as valor
            from tblnotafiscal nf
            inner join tblfilial f on (f.codfilial = nf.codfilial)
            inner join tblfilial f2 on (f2.codpessoa = nf.codpessoa)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
            where nf.emitida = true
            and nf.emissao >= :data
            and nf.codpessoa in (select f2.codpessoa from tblfilial f2)
            and nf.nfeinutilizacao is null
            and nf.nfecancelamento is null
            group by f.filial, f2.filial, nat.naturezaoperacao, nat.codnaturezaoperacao, nat.codnaturezaoperacaodevolucao
            order by 1, 2, 3, 4, 5
        )
        select
            e.valor as emitida,
            l.valor as lancada,
            abs(coalesce(e.valor, 0) - coalesce(l.valor, 0)) as valor_dif,
            coalesce(e.origem, l.origem) as origem,
            coalesce(e.destino, l.destino) as destino,
            e.natureza as natureza_e,
            l.natureza as natureza_l,
            e.codnaturezaoperacao as codnaturezaoperacao_e,
            l.codnaturezaoperacao as codnaturezaoperacao_l,
            e.qtd as qtd_e,
            l.qtd as qtd_l,
            coalesce(e.qtd, 0) - coalesce(l.qtd, 0) as qtd_dif
        from emitidas e
        full outer join lancadas l on (l.origem = e.origem and l.destino = e.destino and l.codnaturezaoperacao = e.codnaturezaoperacaodevolucao)
        order by 3 desc, 4, 5, 6, 7, 8, 9 desc
        ';

        $resultado = DB::select($sql, [
            'data' => $data
        ]);

        return $resultado;
    }

    public static function geraTransferencias($codfilial)
    {
        DB::BeginTransaction();

        $sql = "

            --Negocios gerados a partir de uma Filial, com NF emitida por outra Filial
            select
                      tblnegocio.codfilial
                    , tblnegocio.codestoquelocal
                    , destino.codpessoa
                    , tblnegocio.codnaturezaoperacao
                    , tblnegocioprodutobarra.codnegocioprodutobarra
            from tblnotafiscal
            inner join tblnotafiscalprodutobarra on (tblnotafiscalprodutobarra.codnotafiscal = tblnotafiscal.codnotafiscal)
            inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocioprodutobarra = tblnotafiscalprodutobarra.codnegocioprodutobarra)
            inner join tblnegocio on (tblnegocio.codnegocio = tblnegocioprodutobarra.codnegocio)
            inner join tblfilial as origem on (origem.codfilial = tblnegocio.codfilial)
            inner join tblfilial as destino on (destino.codfilial = tblnotafiscal.codfilial)
            left join (

                    select
                              tblnotafiscal.codnotafiscal
                            , tblnotafiscal.codfilial
                            , tblnotafiscal.codpessoa
                            , tblnotafiscalprodutobarra.codnotafiscalprodutobarra
                            , tblnotafiscalprodutobarra.codnegocioprodutobarra
                    from tblnotafiscal
                    inner join tblnotafiscalprodutobarra on (tblnotafiscalprodutobarra.codnotafiscal = tblnotafiscal.codnotafiscal)
                    where tblnotafiscal.emitida = true
                    --and tblnotafiscal.nfeautorizacao is not null -- Nao importa se ainda esta em digitacao
                    and tblnotafiscal.nfeinutilizacao is null
                    and tblnotafiscal.nfecancelamento is null
                    and tblnotafiscalprodutobarra.codnegocioprodutobarra is not null

                    ) emitida on (

                    emitida.codfilial = tblnegocio.codfilial
                    and emitida.codpessoa = destino.codpessoa
                    AND emitida.codnegocioprodutobarra = tblnegocioprodutobarra.codnegocioprodutobarra

                    )
            where tblnotafiscal.emitida = true
            and tblnotafiscal.nfeautorizacao is not null
            and tblnotafiscal.nfeinutilizacao is null
            and tblnotafiscal.nfecancelamento is null
            and tblnotafiscal.emissao >= (now() - interval '3 months')
            and tblnegocio.codfilial <> destino.codfilial
            and origem.codempresa = destino.codempresa
            and emitida.codnotafiscal is null
            and origem.codfilial = :codfilial
            and origem.codfilial != 199 -- Defeito
            and destino.codfilial != 199 -- Defeito
            --limit 50

            union all

            --Negocios Intercompany
            select
                      tblnegocio.codfilial
                    , tblnegocio.codestoquelocal
                    , tblnegocio.codpessoa
                    , tblnegocio.codnaturezaoperacao
                    , tblnegocioprodutobarra.codnegocioprodutobarra
            from tblnegocio
            inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocio = tblnegocio.codnegocio)
            inner join tblfilial as origem on (origem.codfilial = tblnegocio.codfilial)
            inner join tblfilial as destino on (destino.codpessoa = tblnegocio.codpessoa)
            left join (

                    select
                              tblnotafiscal.codnotafiscal
                            , tblnotafiscal.codfilial
                            , tblnotafiscal.codpessoa
                            , tblnotafiscalprodutobarra.codnotafiscalprodutobarra
                            , tblnotafiscalprodutobarra.codnegocioprodutobarra
                    from tblnotafiscal
                    inner join tblnotafiscalprodutobarra on (tblnotafiscalprodutobarra.codnotafiscal = tblnotafiscal.codnotafiscal)
                    where tblnotafiscal.emitida = true
                    --and tblnotafiscal.nfeautorizacao is not null -- Nao importa se ainda esta em digitacao
                    and tblnotafiscal.nfeinutilizacao is null
                    and tblnotafiscal.nfecancelamento is null
                    and tblnotafiscalprodutobarra.codnegocioprodutobarra is not null

                    ) emitida on (

                    emitida.codfilial = tblnegocio.codfilial
                    and emitida.codpessoa = tblnegocio.codpessoa
                    AND emitida.codnegocioprodutobarra = tblnegocioprodutobarra.codnegocioprodutobarra

                    )
            where tblnegocio.codnegociostatus = 2
            and tblnegocio.lancamento >= (now() - interval '2 months')
            and emitida.codnotafiscal is null
            and tblnegocio.codfilial <> destino.codfilial
            and tblnegocio.codnaturezaoperacao not in (19) --Uso e Consumo
            and origem.codempresa = destino.codempresa
            and origem.codfilial = :codfilial
            and origem.codfilial != 199 -- Defeito
            and destino.codfilial != 199 -- Defeito
            --limit 50

            limit 600

            ";

        $regs = DB::select($sql, [
            'codfilial' => $codfilial
        ]);


        $gerados = [];

        $nfs = [];

        foreach ($regs as $reg) {
            if (isset($gerados[$reg->codfilial][$reg->codpessoa][$reg->codnaturezaoperacao])) {

                $nf = $nfs[$gerados[$reg->codfilial][$reg->codpessoa][$reg->codnaturezaoperacao]['codnotafiscal']];
            } else {


                $nf = new NotaFiscal;
                $nf->codfilial = $reg->codfilial;
                $nf->codestoquelocal = $reg->codestoquelocal;
                $nf->modelo = NotaFiscalService::MODELO_NFE;
                $nf->codpessoa = $reg->codpessoa;
                $nf->emitida = true;
                $nf->codnaturezaoperacao = $reg->codnaturezaoperacao;
                $nf->codoperacao = $nf->NaturezaOperacao->codoperacao;
                $nf->serie = 1;
                $nf->numero = 0;
                $nf->emissao = new Carbon();
                $nf->saida = $nf->emissao;
                $nf->save();

                $gerados[$reg->codfilial][$reg->codpessoa][$reg->codnaturezaoperacao] = [
                    'itens' => 0,
                    'codnotafiscal' => 0,
                ];
            }

            $npb = NegocioProdutoBarra::findOrFail($reg->codnegocioprodutobarra);

            $nfpb = new NotaFiscalProdutoBarra;

            $nfpb->codnotafiscal = $nf->codnotafiscal;
            $nfpb->codprodutobarra = $npb->codprodutobarra;
            $nfpb->quantidade = $npb->quantidade;

            $preco = $npb->ProdutoBarra->Produto->preco;
            if (!empty($npb->ProdutoBarra->codprodutoembalagem)) {
                $preco *= $npb->ProdutoBarra->ProdutoEmbalagem->quantidade;
            }
            $nfpb->valorunitario = round($preco * 0.7, 2);

            $nfpb->valortotal = $nfpb->valorunitario * $npb->quantidade;
            $nfpb->codnegocioprodutobarra = $npb->codnegocioprodutobarra;

            NotaFiscalProdutoBarraService::calcularTributacao($nfpb);

            $nfpb->save();

            $gerados[$reg->codfilial][$reg->codpessoa][$reg->codnaturezaoperacao]['itens']++;
            $gerados[$reg->codfilial][$reg->codpessoa][$reg->codnaturezaoperacao]['codnotafiscal'] = $nf->codnotafiscal;
            $nfs[$nf->codnotafiscal] = $nf;
        }

        DB::commit();

        return $gerados;
    }

    public static function ListarNotasPorEmitir()
    {
        $sql = 'select f.codfilial, f.filial, nat.codnaturezaoperacao, nat.naturezaoperacao, count(*) as quant, sum(nf.valortotal) as valor
        from tblnotafiscal nf
        left join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
        inner join tblfilial f on (f.codfilial = nf.codfilial)
        where nf.numero = 0
        group by f.codfilial, f.filial, nat.codnaturezaoperacao, nat.naturezaoperacao
        order by f.codfilial, nat.naturezaoperacao';

        $notasemitir = DB::select(DB::raw($sql));

        return $notasemitir;
    }

    public static function ListarNotasNaoAutorizadas()
    {
        $sql = 'select f.codfilial, f.filial, nat.codnaturezaoperacao, nat.naturezaoperacao, count(*) as quant, sum(nf.valortotal) as valor
        from tblnotafiscal nf
        left join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
        inner join tblfilial f on (f.codfilial = nf.codfilial)
        where nf.emitida = true
        and nf.nfeautorizacao is null
        and nf.nfeinutilizacao is null
        and nf.nfecancelamento is null
        and nf.numero != 0
        group by f.codfilial, f.filial, nat.codnaturezaoperacao, nat.naturezaoperacao
        order by f.codfilial, nat.naturezaoperacao';

        $notasnaoautorizadas = DB::select(DB::raw($sql));

        return $notasnaoautorizadas;
    }

    public static function ListarNotasEmitidas($data)
    {
        $sql = 'select nat.codnaturezaoperacao, nat.naturezaoperacao, f.codfilial, f.filial, count(*) as quant, sum(nf.valortotal) as valor
        from tblnotafiscal nf
        left join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
        inner join tblfilial f on (f.codfilial = nf.codfilial)
        where nf.emitida = true
        and nf.nfeautorizacao is not null
        and nf.nfeinutilizacao is null
        and nf.nfecancelamento is null
        and nf.numero != 0
        and nf.saida >= :data
        group by f.codfilial, f.filial, nat.codnaturezaoperacao, nat.naturezaoperacao
        order by nat.naturezaoperacao';

        $emitidas = DB::select($sql, [
            'data' => $data
        ]);

        return $emitidas;
    }

    public static function ListarNotasLancadas($data)
    {
        $sql = 'select nat.codnaturezaoperacao, nat.naturezaoperacao, f.codfilial, f.filial, count(*) as quant, sum(nf.valortotal) as valor
        from tblnotafiscal nf
        left join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
        inner join tblfilial f on (f.codfilial = nf.codfilial)
        where nf.emitida = false
        and nf.numero != 0
        and nf.saida >= :data
        group by f.codfilial, f.filial, nat.codnaturezaoperacao, nat.naturezaoperacao
        order by nat.naturezaoperacao';

        $lancada = DB::select($sql, [
            'data' => $data
        ]);

        return $lancada;
    }

    public static function buscarNotasTransferenciaSaidaSemEntrada()
    {
        $sql = '
            select 
                nf.*
            from tblnotafiscal nf
            inner join tblfilial orig on (orig.codfilial = nf.codfilial)
            inner join tblfilial dest on (dest.codpessoa = nf.codpessoa)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
            left join tblnotafiscal nfdest on (nfdest.emitida = false and nfdest.nfechave = nf.nfechave)
            where nf.emitida = true
            and nf.nfeautorizacao is not null
            and nf.nfeinutilizacao is null
            and nf.nfecancelamento is null
            and orig.codempresa = dest.codempresa
            and orig.codempresa = 1
            and nat.transferencia = true
            and nfdest.codnotafiscal is null
        ';
        $regs = DB::select($sql);
        $nfs = NotaFiscal::hydrate($regs);
        return $nfs;
    }

    public static function gerarTransferenciaEntrada(NotaFiscal $nf)
    {
        $nfEnt = NotaFiscal::updateOrCreate([
            'nfechave' => $nf->nfechave,
            'numero' => $nf->numero,
            'serie' => $nf->serie,
            'modelo' => $nf->modelo,
            'emitida' => false,
            'codfilial' => $nf->Pessoa->FilialS[0]->codfilial,
            'codestoquelocal' => $nf->Pessoa->FilialS[0]->EstoqueLocalS[0]->codestoquelocal,
            'codpessoa' => $nf->Filial->codpessoa,
        ], [
            'codnaturezaoperacao' => $nf->NaturezaOperacao->codnaturezaoperacaodevolucao,
            'codoperacao' => $nf->codoperacao == 2 ? 1 : 2,
            'emissao' => $nf->emissao,
            'saida' => $nf->saida,
            'frete' => $nf->frete,
            'icmsbase' => $nf->icmsbase,
            'icmsvalor' => $nf->icmsvalor,
            'icmsstbase' => $nf->icmsstbase,
            'icmsstvalor' => $nf->icmsstvalor,
            'ipibase' => $nf->ipibase,
            'ipivalor' => $nf->ipivalor,
            'pesobruto' => $nf->pesobruto,
            'pesoliquido' => $nf->pesoliquido,
            'tpemis' => $nf->tpemis,
            'valordesconto' => $nf->valordesconto,
            'valorfrete' => $nf->valorfrete,
            'valoroutras' => $nf->valoroutras,
            'valorprodutos' => $nf->valorprodutos,
            'valorseguro' => $nf->valorseguro,
            'valortotal' => $nf->valortotal,
            'volumes' => $nf->volumesx,
        ]);

        foreach ($nf->NotaFiscalProdutoBarraS as $item) {
            $itemEnt = NotaFiscalProdutoBarra::updateOrCreate([
                'codnotafiscal' => $nfEnt->codnotafiscal,
                'codnotafiscalprodutobarraorigem' => $item->codnotafiscalprodutobarra
            ], [
                'codprodutobarra' => $item->codprodutobarra,
                'codnegocioprodutobarra' => $item->codnegocioprodutobarra,
                'codcfop' => $item->codcfop,
                'csosn' => $item->csosn,
                'quantidade' => $item->quantidade,
                'valorunitario' => $item->valorunitario,
                'valortotal' => $item->valortotal,
                'valordesconto' => $item->valordesconto,
                'valorfrete' => $item->valorfrete,
                'valoroutras' => $item->valoroutras,
                'valorseguro' => $item->valorseguro,
                'cofinsbase' => $item->cofinsbase,
                'cofinscst' => $item->cofinscst,
                'cofinspercentual' => $item->cofinspercentual,
                'cofinsvalor' => $item->cofinsvalor,
                'csllbase' => $item->csllbase,
                'csllpercentual' => $item->csllpercentual,
                'csllvalor' => $item->csllvalor,
                'fethabkg' => $item->fethabkg,
                'fethabvalor' => $item->fethabvalor,
                'funruralpercentual' => $item->funruralpercentual,
                'funruralvalor' => $item->funruralvalor,
                'iagrokg' => $item->iagrokg,
                'iagrovalor' => $item->iagrovalor,
                'icmsbase' => $item->icmsbase,
                'icmsbasepercentual' => $item->icmsbasepercentual,
                'icmscst' => $item->icmscst,
                'icmspercentual' => $item->icmspercentual,
                'icmsstbase' => $item->icmsstbase,
                'icmsstpercentual' => $item->icmsstpercentual,
                'icmsstvalor' => $item->icmsstvalor,
                'icmsvalor' => $item->icmsvalor,
                'ipibase' => $item->ipibase,
                'ipicst' => $item->ipicst,
                'ipipercentual' => $item->ipipercentual,
                'ipivalor' => $item->ipivalor,
                'irpjbase' => $item->irpjbase,
                'irpjpercentual' => $item->irpjpercentual,
                'irpjvalor' => $item->irpjvalor,
                'pisbase' => $item->pisbase,
                'piscst' => $item->piscst,
                'pispercentual' => $item->pispercentual,
                'pisvalor' => $item->pisvalor,
                'senarpercentual' => $item->senarpercentual,
                'senarvalor' => $item->senarvalor,
            ]);
            NotaFiscalProdutoBarraService::calcularTributacao($itemEnt, false);
            $itemEnt->save();
        }

        NfeTerceiro::where('nfechave', $nfEnt->nfechave)->whereNull('codnotafiscal')->update(['codnotafiscal' => $nfEnt->codnotafiscal]);

        return $nfEnt;
    }
}
