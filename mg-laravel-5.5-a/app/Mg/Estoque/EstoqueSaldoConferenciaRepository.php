<?php
namespace Mg\Estoque;
use Mg\MgRepository;
use DB;
use Carbon\Carbon;

class EstoqueSaldoConferenciaRepository extends MgRepository
{

    public static function criaConferencia(
                $codprodutovariacao,
                $codestoquelocal,
                $fiscal,
                $quantidadeinformada,
                $customedioinformado,
                $data,
                $observacoes,
                $corredor,
                $prateleira,
                $coluna,
                $bloco,
                $vencimento,
                $estoquemaximo,
                $estoqueminimo
            ) {

        $es = EstoqueSaldoRepository::buscaOuCria($request['codprodutovariacao'], $request['codestoquelocal'], (boolean) $request['fiscal']);
        $es->EstoqueLocalProdutoVariacao->estoquemaximo = $request['estoquemaximo'];
        $es->EstoqueLocalProdutoVariacao->estoqueminimo = $request['estoqueminimo'];
        $es->EstoqueLocalProdutoVariacao->corredor = $request['corredor'];
        $es->EstoqueLocalProdutoVariacao->prateleira = $request['prateleira'];
        $es->EstoqueLocalProdutoVariacao->coluna = $request['coluna'];
        $es->EstoqueLocalProdutoVariacao->bloco = $request['bloco'];

        $es->EstoqueLocalProdutoVariacao->save();

        $model = new EstoqueSaldoConferencia();

        $model->codestoquesaldo = $es->codestoquesaldo;
        $model->quantidadesistema = $es->saldoquantidade;
        $model->quantidadeinformada = $request['quantidadeinformada'];
        $model->customediosistema = $es->customedio;
        $model->customedioinformado = $request['customedioinformado'];

        $model->data = new Carbon($request['data']);
        $model->save();

        $model->EstoqueSaldo->ultimaconferencia = $model->criacao;
        $model->EstoqueSaldo->save();

        return $model;
    }

    public static function buscaListagem (int $codmarca, int $codestoquelocal, bool $fiscal) {

        $marca = \Mg\Marca\Marca::findOrFail($codmarca);
        $estoquelocal = EstoqueLocal::findOrFail($codestoquelocal);

        $produtos = [];
        foreach ($marca->ProdutoS()->get() as $produto) {
            foreach ($produto->ProdutoVariacaoS()->get() as $variacao) {
                $saldo = 0;
                $ultimaconferencia = null;
                foreach ($variacao->EstoqueLocalProdutoVariacaoS()->where('codestoquelocal', $codestoquelocal)->get() as $elpv) {
                    foreach ($elpv->EstoqueSaldoS()->orderBy('ultimaconferencia', 'ASC')->where('fiscal', $fiscal)->get() as $es) {
                        $saldo += (float)$es->saldoquantidade;
                        $ultimaconferencia = $es->ultimaconferencia;
                    }
                }

                $imagem = null;
                if (!empty($variacao->codprodutoimagem)) {
                    $imagem = $variacao->ProdutoImagem->Imagem->url;
                } elseif (!empty($produto->codprodutoimagem)) {
                    $imagem = $produto->ProdutoImagem->Imagem->url;
                } elseif ($pi = $produto->ProdutoImagemS()->first()) {
                    $imagem = $pi->Imagem->url;
                }

                $produtos[] = [
                    'imagem' => $imagem,
                    'codproduto' => $variacao->codproduto,
                    'codprodutovariacao' => $variacao->codprodutovariacao,
                    'produto' => $variacao->Produto->produto,
                    'variacao' => $variacao->variacao,
                    'saldo' => $saldo,
                    'inativo' => $variacao->inativo,
                    'descontinuado' => $variacao->descontinuado,
                    'ultimaconferencia' => $ultimaconferencia
                ];
            }
        }

        $res = [
            'local' => [
                'codestoquelocal' => $estoquelocal->codestoquelocal,
                'estoquelocal' => $estoquelocal->estoquelocal
            ],
            'marca' => [
                'marca' => $marca->marca,
                'codmarca' => $marca->codmarca
            ],
            'produtos' => $produtos,
        ];

        return $res;
    }

    public static function buscaProduto($codprodutovariacao, $codestoquelocal, $fiscal)
    {

        $pv  = \Mg\Produto\ProdutoVariacao::findOrFail($codprodutovariacao);
        $elpv = $pv->EstoqueLocalProdutoVariacaoS()->where('codestoquelocal', $codestoquelocal)->first();
        $es = $elpv->EstoqueSaldoS()->where('fiscal', $fiscal)->first();
        $conferencias = [];
        foreach ($es->EstoqueSaldoConferenciaS()->orderBy('data', 'DESC')->whereNull('inativo')->get() as $esc) {
            $conferencias[] = [
                'data' => $esc->data->toW3cString(),
                'usuario' => $esc->UsuarioCriacao->usuario,
                'quantidadesistema' => $esc->quantidadesistema,
                'quantidadeinformada' => $esc->quantidadeinformada,
                'customediosistema' => $esc->customediosistema,
                'customedioinformado' => $esc->customedioinformado,
                'observacoes' => $esc->observacoes,
                'criacao' => $esc->criacao->toW3cString()
            ];
        }
        $res = [
            'produto' => [
                'codproduto' => $pv->Produto->codproduto,
                'produto' => $pv->Produto->produto,
                'referencia' => $pv->Produto->referencia,
                'inativo' => $pv->Produto->inativo,
            ],
            'variacao' => [
                'codprodutovariacao' => $pv->codprodutovariacao,
                'variacao' => $pv->variacao,
                'referencia' => $pv->referencia,
                'descontinuado' => $pv->descontinuado,
                'inativo' => $pv->inativo,
            ],
            'localizacao' => [
                'corredor' => $elpv->corredor,
                'prateleira' => $elpv->prateleira,
                'coluna' => $elpv->coluna,
                'bloco' => $elpv->bloco,
            ],
            'vencimento' => $elpv->vencimento,
            'saldoatual' => [
                'quantidade' => $es->saldoquantidade,
                'custo' => $es->customedio
            ],
            'conferencias' => $conferencias,

        ];

        return $res;
    }

}
