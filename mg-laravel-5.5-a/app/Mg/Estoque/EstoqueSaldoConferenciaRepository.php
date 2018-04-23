<?php
namespace Mg\Estoque;
use Mg\MgRepository;
use DB;
use Carbon\Carbon;

class EstoqueSaldoConferenciaRepository extends MgRepository
{

    public static function BuscaHistoricoConferencia(){

    }

    public static function criaConferencia(
                int $codprodutovariacao,
                int $codestoquelocal,
                bool $fiscal,
                float $quantidadeinformada,
                float $customedioinformado,
                Carbon $data,
                $observacoes,
                int $corredor,
                int $prateleira,
                int $coluna,
                int $bloco,
                Carbon $vencimento
            ) {

        // Atualiza dados da EstoqueLocalProdutoVariacao
        $es = EstoqueSaldoRepository::buscaOuCria($codprodutovariacao, $codestoquelocal, $fiscal);
        $es->EstoqueLocalProdutoVariacao->corredor = $corredor;
        $es->EstoqueLocalProdutoVariacao->prateleira = $prateleira;
        $es->EstoqueLocalProdutoVariacao->coluna = $coluna;
        $es->EstoqueLocalProdutoVariacao->bloco = $bloco;
        $es->EstoqueLocalProdutoVariacao->vencimento = $vencimento;
        $es->EstoqueLocalProdutoVariacao->save();

        // Cria novo registro de conferência
        $model = new EstoqueSaldoConferencia();
        $model->codestoquesaldo = $es->codestoquesaldo;
        $model->quantidadesistema = $es->saldoquantidade;
        $model->quantidadeinformada = $quantidadeinformada;
        $model->customediosistema = $es->customedio;
        $model->customedioinformado = $customedioinformado;
        $model->data = $data;
        $model->observacoes = $observacoes;
        $model->save();

        // Atualiza Informação da última conferência
        $model->EstoqueSaldo->ultimaconferencia = $model->criacao;
        $model->EstoqueSaldo->save();

        // Cria Registro de Movimento de Estoque
        // $mov = new EstoqueMovimento();
        // EstoqueGeraMovimentoConferencia --> Este Repositorio
        $mov = static::estoqueGeraMovimentoConferencia($model);

        return $model;
    }

    public static function estoqueGeraMovimentoConferencia($conferencia)
    {

        // Array com movimentos gerados e meses para recalcular
        $codestoquemesRecalcular = [];
        $codestoquemovimentoGerado = [];

        // calcula quantidade e valor do movimento
        $quantidade = $conferencia->quantidadeinformada - $conferencia->quantidadesistema;
        $valor = $quantidade * $conferencia->customedioinformado;

        // se não há quantidade nem valor para movimentar, retorna
        if ($quantidade == 0 && $valor == 0) {
            return;
        }

        // busca primeiro movimento vinculado à conferencia
        $mov = $conferencia->EstoqueMovimentoS->first();

        // se não existe nenhum movimento vinculado cria um novo
        if ($mov == false) {
            $mov = new EstoqueMovimento();
        }

        // descobre qual o EstoqueMes ficará vinculado ao movimento
        $mes = EstoqueMesRepository::buscaOuCria(
            $conferencia->EstoqueSaldo->EstoqueLocalProdutoVariacao->codprodutovariacao,
            $conferencia->EstoqueSaldo->EstoqueLocalProdutoVariacao->codestoquelocal,
            $conferencia->EstoqueSaldo->fiscal,
            $conferencia->data
        );

        // empilha na lista de meses para recalcular o estoquemes
        $codestoquemesRecalcular[] = $mes->codestoquemes;

        // se o estoquemes calculado é diferente daquele que estava
        // anteriormente vinculado ao movimento, empilha este também
        if (!empty($mov->codestoquemes) && $mov->codestoquemes != $mes->codestoquemes) {
            $codestoquemesRecalcular[] = $mov->codestoquemes;
        }

        // preenche os dados do movimento
        $mov->codestoquesaldoconferencia = $conferencia->codestoquesaldoconferencia;
        $mov->codestoquemes = $mes->codestoquemes;
        $mov->codestoquemovimentotipo = EstoqueMovimentoTipo::AJUSTE;
        $mov->manual = false;
        $mov->data = $conferencia->data;

        // se quantidade > 0, joga como entrada
        // senão joga na saída
        if ($quantidade >= 0) {
            $mov->entradaquantidade = $quantidade;
            $mov->saidaquantidade = null;
        } else {
            $mov->entradaquantidade = null;
            $mov->saidaquantidade = abs($quantidade);
        }

        // se valor > 0, joga como entrada
        // senão joga na saída
        if ($valor >= 0) {
            $mov->entradavalor = $valor;
            $mov->saidavalor = null;
        } else {
            $mov->entradavalor = null;
            $mov->saidavalor = abs($valor);
        }

        // salva o movimento de estoque
        $mov->save();

        // armazena estoquemovimento gerado
        $codestoquemovimentoGerado[] = $mov->codestoquemovimento;

        // Uma conferência só deve ter um Movimento
        // Caso exista mais de um movimento vinculado à conferência
        // Percorre os movimentos excedentes e apaga
        $movExcedente =
             EstoqueMovimento
             ::whereNotIn('codestoquemovimento', $codestoquemovimentoGerado)
             ->where('codestoquesaldoconferencia', $conferencia->codestoquesaldoconferencia)
             ->get();

        foreach ($movExcedente as $excluir) {

            // Guarda o código do estoquemes vinculado para recalcular
            $codestoquemesRecalcular[] = $excluir->codestoquemes;

            // se por ventura existir movimento vinculado apaga o movimento
            // antes para não ocorrer falha de Foreign Key
            foreach ($excluir->EstoqueMovimentoS as $excluirDest) {
                $excluirDest->codestoquemovimentoorigem = null;
                $excluirDest->save();
            }

            // apaga registro
            $excluir->delete();

        }

        // Recalcula custo Médio de todos Meses Afetados
        foreach($codestoquemesRecalcular as $codestoquemes) {
            EstoqueSaldoRepository::estoqueCalculaCustoMedio($codestoquemes);
        }

        return $mov;
    }

