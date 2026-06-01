<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

use Mg\MgController;
use Mg\Mercos\MercosProduto;
use Mg\Mercos\MercosProdutoService;
use Mg\Woo\WooProduto;

class ProdutoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);

        $qry = ProdutoService::pesquisar($filter, $sort, $fields)->with([
            'Marca',
            'Ncm',
            'UnidadeMedida',
            'TipoProduto',
            'Tributacao',
            'EstoqueLocal',
            'SubGrupoProduto.GrupoProduto.FamiliaProduto.SecaoProduto',
            'ProdutoVariacaoS.ProdutoBarraS',
            'ProdutoEmbalagemS',
            'ProdutoImagem.Imagem',
        ]);

        $res = $qry->paginate()->appends($request->all());
        return ProdutoResource::collection($res);
    }

    public function show(Request $request, $codproduto)
    {
        $produto = Produto::findOrFail($codproduto);
        return new ProdutoResource($produto);
    }

    public function store(Request $request)
    {
        $request->validate($this->regras($request), $this->mensagens());

        $model = ProdutoService::criar($request->all());
        return (new ProdutoResource($model))->response()->setStatusCode(201);
    }

    public function update(Request $request, $codproduto)
    {
        $model = Produto::findOrFail($codproduto);
        $request->validate($this->regras($request, $model->codproduto), $this->mensagens());

        $model = ProdutoService::atualizar($model, $request->all());
        return new ProdutoResource($model);
    }

    public function inativar(Request $request, $codproduto)
    {
        $model = Produto::findOrFail($codproduto);
        $model = ProdutoService::inativar($model);
        return new ProdutoResource($model);
    }

    public function ativar(Request $request, $codproduto)
    {
        $model = Produto::findOrFail($codproduto);
        $model = ProdutoService::ativar($model);
        return new ProdutoResource($model);
    }

    // ─────────────────────────── Abas de leitura ──────────────────────────

    public function estoque(Request $request, $codproduto)
    {
        return response()->json(ProdutoService::saldoEstoque($codproduto), 200);
    }

    public function negocios(Request $request, $codproduto)
    {
        return response()->json(ProdutoService::negocios($codproduto, $request->all()), 200);
    }

    public function notas(Request $request, $codproduto)
    {
        return response()->json(ProdutoService::notas($codproduto, $request->all()), 200);
    }

    public function compras(Request $request, $codproduto)
    {
        return response()->json(ProdutoService::compras($codproduto), 200);
    }

    // ───────────────────────── Integrações externas ───────────────────────

    public function mercos(Request $request, $codproduto)
    {
        $regs = MercosProduto::where('codproduto', $codproduto)
            ->with(['ProdutoVariacao', 'ProdutoEmbalagem'])
            ->get()
            ->map(function ($mp) {
                return [
                    'codmercosproduto' => $mp->codmercosproduto,
                    'codprodutovariacao' => $mp->codprodutovariacao,
                    'variacao' => $mp->ProdutoVariacao->variacao ?? null,
                    'codprodutoembalagem' => $mp->codprodutoembalagem,
                    'embalagem' => $mp->ProdutoEmbalagem->quantidade ?? null,
                    'produtoid' => $mp->produtoid,
                    'preco' => $mp->preco,
                    'precoatualizado' => $mp->precoatualizado,
                    'saldoquantidade' => $mp->saldoquantidade,
                    'saldoquantidadeatualizado' => $mp->saldoquantidadeatualizado,
                    'inativo' => $mp->inativo,
                ];
            });
        return response()->json($regs, 200);
    }

    public function mercosExportar(Request $request, $codproduto)
    {
        $res = MercosProdutoService::atualizaProduto($codproduto);
        return response()->json($res, 200);
    }

    public function woo(Request $request, $codproduto)
    {
        $regs = WooProduto::where('codproduto', $codproduto)
            ->with(['ProdutoVariacao'])
            ->get()
            ->map(function ($wp) {
                return [
                    'codwooproduto' => $wp->codwooproduto,
                    'codprodutovariacao' => $wp->codprodutovariacao,
                    'variacao' => $wp->ProdutoVariacao->variacao ?? null,
                    'id' => $wp->id,
                    'idvariation' => $wp->idvariation,
                    'integracao' => $wp->integracao,
                    'exportacao' => $wp->exportacao,
                    'inativo' => $wp->inativo,
                ];
            });
        return response()->json($regs, 200);
    }

    public function imagemOrdem(Request $request, $codproduto)
    {
        $request->validate(['ordem' => ['required', 'array']]);
        foreach ($request->ordem as $i => $codprodutoimagem) {
            ProdutoImagem::where('codproduto', $codproduto)
                ->where('codprodutoimagem', $codprodutoimagem)
                ->update(['ordem' => $i + 1]);
        }
        $produto = Produto::findOrFail($codproduto);
        return new ProdutoResource($produto);
    }

    private function regras(Request $request, $codproduto = null): array
    {
        $unico = Rule::unique('tblproduto', 'produto')
            ->where('codsubgrupoproduto', $request->codsubgrupoproduto);
        if ($codproduto) {
            $unico->ignore($codproduto, 'codproduto');
        }

        $regras = [
            'produto' => ['required', 'min:10', 'max:100', $unico],
            'referencia' => ['nullable', 'max:50'],
            'preco' => ['required', 'numeric', 'min:0.01'],
            'codunidademedida' => ['required', 'numeric', 'exists:tblunidademedida,codunidademedida'],
            'codsubgrupoproduto' => ['required', 'numeric', 'exists:tblsubgrupoproduto,codsubgrupoproduto'],
            'codmarca' => ['required', 'numeric', 'exists:tblmarca,codmarca'],
            'codtributacao' => ['required', 'numeric'],
            'codtipoproduto' => ['required', 'numeric', 'exists:tbltipoproduto,codtipoproduto'],
            'codncm' => ['required', 'numeric', 'exists:tblncm,codncm'],
            'codcest' => ['nullable', 'numeric'],
            'codestoquelocal' => ['nullable', 'numeric'],
            'abc' => ['required', 'in:A,B,C,D'],
        ];

        if (filter_var($request->conferenciaperiodica, FILTER_VALIDATE_BOOLEAN)) {
            $regras['estoque'] = ['accepted'];
        }

        return $regras;
    }

    private function mensagens(): array
    {
        return [
            'produto.required' => 'O campo Descrição não pode ser vazio',
            'produto.min' => 'A descrição do produto não pode ter menos de 10 caracteres',
            'produto.unique' => 'Já existe um produto com essa descrição neste subgrupo!',
            'preco.required' => 'O campo Preço não pode ser vazio',
            'preco.min' => 'O preço deve ser maior que zero',
            'codunidademedida.required' => 'O campo Unidade de medida não pode ser vazio',
            'codsubgrupoproduto.required' => 'O campo Subgrupo não pode ser vazio',
            'codmarca.required' => 'O campo Marca não pode ser vazio',
            'codtributacao.required' => 'O campo Tributação não pode ser vazio',
            'codtipoproduto.required' => 'O campo Tipo não pode ser vazio',
            'codncm.required' => 'O campo NCM não pode ser vazio',
            'codncm.exists' => 'NCM inválido',
            'abc.required' => 'O campo ABC não pode ser vazio',
            'estoque.accepted' => 'Produto com conferência periódica deve controlar o Estoque!',
        ];
    }

    public function unificaVariacoes(Request $request)
    {
        $request->validate([
            'codprodutovariacaoorigem' => 'int|required',
            'codprodutovariacaodestino' => 'int|required',
        ]);
        $pv = ProdutoVariacaoService::unificaVariacoes(
            $request->codprodutovariacaoorigem,
            $request->codprodutovariacaodestino
        );
        return new ProdutoVariacaoResource($pv);
    }

    public function unificaBarras(Request $request)
    {
        $request->validate([
            'codprodutobarraorigem' => 'int|required',
            'codprodutobarradestino' => 'int|required',
        ]);
        $pb = ProdutoBarraService::unificaBarras(
            $request->codprodutobarraorigem,
            $request->codprodutobarradestino
        );
        return new ProdutoBarraResource($pb);
    }

    public function embalagemParaUnidade(Request $request)
    {
        $request->validate([
            'codprodutoembalagem' => 'int|required',
        ]);
        $pe = ProdutoEmbalagemService::embalagemParaUnidade(
            $request->codprodutoembalagem
        );
        return new ProdutoEmbalagemResource($pe);
    }

    public function listagemPdv (Request $request)
    {
        $codprodutobarra = $request->codprodutobarra??0;
        $limite = $request->limite??10000;
        return ProdutoService::listagemPdv($codprodutobarra, $limite);
    }

    public function listagemPdvCount (Request $request)
    {
        return ProdutoService::listagemPdvCount();
    }

}
