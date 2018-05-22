<?php
namespace Mg\Estoque;
use Mg\MgRepository;
use DB;
use Carbon\Carbon;

class EstoqueSaldoConferenciaRepository extends MgRepository
{

    public static function criaConferencia(
                int $codprodutovariacao,
                int $codestoquelocal,
                bool $fiscal,
                float $quantidadeinformada,
                float $customedioinformado,
                Carbon $data,
                $observacoes,
                $corredor,
                $prateleira,
                $coluna,
                $bloco,
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

    public static function buscaListagem (int $codmarca, int $codestoquelocal, bool $fiscal, int $inativo)
    {

        $marca = \Mg\Marca\Marca::findOrFail($codmarca);
        $estoquelocal = EstoqueLocal::findOrFail($codestoquelocal);

        $produtos = [];

        $qProduto = $marca->ProdutoS();
        // Filtra produtos inativos
        switch ($inativo) {
            // Inativos
            case 1:
                $qProduto = $qProduto->whereNotNull('inativo')->orderBy('produto');
                break;

            // Todos
            case 9:
                break;

            // Ativos
            default:
                $qProduto = $qProduto->whereNull('inativo')->orderBy('produto');
                break;
        }
        $prods = $qProduto->get();

        foreach ($prods as $produto) {

            $qVariacoes = $produto->ProdutoVariacaoS();
            // Filtra variacoes inativas
            switch ($inativo) {
                // Inativos
                case 1:
                    if (empty($produto->inativo)) {
                        $qVariacoes = $qVariacoes->whereNotNull('inativo');
                    }
                    break;

                // Todos
                case 9:
                    break;

                // Ativos
                default:
                    $qVariacoes = $qVariacoes->whereNull('inativo');
                    break;
            }
            $produtoVariacoes = $qVariacoes->get();

            foreach ($produtoVariacoes as $variacao) {

                $saldo = 0;
                $ultimaconferencia = null;
                foreach ($variacao->EstoqueLocalProdutoVariacaoS()->where('codestoquelocal', $codestoquelocal)->get() as $elpv) {
                    foreach ($elpv->EstoqueSaldoS()->orderBy('ultimaconferencia', 'DESC')->where('fiscal', $fiscal)->get() as $es) {
                        $saldo += (float)$es->saldoquantidade;
                        $ultimaconferencia = (!empty($es->ultimaconferencia))?$es->ultimaconferencia->toW3cString():null;
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

                $i = $produto->inativo??$variacao->inativo;
                if (!empty($i)) {
                    $i = $i->toW3CString();
                }

                $produtos[] = [
                    'imagem' => $imagem,
                    'codproduto' => $variacao->codproduto,
                    'codprodutovariacao' => $variacao->codprodutovariacao,
                    'produto' => $variacao->Produto->produto,
                    'variacao' => $variacao->variacao,
                    'saldo' => $saldo,
                    'inativo' => $i,
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
        $conferencias = [];

        if ($elpv = $pv->EstoqueLocalProdutoVariacaoS()->where('codestoquelocal', $codestoquelocal)->first()) {
            if ($es = $elpv->EstoqueSaldoS()->where('fiscal', $fiscal)->first()) {
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
                        //'vencimento' => $esc->vencimento->toW3toW3cString()
                    ];
                }
            }
        }

        $barras = [];
        foreach ($pv->ProdutoBarraS as $pb) {
            if (!empty($pb->codprodutoembalagem)) {
                $siglaunidademedida = $pb->ProdutoEmbalagem->UnidadeMedida->sigla;
                $quantidade = $pb->ProdutoEmbalagem->quantidade;
            } else {
                $siglaunidademedida = $pv->Produto->UnidadeMedida->sigla;
                $quantidade = 1;
            }
            $barras[] = [
                'barras' => $pb->barras,
                'siglaunidademedida' => $siglaunidademedida,
                'quantidade' => $quantidade,
            ];
        }

        $res = [
            'produto' => [
                'codproduto' => $pv->Produto->codproduto,
                'produto' => $pv->Produto->produto,
                'referencia' => $pv->Produto->referencia,
                'inativo' => ($pv->Produto->inativo)?$pv->Produto->inativo->toW3cString():null,
                'preco' => $pv->Produto->preco,
                'siglaunidademedida' => $pv->Produto->UnidadeMedida->sigla,
                'unidademedida' => $pv->Produto->UnidadeMedida->unidademedida,
            ],
            'variacao' => [
                'codprodutovariacao' => $pv->codprodutovariacao,
                'variacao' => $pv->variacao,
                'referencia' => $pv->referencia,
                'descontinuado' => $pv->descontinuado,
                'inativo' => ($pv->inativo)?$pv->inativo->toW3cString():null,
                'estoqueminimo' => $elpv->estoqueminimo??null,
                'estoquemaximo' => $elpv->estoquemaximo??null,
            ],
            'barras' => $barras,
            'localizacao' => [
                'codestoquelocal' => $elpv->codestoquelocal??null,
                'estoquelocal' => $elpv->EstoqueLocal->estoquelocal??null,
                'corredor' => $elpv->corredor??null,
                'prateleira' => $elpv->prateleira??null,
                'coluna' => $elpv->coluna??null,
                'bloco' => $elpv->bloco??null,
                'vencimento' => (!empty($elpv->vencimento))?$elpv->vencimento->toW3cString():null,
            ],
            'saldoatual' => [
                'ultimaconferencia' => (!empty($es->ultimaconferencia))?$es->ultimaconferencia->toW3cString():null,
                'quantidade' => $es->saldoquantidade??null,
                'custo' => $es->customedio??null
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

        // 4 - Recalcular data da ultima conferencia
        $ultimaconferencia = null;
        if ($conf = $model->EstoqueSaldo->EstoqueSaldoConferenciaS()->where('codestoquesaldoconferencia', '!=', $model->codestoquesaldoconferencia)->ativo()->orderBy('criacao', 'desc')->first()) {
          $ultimaconferencia = $conf->criacao;
        }

        $model->EstoqueSaldo->ultimaconferencia = $ultimaconferencia;
        $model->EstoqueSaldo->save();

    }

}
