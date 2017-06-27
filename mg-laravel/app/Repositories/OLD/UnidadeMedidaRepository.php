<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\UnidadeMedida;

/**
 * Description of UnidadeMedidaRepository
 * 
 * @property Validator $validator
 * @property UnidadeMedida $model
 */
class UnidadeMedidaRepository extends MGRepository {
    
    public function boot() {
        $this->model = new UnidadeMedida();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codunidademedida;
        }
        
        $this->validator = Validator::make($data, [
            'unidademedida' => [
                'required',
                Rule::unique('tblunidademedida')->ignore($id, 'codunidademedida')
            ],            
            'sigla' => [
                'required',
                Rule::unique('tblunidademedida')->ignore($id, 'codunidademedida')
            ],            
            
        ], [
            'unidademedida.required' => 'O campo Descrição não pode ser vazio',
            'unidademedida.unique' => 'Esta Descrição já esta cadastrada',
            'sigla.required' => 'O campo Sigla não pode ser vazio',
            'sigla.unique' => 'Esta sigla já esta cadastrado',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->ProdutoS->count() > 0) {
            return 'Unidade de medida sendo utilizada em Produtos!';
        }
        if ($this->model->ProdutoEmbalagemS->count() > 0) {
            return 'Unidade de medida sendo utilizada em Embalagens!';
        }
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = UnidadeMedida::query();
        
        // Filtros
        if (!empty($filters['codunidademedida'])) {
            $qry->where('codunidademedida', '=', $filters['codunidademedida']);
        }
        
        if (!empty($filters['unidademedida'])) {
            $qry->palavras('unidademedida', $filters['unidademedida']);
        }
        
        if (!empty($filters['sigla'])) {
            $qry->palavras('sigla', $filters['sigla']);
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
            , 'recordsTotal' => UnidadeMedida::count()
            , 'data' => $qry->get()
        ];        
    }
    
}
