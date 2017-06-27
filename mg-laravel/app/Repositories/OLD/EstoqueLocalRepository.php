<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\EstoqueLocal;

/**
 * Description of EstoqueLocalRepository
 * 
 * @property  Validator $validator
 * @property  EstoqueLocal $model
 */
class EstoqueLocalRepository extends MGRepository {
    
    public function boot() {
        $this->model = new EstoqueLocal();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codestoquelocal;
        }
        
        $this->validator = Validator::make($data, [
            'estoquelocal' => [
                'max:50',
                'required',
            ],
            'codfilial' => [
                'numeric',
                'required',
            ],
            'sigla' => [
                'max:3',
                'required',
            ],
        ], [
            'estoquelocal.max' => 'O campo "estoquelocal" não pode conter mais que 50 caracteres!',
            'estoquelocal.required' => 'O campo "estoquelocal" deve ser preenchido!',
            'codfilial.numeric' => 'O campo "codfilial" deve ser um número!',
            'codfilial.required' => 'O campo "codfilial" deve ser preenchido!',
            'sigla.max' => 'O campo "sigla" não pode conter mais que 3 caracteres!',
            'sigla.required' => 'O campo "sigla" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->EstoqueLocalProdutoVariacaoS->count() > 0) {
            return 'Local de Estoque sendo utilizada em "EstoqueLocalProdutoVariacao"!';
        }
        
        if ($this->model->NegocioS->count() > 0) {
            return 'Local de Estoque sendo utilizada em "Negocio"!';
        }
        
        if ($this->model->NotaFiscalS->count() > 0) {
            return 'Local de Estoque sendo utilizada em "NotaFiscal"!';
            return 'Estoque Local sendo utilizada em "EstoqueLocalProdutoVariacao"!';
        }
        
        if ($this->model->NegocioS->count() > 0) {
            return 'Estoque Local sendo utilizada em "Negocio"!';
        }
        
        if ($this->model->NotaFiscalS->count() > 0) {
            return 'Estoque Local sendo utilizada em "NotaFiscal"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = EstoqueLocal::query();
        
        // Filtros
         if (!empty($filters['codestoquelocal'])) {
            $qry->where('codestoquelocal', '=', $filters['codestoquelocal']);
        }

         if (!empty($filters['estoquelocal'])) {
            $qry->palavras('estoquelocal', $filters['estoquelocal']);
        }

         if (!empty($filters['codfilial'])) {
            $qry->where('codfilial', '=', $filters['codfilial']);
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

         if (!empty($filters['sigla'])) {
            $qry->palavras('sigla', $filters['sigla']);
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
            , 'recordsTotal' => EstoqueLocal::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
