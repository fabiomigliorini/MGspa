<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Ncm;

/**
 * Description of NcmRepository
 * 
 * @property  Validator $validator
 * @property  Ncm $model
 */
class NcmRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Ncm();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codncm;
        }
        
        $this->validator = Validator::make($data, [
            'ncm' => [
                'max:10',
                'required',
            ],
            'descricao' => [
                'max:1500',
                'required',
            ],
            'codncmpai' => [
                'numeric',
                'nullable',
            ],
        ], [
            'ncm.max' => 'O campo "ncm" não pode conter mais que 10 caracteres!',
            'ncm.required' => 'O campo "ncm" deve ser preenchido!',
            'descricao.max' => 'O campo "descricao" não pode conter mais que 1500 caracteres!',
            'descricao.required' => 'O campo "descricao" deve ser preenchido!',
            'codncmpai.numeric' => 'O campo "codncmpai" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->CestS->count() > 0) {
            return 'Ncm sendo utilizada em "Cest"!';
        }
        
        if ($this->model->IbptaxS->count() > 0) {
            return 'Ncm sendo utilizada em "Ibptax"!';
        }
        
        if ($this->model->NcmS->count() > 0) {
            return 'Ncm sendo utilizada em "Ncm"!';
        }
        
        if ($this->model->ProdutoS->count() > 0) {
            return 'Ncm sendo utilizada em "Produto"!';
        }
        
        if ($this->model->RegulamentoIcmsStMtS->count() > 0) {
            return 'Ncm sendo utilizada em "RegulamentoIcmsStMt"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Ncm::query();
        
        // Filtros
         if (!empty($filters['codncm'])) {
            $qry->where('codncm', '=', $filters['codncm']);
        }

         if (!empty($filters['ncm'])) {
            $qry->palavras('ncm', $filters['ncm']);
        }

         if (!empty($filters['descricao'])) {
            $qry->palavras('descricao', $filters['descricao']);
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

         if (!empty($filters['codncmpai'])) {
            $qry->where('codncmpai', '=', $filters['codncmpai']);
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
        return $qry->get();
        
    }
    
    /**
     * 
     * @return Cest[]
     */
    public function cestsDisponiveis()
    {
        $cests = [];
        if (sizeof($this->model->CestS) > 0) {
            $cests = array_merge($cests, $this->model->CestS->toArray());
        }

        if ($this->model->Ncm) {
            $cests = array_merge($cests, $this->model->Ncm->cestsDisponiveis());
        }

        return $cests;
    }

    /**
     * 
     * @return Cest[]
     */
    public function regulamentoIcmsStMtsDisponiveis()
    {
        $regs = [];

        // pega regulamentos do registro corrente
        if (sizeof($this->model->RegulamentoIcmsStMtS) > 0){
            $regs = array_merge ($regs, $this->model->RegulamentoIcmsStMtS->toArray());
        }
        // pega regulamentos da arvore recursivamente
        if ($this->Ncm){
            $regs = array_merge ($regs, $this->model->Ncm->regulamentoIcmsStMtsDisponiveis());
        }
        return $regs;
    }
    
}
