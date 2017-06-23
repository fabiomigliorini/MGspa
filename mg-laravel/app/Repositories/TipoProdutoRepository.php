<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\TipoProduto;

/**
 * Description of TipoProdutoRepository
 * 
 * @property Validator $validator
 * @property TipoProduto $model
 */
class TipoProdutoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new TipoProduto();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $this->model->codtipoproduto;
        }
        
        $this->validator = Validator::make($data, [
            'tipoproduto' => [
                'required',
                Rule::unique('tbltipoproduto')->ignore($id, 'codtipoproduto')
            ],            
        ], [
            'tipoproduto.required' => 'O campo Tipo Produto não pode ser vazio',
            'tipoproduto.unique' => 'Esta Descrição já esta cadastrada',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ProdutoS->count() > 0) {
            return 'Tipo Produtos sendo utilizada em "Produto"!';
        }
        
        if ($this->model->TributacaoNaturezaOperacaoS->count() > 0) {
            return 'Tipo Produtos sendo utilizada em "TributacaoNaturezaOperacao"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = TipoProduto::query();
        
        // Filtros
        if (!empty($filters['codtipoproduto'])) {
            $qry->where('codtipoproduto', '=', $filters['codtipoproduto']);
        }
        
        if (!empty($filters['tipoproduto'])) {
            $qry->palavras('tipoproduto', $filters['tipoproduto']);
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
            , 'recordsTotal' => TipoProduto::count()
            , 'data' => $qry->get()
        ];
        
    }
}
