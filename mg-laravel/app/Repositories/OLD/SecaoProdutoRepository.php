<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\SecaoProduto;

/**
 * Description of SecaoProdutoRepository
 * 
 * @property Validator $validator
 * @property SecaoProduto $model
 */
class SecaoProdutoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new SecaoProduto();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $this->model->codsecaoproduto;
        }
        
        $this->validator = Validator::make($data, [
            'secaoproduto' => [
                'required',
                Rule::unique('tblsecaoproduto')->ignore($id, 'codsecaoproduto')
            ],            
        ], [
            'secaoproduto.required' => 'O campo Grupo Usuário não pode ser vazio',
            'secaoproduto.unique' => 'Esta Descrição já esta cadastrada',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->FamiliaProdutoS->count() > 0) {
            return 'Secao Produto sendo utilizada em Família Produto!';
        }
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = SecaoProduto::query();
        
        // Filtros
        if (!empty($filters['codsecaoproduto'])) {
            $qry->where('codsecaoproduto', '=', $filters['codsecaoproduto']);
        }
        
        if (!empty($filters['secaoproduto'])) {
            $qry->palavras('secaoproduto', $filters['secaoproduto']);
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
            , 'recordsTotal' => SecaoProduto::count()
            , 'data' => $qry->get()
        ];        
    }
}
