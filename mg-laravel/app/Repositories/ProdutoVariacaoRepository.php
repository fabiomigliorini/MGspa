<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ProdutoVariacao;

/**
 * Description of ProdutoVariacaoRepository
 * 
 * @property  Validator $validator
 * @property  ProdutoVariacao $model
 */
class ProdutoVariacaoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ProdutoVariacao();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codprodutovariacao;
        }
        
        $this->validator = Validator::make($data, [
            'codproduto' => [
                'numeric',
                'required',
            ],
            'variacao' => [
                'max:100',
                'nullable',
                Rule::unique('tblprodutovariacao')->ignore($id, 'codprodutovariacao')->where(function ($query) use ($data) {
                    if(!isset($data['codproduto'])){
                        return true;
                    }
                    
                    $query->where('codproduto', $data['codproduto']);
                })
            ],
            'referencia' => [
                'max:50',
                'nullable',
            ],
            'codmarca' => [
                'numeric',
                'nullable',
                "not_in:{$this->model->Produto->codmarca}"
            ],
            'codopencart' => [
                'numeric',
                'nullable',
            ],
            'dataultimacompra' => [
                'date',
                'nullable',
            ],
            'custoultimacompra' => [
                'numeric',
                'nullable',
            ],
            'quantidadeultimacompra' => [
                'numeric',
                'nullable',
            ],
        ], [
            'codproduto.numeric' => 'O campo "codproduto" deve ser um número!',
            'codproduto.required' => 'O campo "codproduto" deve ser preenchido!',
            'variacao.max' => 'O campo "variacao" não pode conter mais que 100 caracteres!',
            'variacao.unique' => 'Esta Variação já está cadastrada!',
            'variacao.required' => 'Já existe uma Variação em branco, preencha a descrição desta nova Variação!',
            'referencia.max' => 'O campo "referencia" não pode conter mais que 50 caracteres!',
            'codmarca.numeric' => 'O campo "codmarca" deve ser um número!',
            'codmarca.not_in' => 'Somente selecione a Marca caso seja diferente do produto!',
            'codopencart.numeric' => 'O campo "codopencart" deve ser um número!',
            'dataultimacompra.date' => 'O campo "dataultimacompra" deve ser uma data!',
            'custoultimacompra.numeric' => 'O campo "custoultimacompra" deve ser um número!',
            'quantidadeultimacompra.numeric' => 'O campo "quantidadeultimacompra" deve ser um número!',
        ]);
/*
        if (isset($this->codproduto) && empty($this->variacao))
            if ($this->Produto->ProdutoVariacaoS()->whereNull('variacao')->count() > 0)
                $this->_regrasValidacao['variacao'] = 'required|' . $this->_regrasValidacao['variacao'];
*/        

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->EstoqueLocalProdutoVariacaoS->count() > 0) {
            return 'Produto Variacao sendo utilizada em "EstoqueLocalProdutoVariacao"!';
        }
        
        if ($this->model->ProdutoBarraS->count() > 0) {
            return 'Produto Variacao sendo utilizada em "ProdutoBarra"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ProdutoVariacao::query();
        
        // Filtros
         if (!empty($filters['codprodutovariacao'])) {
            $qry->where('codprodutovariacao', '=', $filters['codprodutovariacao']);
        }

         if (!empty($filters['codproduto'])) {
            $qry->where('codproduto', '=', $filters['codproduto']);
        }

         if (!empty($filters['variacao'])) {
            $qry->palavras('variacao', $filters['variacao']);
        }

         if (!empty($filters['referencia'])) {
            $qry->palavras('referencia', $filters['referencia']);
        }

         if (!empty($filters['codmarca'])) {
            $qry->where('codmarca', '=', $filters['codmarca']);
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

         if (!empty($filters['codopencart'])) {
            $qry->where('codopencart', '=', $filters['codopencart']);
        }

         if (!empty($filters['dataultimacompra'])) {
            $qry->where('dataultimacompra', '=', $filters['dataultimacompra']);
        }

         if (!empty($filters['custoultimacompra'])) {
            $qry->where('custoultimacompra', '=', $filters['custoultimacompra']);
        }

         if (!empty($filters['quantidadeultimacompra'])) {
            $qry->where('quantidadeultimacompra', '=', $filters['quantidadeultimacompra']);
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
            , 'recordsTotal' => ProdutoVariacao::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
