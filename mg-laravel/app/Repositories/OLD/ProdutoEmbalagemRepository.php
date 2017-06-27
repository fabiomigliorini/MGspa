<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ProdutoEmbalagem;

/**
 * Description of ProdutoEmbalagemRepository
 * 
 * @property  Validator $validator
 * @property  ProdutoEmbalagem $model
 */
class ProdutoEmbalagemRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ProdutoEmbalagem();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codprodutoembalagem;
        }

        Validator::extend('precomin', function ($attribute, $value, $parameters) use($data) {
            if(!empty($value)){
                if($value < ($this->model->Produto->preco * $data['quantidade'] * .5)) {
                    return false;
                }
            }
            return true;
        });         

        Validator::extend('precomax', function ($attribute, $value, $parameters) use($data) {
            if(!empty($value)){
                if($value > ($this->model->Produto->preco * $data['quantidade'])) {
                    return false;
                }
            }
            return true;
        });         
        
        
        $this->validator = Validator::make($data, [
//            'codproduto' => [
//                'numeric',
//                'required',
//            ],
            'codunidademedida' => [
                'numeric',
                'required',
            ],
            'quantidade' => [
                'numeric',
                'required',
                Rule::unique('tblprodutoembalagem')->ignore($id, 'codprodutoembalagem')->where(function ($query) use ($data) {
                    if(!isset($data['codproduto'])){
                        return true;
                    }
                    
                    $query->where('codproduto', $data['codproduto']);
                })
                
            ],
            'preco' => [
                'precomin',
                'precomax',
                
            ],
        ], [      
            
            'codunidademedida.required'         => 'O campo "Unidade de medida" deve ser preenchido!',
            'codunidademedida.numeric'          => 'O campo "codunidademedida" deve ser um número!',
            
            'quantidade.required'               => 'O campo "Quantidade" deve ser preenchido!',
            'quantidade.numeric'                => 'O campo "Quantidade" deve ser um número!',
            'quantidade.unique'                 => 'Já existe uma embalagem cadastrada com esta mesma quantidade!',
            
            'preco.precomax'                    => 'Preço maior que o custo unitário!',
            'preco.precomin'                    => 'Preço inferior à 50% do custo unitário!',
            'preco.numeric'                     => 'O campo "preco" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ProdutoBarraS->count() > 0) {
            return 'Produto Embalagem sendo utilizada em "ProdutoBarra"!';
        }
        
        if ($this->model->ProdutoHistoricoPrecoS->count() > 0) {
            return 'Produto Embalagem sendo utilizada em "ProdutoHistoricoPreco"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ProdutoEmbalagem::query();
        
        // Filtros
         if (!empty($filters['codprodutoembalagem'])) {
            $qry->where('codprodutoembalagem', '=', $filters['codprodutoembalagem']);
        }

         if (!empty($filters['codproduto'])) {
            $qry->where('codproduto', '=', $filters['codproduto']);
        }

         if (!empty($filters['codunidademedida'])) {
            $qry->where('codunidademedida', '=', $filters['codunidademedida']);
        }

         if (!empty($filters['quantidade'])) {
            $qry->where('quantidade', '=', $filters['quantidade']);
        }

         if (!empty($filters['preco'])) {
            $qry->where('preco', '=', $filters['preco']);
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
            , 'recordsTotal' => ProdutoEmbalagem::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
