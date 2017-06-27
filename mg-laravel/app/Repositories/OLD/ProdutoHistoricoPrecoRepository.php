<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ProdutoHistoricoPreco;
use App\Models\Produto;

/**
 * Description of ProdutoHistoricoPrecoRepository
 * 
 * @property  Validator $validator
 * @property  ProdutoHistoricoPreco $model
 */
class ProdutoHistoricoPrecoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ProdutoHistoricoPreco();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codprodutohistoricopreco;
        }
        
        $this->validator = Validator::make($data, [
            'codproduto' => [
                'numeric',
                'required',
            ],
            'codprodutoembalagem' => [
                'numeric',
                'nullable',
            ],
            'precoantigo' => [
                'numeric',
                'nullable',
            ],
            'preconovo' => [
                'numeric',
                'nullable',
            ],
        ], [
            'codproduto.numeric' => 'O campo "codproduto" deve ser um número!',
            'codproduto.required' => 'O campo "codproduto" deve ser preenchido!',
            'codprodutoembalagem.numeric' => 'O campo "codprodutoembalagem" deve ser um número!',
            'precoantigo.numeric' => 'O campo "precoantigo" deve ser um número!',
            'preconovo.numeric' => 'O campo "preconovo" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ProdutoHistoricoPreco::query();
        
        if(!empty($filters['codproduto']) and !empty($filters['codproduto'])){
            $qry->whereHas('Produto', function($q) use ($filters) {
                $q->where('codproduto',  $filters['codproduto']);
            });
        }

        if(!empty($filters['produto'])){
            $qry->produto(removeAcentos ($filters['produto']));
        }

        if(!empty($filters['referencia']) and !empty($filters['referencia'])){
            $qry->whereHas('Produto', function($q) use ($filters) {
                $referencia = $filters['referencia'];
                $q->where('referencia', 'ILIKE', "%$referencia%");
            });
        }
        
        if (!empty($filters['alteracao_de'])) {
            $qry->where('criacao', '>=', $filters['alteracao_de']);
        }

        if (!empty($filters['alteracao_ate'])) {
            $qry->where('criacao', '<=', $filters['alteracao_ate']);
        }

        if(!empty($filters['codmarca']) and !empty($filters['codmarca'])) {
            $qry->whereHas('Produto', function($q) use ($filters) {
                $codmarca = $filters['codmarca'];
                $q->where('codmarca', $codmarca);
            });        
        }
        
        if(!empty($filters['codusuario']) and !empty($filters['codusuario'])){
            $qry->where('codusuariocriacao', $filters['codusuario']);
        }

         
        $count = $qry->count();
    
        /*
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
        */
        
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
            , 'recordsTotal' => ProdutoHistoricoPreco::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
