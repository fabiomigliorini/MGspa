<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\SubGrupoProduto;

/**
 * Description of SubGrupoProdutoRepository
 * 
 * @property Validator $validator
 * @property SubGrupoProduto $model
 */
class SubGrupoProdutoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new SubGrupoProduto();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $this->model->codsubgrupoproduto;
        }
        
        $this->validator = Validator::make($data, [
            'subgrupoproduto' => [
                'required',
                'min:3',
                Rule::unique('tblsubgrupoproduto')->ignore($id, 'codsubgrupoproduto')->where(function ($query) use ($data) {
                    if(!isset($data['codgrupoproduto'])){
                        return true;
                    }                    
                    $query->where('codgrupoproduto', $data['codgrupoproduto']);
                })
            ],            
        ], [
            'subgrupoproduto.required'   => 'Sub Grupo de produto nao pode ser vazio!',
            'subgrupoproduto.min'        => 'Sub grupo de produto deve ter mais de 3 caracteres!',
            'subgrupoproduto.unique'     => 'Esta Sub Grupo já esta cadastrada nesse Grupo!',            
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->ProdutoS->count() > 0) {
            return 'Sub Grupo Produto sendo utilizada em Produto!';
        }
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = SubGrupoProduto::query();
        
        // Filtros
        if(!empty($filters['codgrupoproduto']))
            $qry->where('codgrupoproduto', $filters['codgrupoproduto']);

        if (!empty($filters['codsubgrupoproduto'])) {
            $qry->where('codsubgrupoproduto', '=', $filters['codsubgrupoproduto']);
        }
        
        if (!empty($filters['subgrupoproduto'])) {
            $qry->palavras('subgrupoproduto', $filters['subgrupoproduto']);
        }        
        
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
        
        $count = $qry->count();
        
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
            , 'recordsTotal' => SubGrupoProduto::count()
            , 'data' => $qry->get()
        ];        
    }
}
