<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\GrupoProduto;

/**
 * Description of GrupoProdutoRepository
 * 
 * @property Validator $validator
 * @property GrupoProduto $model
 */
class GrupoProdutoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new GrupoProduto();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $this->model->codgrupoproduto;
        }

        //dd($data);
        
        $this->validator = Validator::make($data, [
            'grupoproduto' => [
                'required',
                'min:3',
                Rule::unique('tblgrupoproduto')->ignore($id, 'codgrupoproduto')->where(function ($query) use ($data) {
                    $query->where('codfamiliaproduto', $data['codfamiliaproduto']);
                })
            ],            
        ], [
            'grupoproduto.required'   => 'Grupo de produto nao pode ser vazio!',
            'grupoproduto.min'        => 'Grupo de produto deve ter mais de 3 caracteres!',
            'grupoproduto.unique'     => 'Este Grupo já esta cadastrada nessa Família!',            
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->SubGrupoProdutoS->count() > 0) {
            return 'Grupo Produto sendo utilizada em Família Produto!';
        }
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = GrupoProduto::query();
        
        // Filtros
        if(!empty($filters['codfamiliaproduto']))
            $qry->where('codfamiliaproduto', $filters['codfamiliaproduto']);

        if (!empty($filters['codgrupoproduto'])) {
            $qry->where('codgrupoproduto', '=', $filters['codgrupoproduto']);
        }
        
        if (!empty($filters['grupoproduto'])) {
            $qry->palavras('grupoproduto', $filters['grupoproduto']);
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
            , 'recordsTotal' => GrupoProduto::count()
            , 'data' => $qry->get()
        ];        
    }
}
