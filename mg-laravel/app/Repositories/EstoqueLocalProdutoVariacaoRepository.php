<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\EstoqueLocalProdutoVariacao;

/**
 * Description of EstoqueLocalProdutoVariacaoRepository
 * 
 * @property  Validator $validator
 * @property  EstoqueLocalProdutoVariacao $model
 */
class EstoqueLocalProdutoVariacaoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new EstoqueLocalProdutoVariacao();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codestoquelocalprodutovariacao;
        }
        
        $this->validator = Validator::make($data, [
            'codestoquelocal' => [
                'numeric',
                'required',
            ],
            'corredor' => [
                'numeric',
                'nullable',
            ],
            'prateleira' => [
                'numeric',
                'nullable',
            ],
            'coluna' => [
                'numeric',
                'nullable',
            ],
            'bloco' => [
                'numeric',
                'nullable',
            ],
            'estoqueminimo' => [
                'numeric',
                'nullable',
            ],
            'estoquemaximo' => [
                'numeric',
                'nullable',
            ],
            'codprodutovariacao' => [
                'numeric',
                'required',
            ],
            'vendabimestrequantidade' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'vendabimestrevalor' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'vendasemestrequantidade' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'vendasemestrevalor' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'vendaanoquantidade' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'vendaanovalor' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'vendaultimocalculo' => [
                'date',
                'nullable',
            ],
            'vencimento' => [
                'date',
                'nullable',
            ],
            'vendadiaquantidadeprevisao' => [
                'numeric',
                'nullable',
            ],
        ], [
            'codestoquelocal.numeric' => 'O campo "codestoquelocal" deve ser um número!',
            'codestoquelocal.required' => 'O campo "codestoquelocal" deve ser preenchido!',
            'corredor.numeric' => 'O campo "corredor" deve ser um número!',
            'prateleira.numeric' => 'O campo "prateleira" deve ser um número!',
            'coluna.numeric' => 'O campo "coluna" deve ser um número!',
            'bloco.numeric' => 'O campo "bloco" deve ser um número!',
            'estoqueminimo.numeric' => 'O campo "estoqueminimo" deve ser um número!',
            'estoquemaximo.numeric' => 'O campo "estoquemaximo" deve ser um número!',
            'codprodutovariacao.numeric' => 'O campo "codprodutovariacao" deve ser um número!',
            'codprodutovariacao.required' => 'O campo "codprodutovariacao" deve ser preenchido!',
            'vendabimestrequantidade.digits' => 'O campo "vendabimestrequantidade" deve conter no máximo 3 dígitos!',
            'vendabimestrequantidade.numeric' => 'O campo "vendabimestrequantidade" deve ser um número!',
            'vendabimestrevalor.digits' => 'O campo "vendabimestrevalor" deve conter no máximo 2 dígitos!',
            'vendabimestrevalor.numeric' => 'O campo "vendabimestrevalor" deve ser um número!',
            'vendasemestrequantidade.digits' => 'O campo "vendasemestrequantidade" deve conter no máximo 3 dígitos!',
            'vendasemestrequantidade.numeric' => 'O campo "vendasemestrequantidade" deve ser um número!',
            'vendasemestrevalor.digits' => 'O campo "vendasemestrevalor" deve conter no máximo 2 dígitos!',
            'vendasemestrevalor.numeric' => 'O campo "vendasemestrevalor" deve ser um número!',
            'vendaanoquantidade.digits' => 'O campo "vendaanoquantidade" deve conter no máximo 3 dígitos!',
            'vendaanoquantidade.numeric' => 'O campo "vendaanoquantidade" deve ser um número!',
            'vendaanovalor.digits' => 'O campo "vendaanovalor" deve conter no máximo 2 dígitos!',
            'vendaanovalor.numeric' => 'O campo "vendaanovalor" deve ser um número!',
            'vendaultimocalculo.date' => 'O campo "vendaultimocalculo" deve ser uma data!',
            'vencimento.date' => 'O campo "vencimento" deve ser uma data!',
            'vendadiaquantidadeprevisao.numeric' => 'O campo "vendadiaquantidadeprevisao" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->EstoqueSaldoS->count() > 0) {
            return 'Estoque Local Produto Variacao sendo utilizada em "EstoqueSaldo"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = EstoqueLocalProdutoVariacao::query();
        
        // Filtros
         if (!empty($filters['codestoquelocalprodutovariacao'])) {
            $qry->where('codestoquelocalprodutovariacao', '=', $filters['codestoquelocalprodutovariacao']);
        }

         if (!empty($filters['codestoquelocal'])) {
            $qry->where('codestoquelocal', '=', $filters['codestoquelocal']);
        }

         if (!empty($filters['corredor'])) {
            $qry->where('corredor', '=', $filters['corredor']);
        }

         if (!empty($filters['prateleira'])) {
            $qry->where('prateleira', '=', $filters['prateleira']);
        }

         if (!empty($filters['coluna'])) {
            $qry->where('coluna', '=', $filters['coluna']);
        }

         if (!empty($filters['bloco'])) {
            $qry->where('bloco', '=', $filters['bloco']);
        }

         if (!empty($filters['alteracao'])) {
            $qry->where('alteracao', '=', $filters['alteracao']);
        }

         if (!empty($filters['codusuarioalteracao'])) {
            $qry->where('codusuarioalteracao', '=', $filters['codusuarioalteracao']);
        }

         if (!empty($filters['criacao'])) {
            $qry->where('criacao', '=', $filters['criacao']);
        }

         if (!empty($filters['codusuariocriacao'])) {
            $qry->where('codusuariocriacao', '=', $filters['codusuariocriacao']);
        }

         if (!empty($filters['estoqueminimo'])) {
            $qry->where('estoqueminimo', '=', $filters['estoqueminimo']);
        }

         if (!empty($filters['estoquemaximo'])) {
            $qry->where('estoquemaximo', '=', $filters['estoquemaximo']);
        }

         if (!empty($filters['codprodutovariacao'])) {
            $qry->where('codprodutovariacao', '=', $filters['codprodutovariacao']);
        }

         if (!empty($filters['vendabimestrequantidade'])) {
            $qry->where('vendabimestrequantidade', '=', $filters['vendabimestrequantidade']);
        }

         if (!empty($filters['vendabimestrevalor'])) {
            $qry->where('vendabimestrevalor', '=', $filters['vendabimestrevalor']);
        }

         if (!empty($filters['vendasemestrequantidade'])) {
            $qry->where('vendasemestrequantidade', '=', $filters['vendasemestrequantidade']);
        }

         if (!empty($filters['vendasemestrevalor'])) {
            $qry->where('vendasemestrevalor', '=', $filters['vendasemestrevalor']);
        }

         if (!empty($filters['vendaanoquantidade'])) {
            $qry->where('vendaanoquantidade', '=', $filters['vendaanoquantidade']);
        }

         if (!empty($filters['vendaanovalor'])) {
            $qry->where('vendaanovalor', '=', $filters['vendaanovalor']);
        }

         if (!empty($filters['vendaultimocalculo'])) {
            $qry->where('vendaultimocalculo', '=', $filters['vendaultimocalculo']);
        }

         if (!empty($filters['vencimento'])) {
            $qry->where('vencimento', '=', $filters['vencimento']);
        }

         if (!empty($filters['vendadiaquantidadeprevisao'])) {
            $qry->where('vendadiaquantidadeprevisao', '=', $filters['vendadiaquantidadeprevisao']);
        }

        
        $count = $qry->count();
    
        switch ($filters['inativo']) {
            case 2: //Inativos
                $qry = $qry->inativo();
                break;

            case 9: //Todos
                break;

            case 1: //Ativos
            default:
                $qry = $qry->ativo();
                break;
        }
        
        // Paginacao
        if (!empty($start)) {
            $qry->offset($start);
        }
        if (!empty($length)) {
            $qry->limit($length);
        }
        
        // Ordenacao
        foreach ($sort as $s) {
            $qry->orderBy($s['column'], $s['dir']);
        }
        
        // Registros
        return [
            'recordsFiltered' => $count
            , 'recordsTotal' => EstoqueLocalProdutoVariacao::count()
            , 'data' => $qry->get()
        ];
        
    }
    
    public function buscaOuCria(int $codestoquelocal, int $codprodutovariacao)
    {
        if ($this->busca($codestoquelocal, $codprodutovariacao)) {
            return $this->model;
        }
        
        if (!$this->create([
            'codestoquelocal' => $codestoquelocal,
            'codprodutovariacao' => $codprodutovariacao,
        ])) {
            return false;
        }
        
        return $this->model;
    }
    
    public function busca(int $codestoquelocal, int $codprodutovariacao) 
    {
        return $this->model = EstoqueLocalProdutoVariacao::where('codprodutovariacao', $codprodutovariacao)->where('codestoquelocal', $codestoquelocal)->first();
    }
    
}
