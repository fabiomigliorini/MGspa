<?php

namespace Mg\Pedido;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

use Mg\MgService;
use Mg\Produto\ProdutoBarra;

class PedidoService extends MgService
{
    public static function validate($data)
    {

        $rules = [
            'codestoquelocal' => 'required',
            'indtipo' => [
                'required',
                Rule::in([Pedido::TIPO_VENDA, Pedido::TIPO_COMPRA, Pedido::TIPO_TRANSFERENCIA]),
            ]
        ];

        $validator = Validator::make($data, $rules);
        if (!$validator->passes()) {
            throw new ValidationException($validator);
        }

        if ($data['indtipo'] == Pedido::TIPO_TRANSFERENCIA) {
            if (empty($data['codestoquelocalorigem'])) {
                $validator->errors()->add('codestoquelocalorigem', 'Local de Estoque de Origem não informado!');
                throw new ValidationException($validator);
            }
            if (!empty($data['codgrupoeconomico'])) {
                $validator->errors()->add('codgrupoeconomico', 'Grupo Economico não deve ser informado para transferências!');
                throw new ValidationException($validator);
            }
        } else {
            if (!empty($data['codestoquelocalorigem'])) {
                $validator->errors()->add('codestoquelocalorigem', 'Local de Estoque de Origem não deve ser informado quando não for uma transferência!');
                throw new ValidationException($validator);
            }
            if (empty($data['codgrupoeconomico']) && $data['indtipo'] == Pedido::TIPO_COMPRA) {
                $validator->errors()->add('codgrupoeconomico', 'Grupo Economico deve ser informado para Compra!');
                throw new ValidationException($validator);
            }
        }
    }

    public static function insert($data)
    {
        static::validate($data);
        $model = new Pedido();
        $model->fill($data);
        $model->indstatus = Pedido::STATUS_PENDENTE;
        $model->save();
        return $model;
    }

    public static function update(Pedido $model, $data)
    {
        $model->fill($data);
        $data = $model->getAttributes();
        static::validate($data);
        $model->save();
        return $model;
    }

    public static function delete(Pedido $model)
    {
        $sql = "
          SELECT COUNT(npbpi.codnegocioprodutobarrapedidoitem) AS count
          FROM tblpedido p
          INNER JOIN tblpedidoitem pi ON (pi.codpedido = p.codpedido)
          INNER JOIN tblnegocioprodutobarrapedidoitem npbpi ON (npbpi.codpedidoitem = pi.codpedidoitem)
          WHERE p.codpedido = :codpedido
        ";
        $res = DB::select($sql, ['codpedido' => $model->codpedido]);
        if ($res[0]->count > 0) {
            $validator = Validator::make([], []);
            $validator->errors()->add('codpedido', 'Pedido já vinculado à um Negócio!');
            throw new ValidationException($validator);
        }
        $model->delete();
        return $model;
    }

    public static function produtosParaTransferir($codestoquelocalorigem, $codestoquelocaldestino)
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
          -- Marcas Controladas
          where m.controlada = true
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
                    'sigla' => $pb->ProdutoEmbalagem->UnidadeMedida->sigla ?? null,
                    'quantidade' => $pb->ProdutoEmbalagem->quantidade ?? null,
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
            $lotetransferencia = $lotetransferencia ?? 1;
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
}
