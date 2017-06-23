<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;


use App\Models\Marca;

/**
 * Description of MarcaRepository
 * 
 * @property Validator $validator
 * @property Marca $model
 */
class MarcaRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Marca();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codmarca;
        }

        //dd($data);
        $this->validator = Validator::make($data, [
            'marca' => [
                'required',
                Rule::unique('tblmarca')->ignore($id, 'codmarca')
            ],            
        ], [
            'marca.required' => 'O campo Descrição não pode ser vazio',
            'marca.unique' => 'Esta Marca já esta cadastrada',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->ProdutoS->count() > 0) {
            return 'Marca sendo utilizada em Produtos!';
        }
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Marca::query();
        
        // Filtros
        if (!empty($filters['codmarca'])) {
            $qry->where('codmarca', '=', $filters['codmarca']);
        }
        
        if (!empty($filters['marca'])) {
            $qry->palavras('marca', $filters['marca']);
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
            , 'recordsTotal' => Marca::count()
            , 'data' => $qry->get()
        ];        
    }
    
}
