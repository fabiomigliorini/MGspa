<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Pais;

/**
 * Description of PaisRepository
 * 
 * @property  Validator $validator
 * @property  Pais $model
 */
class PaisRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Pais();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codpais;
        }
        
        $this->validator = Validator::make($data, [
            'pais' => [
                'size:50',
            ],
            'sigla' => [
                'size:2',
            ],
            'alteracao' => [
                'date',
            ],
            'codusuarioalteracao' => [
                'numeric',
            ],
            'criacao' => [
                'date',
            ],
            'codusuariocriacao' => [
                'numeric',
            ],
            'codigooficial' => [
                'numeric',
            ],
            'inativo' => [
                'date',
            ],
        ], [
            'pais.size' => 'O campo "pais" não pode conter mais que 50 caracteres!',
            'sigla.size' => 'O campo "sigla" não pode conter mais que 2 caracteres!',
            'alteracao.date' => 'O campo "alteracao" deve ser uma data!',
            'codusuarioalteracao.numeric' => 'O campo "codusuarioalteracao" deve ser um número!',
            'criacao.date' => 'O campo "criacao" deve ser uma data!',
            'codusuariocriacao.numeric' => 'O campo "codusuariocriacao" deve ser um número!',
            'codigooficial.numeric' => 'O campo "codigooficial" deve ser um número!',
            'inativo.date' => 'O campo "inativo" deve ser uma data!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->EstadoS->count() > 0) {
            return 'País sendo utilizada em "Estado"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Pais::query();
        
        // Filtros
         if (!empty($filters['codpais'])) {
            $qry->where('codpais', '=', $filters['codpais']);
        }

         if (!empty($filters['pais'])) {
            $qry->palavras('pais', $filters['pais']);
        }

         if (!empty($filters['sigla'])) {
            $qry->palavras('sigla', $filters['sigla']);
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

         if (!empty($filters['codigooficial'])) {
            $qry->where('codigooficial', '=', $filters['codigooficial']);
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
            , 'recordsTotal' => Pais::count()
            , 'data' => $qry->get()
        ];        
    }
    
}
