<?php

namespace Mg\Transferencia;

use DB;

use Mg\Produto\ProdutoBarra;
use Mg\MgRepository;

class TransferenciaRepository extends MgRepository
{

    public static function produtosFaltandoSemRequisicao($codestoquelocalorigem, $codestoquelocaldestino)
    {

        $sql = "
          select
          	p.codproduto,
          	p.produto,
            pv.codprodutovariacao,
          	pv.variacao,
          	um.sigla as um,
          	p.preco,
          	coalesce(pv.referencia, p.referencia) as referencia,
          	es.saldoquantidade,
          	elpv.estoqueminimo,
          	elpv.estoquemaximo,
          	es_origem.saldoquantidade as saldoquantidade_origem
          from tblproduto p
          inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
          inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
          inner join tblestoquelocalprodutovariacao elpv_origem on (elpv_origem.codestoquelocal = :codestoquelocalorigem and elpv_origem.codprodutovariacao = pv.codprodutovariacao)
          inner join tblestoquesaldo es_origem on (es_origem.codestoquelocalprodutovariacao = elpv_origem.codestoquelocalprodutovariacao and es_origem.fiscal = false)
          inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
          left join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocal = :codestoquelocaldestino and elpv.codprodutovariacao = pv.codprodutovariacao)
          left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
          left join tbltransferenciarequisicao tr on (
            tr.codprodutovariacao = pv.codprodutovariacao
            and tr.codestoquelocalorigem = elpv_origem.codestoquelocal
            and tr.codestoquelocaldestino = elpv.codestoquelocal
            and tr.indstatus not in (40, 90)
          )
          -- Marcas Controladas
          where m.controlada = true
          -- Sem Requisicao Pendente
          and tr.codtransferenciarequisicao is null
          -- Com Saldo na Origem
          and coalesce(es_origem.saldoquantidade, 0) > 0
          -- Com saldo Inferior ao Minimo na Origem
          and coalesce(es.saldoquantidade, 0) <= coalesce(elpv.estoqueminimo, 1)
          -- Com saldo Inferior ao Maximo
          and coalesce(es.saldoquantidade, 0) < coalesce(elpv.estoquemaximo, 1)
          order by m.marca, p.produto, pv.variacao, pv.codprodutovariacao

          Limit 200
        ";

        $res = collect(DB::select($sql, [
          'codestoquelocalorigem' => $codestoquelocalorigem,
          'codestoquelocaldestino' => $codestoquelocaldestino
        ]));

        foreach ($res as $key => $item) {

          // Busca Codigos de Barras dos produtos
          $pbs = ProdutoBarra::where('codprodutovariacao', $item->codprodutovariacao)->with('ProdutoEmbalagem')->get();
          $barras = [];
          $lotetransferencia = null;
          foreach ($pbs as $pb) {
            $barras[$pb->codprodutobarra] = (object) [
              'codprodutobarra' => $pb->codprodutobarra,
              'barras' => $pb->barras,
              'sigla' => $pb->ProdutoEmbalagem->UnidadeMedida->sigla??null,
              'quantidade' => $pb->ProdutoEmbalagem->quantidade??null,
            ];
            if (!empty($pb->codprodutoembalagem)) {
              if (empty($lotetransferencia) || ($lotetransferencia >= $pb->ProdutoEmbalagem->quantidade)) {
                $lotetransferencia = $pb->ProdutoEmbalagem->quantidade;
              }
            }
          }
          $res[$key]->embalagem = $lotetransferencia;
          $res[$key]->barras = $barras;

          // Calcula Percentual do Saldo
          $max = $item->estoquemaximo ?? 1;
          $res[$key]->saldopercentual = ($item->saldoquantidade / $max) * 100;

          // Calcula quanto é pra transferir
          $lotetransferencia = $lotetransferencia??1;
          if (($lotetransferencia >= $max) || ($lotetransferencia >= $item->saldoquantidade_origem)) {
              $lotetransferencia = 1;
          }
          $lotes = ceil(ceil($max - $item->saldoquantidade) / $lotetransferencia);
          $lotesorigem = floor($item->saldoquantidade_origem / $lotetransferencia);
          if ($lotes > $lotesorigem) {
            $lotes = $lotesorigem;
          }
          $transferir = $lotes * $lotetransferencia;

          // se quantidad calculada pra transferir > saldo na origem, diminui 1 lote
          if ($transferir >= $item->saldoquantidade_origem && $item->saldoquantidade > 0) {
            $lotes--;
            $transferir = $lotes * $lotetransferencia;
          }

          $res[$key]->transferir = intval($transferir);
          $res[$key]->lotetransferencia = intval($lotetransferencia);

        }
        return $res;
    }

    public static function criarRequisicoes($requisicoes) {
        foreach ($requisicoes as $key => $req) {
            $requisicoes[$key]['indstatus'] = TransferenciaRequisicao::STATUS_PENDENTE;
        }
        $res = TransferenciaRequisicao::insert($requisicoes); // Eloquent approach
        return $res;
    }
}