    public static function buscaListagem (int $codmarca, int $codestoquelocal, bool $fiscal, $inativo) {

        $marca = \Mg\Marca\Marca::findOrFail($codmarca);
        $estoquelocal = EstoqueLocal::findOrFail($codestoquelocal);

        $produtos = [];
        foreach ($marca->ProdutoS()->get() as $produto) {

            if ($inativo == 1) {
                $produtosVariacao = $produto->ProdutoVariacaoS()->whereNull('inativo')->get();
            } elseif ($inativo == 2) {
                $produtosVariacao = $produto->ProdutoVariacaoS()->whereNotNull('inativo')->get();
            } else {
                $produtosVariacao = $produto->ProdutoVariacaoS()->get();
            }

            foreach ($produtosVariacao as $variacao) {
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
                'codestoquesaldoconferencia' => $esc->codestoquesaldoconferencia,
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
                'estoquemaximo' => $elpv->estoquemaximo,
                'estoqueminimo' => $elpv->estoquemaximo
            ],
            'localizacao' => [
                'codestoquelocal' => $elpv->codestoquelocal,
                'estoquelocal' => $elpv->EstoqueLocal->estoquelocal,
                'corredor' => $elpv->corredor,
                'prateleira' => $elpv->prateleira,
                'coluna' => $elpv->coluna,
                'bloco' => $elpv->bloco,
                'vencimento' => $elpv->vencimento->toW3cString(),
            ],
            'saldoatual' => [
                'quantidade' => $es->saldoquantidade,
                'custo' => $es->customedio
            ],
            'conferencias' => $conferencias,

        ];

        return $res;
    }

    public static function inativar ($model, $date = null)
    {
        // 1 - excluir o registro de movimento que foi gerado a partir
        // da conferencia
        $movs = EstoqueMovimento::where('codestoquesaldoconferencia', $model->codestoquesaldoconferencia)->get();
        $codestoquemes = [];
        foreach ($movs as $mov) {
            $codestoquemes[] = $mov->codestoquemes;
            $mov->delete();
        }

        // 2 - Recalcular o Custo Medio
        foreach ($codestoquemes as $cod) {
            EstoqueSaldoRepository::estoqueCalculaCustoMedio($cod);
        }

        // 3 - Marcar registro como inativo
        parent::inativar($model, $date);

    }

}
