<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\FamiliaProduto;

/**
 * Description of FamiliaProdutoRepository
 * 
 * @property Validator $validator
 * @property FamiliaProduto $model
 */
class FamiliaProdutoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new FamiliaProduto();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $this->model->codfamiliaproduto;
        }
        
        $this->validator = Validator::make($data, [
            'familiaproduto' => [
                'required',
                'min:3',
                Rule::unique('tblfamiliaproduto')->ignore($id, 'codfamiliaproduto')->where(function ($query) use ($data) {
                    if(!isset($data['codsecaoproduto'])){
                        return true;
                    }
                    
                    $query->where('codsecaoproduto', $data['codsecaoproduto']);
                })
            ],            
        ], [
            'familiaproduto.required'   => 'Família de produto nao pode ser vazio!',
            'familiaproduto.min'        => 'Família de produto deve ter mais de 3 caracteres!',
            'familiaproduto.unique'     => 'Esta Família já esta cadastrada nessa seção!',            
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->GrupoProdutoS->count() > 0) {
            return 'Familia Produto sendo utilizada em Grupo Produto!';
        }
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = FamiliaProduto::query();
        
        // Filtros
        if(!empty($filters['codsecaoproduto']))
            $qry->where('codsecaoproduto', $filters['codsecaoproduto']);

        if (!empty($filters['codfamiliaproduto'])) {
            $qry->where('codfamiliaproduto', '=', $filters['codfamiliaproduto']);
        }
        
        if (!empty($filters['familiaproduto'])) {
            $qry->palavras('familiaproduto', $filters['familiaproduto']);
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
            , 'recordsTotal' => FamiliaProduto::count()
            , 'data' => $qry->get()
        ];        
    }
}
