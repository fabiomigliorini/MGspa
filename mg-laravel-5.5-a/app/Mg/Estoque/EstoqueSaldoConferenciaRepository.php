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
        $model->save();

        // Atualiza Informação da última conferência
        $model->EstoqueSaldo->ultimaconferencia = $model->criacao;
        $model->EstoqueSaldo->save();

        // Cria Registro de Movimento de Estoque
        // $mov = new EstoqueMovimento();
        // EstoqueGeraMovimentoConferencia --> Este Repositorio
        $mov = static::estoqueGeraMovimentoConferencia($model);

        // Disparar o Recalculo do Custo Medio
        // é uma JOB no MG Lara
        // EstoqueCalculaCustoMedio --> Repositorio de EstoqueSaldo
        EstoqueSaldoRepository::estoqueCalculaCustoMedio($mov->codestoquemes);

        return $model;
    }

    public static function estoqueGeraMovimentoConferencia($conferencia) {

        $codestoquemesRecalcular = [];
        $codestoquemovimentoGerado = [];

        $quantidade = $conferencia->quantidadeinformada - $conferencia->quantidadesistema;
        $valor = $quantidade * $conferencia->customedioinformado;

        if ($quantidade == 0 && $valor == 0) {
            return;
        }

        $mov = $conferencia->EstoqueMovimentoS->first();

        if ($mov == false) {
            $mov = new EstoqueMovimento();
        }

        $mes = EstoqueMesRepository::buscaOuCria(
            $conferencia->EstoqueSaldo->EstoqueLocalProdutoVariacao->codprodutovariacao,
            $conferencia->EstoqueSaldo->EstoqueLocalProdutoVariacao->codestoquelocal,
            $conferencia->EstoqueSaldo->fiscal,
            $conferencia->data
        );

        $codestoquemesRecalcular[] = $mes->codestoquemes;

        if (!empty($mov->codestoquemes) && $mov->codestoquemes != $mes->codestoquemes)
            $codestoquemesRecalcular[] = $mov->codestoquemes;

        $mov->codestoquemes = $mes->codestoquemes;
        $mov->codestoquemovimentotipo = EstoqueMovimentoTipo::AJUSTE;
        $mov->manual = false;
        $mov->data = $conferencia->data;

        if ($quantidade >= 0) {
            $mov->entradaquantidade = $quantidade;
            $mov->saidaquantidade = null;
        } else {
            $mov->entradaquantidade = null;
            $mov->saidaquantidade = abs($quantidade);
        }

        if ($valor >= 0) {
            $mov->entradavalor = $valor;
            $mov->saidavalor = null;
        } else {
            $mov->entradavalor = null;
            $mov->saidavalor = abs($valor);
        }

        $mov->codestoquesaldoconferencia = $conferencia->codestoquesaldoconferencia;

        $mov->save();

        //armazena estoquemovimento gerado
        $codestoquemovimentoGerado[] = $mov->codestoquemovimento;

        //Apaga estoquemovimento excedente que existir anexado ao negocioprodutobarra
        // $movExcedente =
        //         EstoqueMovimento
        //         ::whereNotIn('codestoquemovimento', $codestoquemovimentoGerado)
        //         ->where('codestoquesaldoconferencia', $conferencia->codestoquesaldoconferencia)
        //         ->get();
        // foreach ($movExcedente as $mov)
        // {
        //     $codestoquemesRecalcular[] = $mov->codestoquemes;
        //     foreach ($mov->EstoqueMovimentoS as $movDest)
        //     {
        //         $movDest->codestoquemovimentoorigem = null;
        //         $movDest->save();
        //     }
        //     $mov->delete();
        // }

        //Coloca Recalculo Custo Medio na Fila
        // foreach($codestoquemesRecalcular as $codestoquemes) {
        //     $this->dispatch((new EstoqueCalculaCustoMedio($codestoquemes))->onQueue('urgent'));
        // }
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

    public static function inativar ($model, $date = null)
    {
        // 1 - excluir o registro de movimento que foi gerado a partir
        // da conferencia
        $mov = EstoqueMovimento::findOrFail($model->codestoquesaldoconferencia);
        $codestoquemes = $mov->codestoquemes;
        $mov->delete();

        // 2 - Recalcular o Custo Medio
        EstoqueSaldoRepository::estoqueCalculaCustoMedio($codestoquemes);

        // 3 - Marcar registro como inativo
        parent::inativar($model, $date);

    }

}
