<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ValeCompraModelo;

/**
 * Description of ValeCompraModeloRepository
 * 
 * @property  Validator $validator
 * @property  ValeCompraModelo $model
 */
class ValeCompraModeloRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ValeCompraModelo();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codvalecompramodelo;
        }
        
        $this->validator = Validator::make($data, [
            'codpessoafavorecido' => [
                'numeric',
                'required',
            ],
            'modelo' => [
                'max:50',
                'required',
            ],
            'turma' => [
                'max:30',
                'nullable',
            ],
            'observacoes' => [
                'max:200',
                'nullable',
            ],
            'totalprodutos' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'desconto' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'total' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'ano' => [
                'numeric',
                'nullable',
            ],
        ], [
            'codpessoafavorecido.numeric' => 'O campo "codpessoafavorecido" deve ser um número!',
            'codpessoafavorecido.required' => 'O campo "codpessoafavorecido" deve ser preenchido!',
            'modelo.max' => 'O campo "modelo" não pode conter mais que 50 caracteres!',
            'modelo.required' => 'O campo "modelo" deve ser preenchido!',
            'turma.max' => 'O campo "turma" não pode conter mais que 30 caracteres!',
            'observacoes.max' => 'O campo "observacoes" não pode conter mais que 200 caracteres!',
            'totalprodutos.digits' => 'O campo "totalprodutos" deve conter no máximo 2 dígitos!',
            'totalprodutos.numeric' => 'O campo "totalprodutos" deve ser um número!',
            'desconto.digits' => 'O campo "desconto" deve conter no máximo 2 dígitos!',
            'desconto.numeric' => 'O campo "desconto" deve ser um número!',
            'total.digits' => 'O campo "total" deve conter no máximo 2 dígitos!',
            'total.numeric' => 'O campo "total" deve ser um número!',
            'ano.numeric' => 'O campo "ano" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ValeCompraS->count() > 0) {
            return 'Vale Compra Modelo sendo utilizada em "ValeCompra"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ValeCompraModelo::query();
        
        // Filtros
         if (!empty($filters['codvalecompramodelo'])) {
            $qry->where('codvalecompramodelo', '=', $filters['codvalecompramodelo']);
        }

         if (!empty($filters['codpessoafavorecido'])) {
            $qry->where('codpessoafavorecido', '=', $filters['codpessoafavorecido']);
        }

         if (!empty($filters['modelo'])) {
            $qry->palavras('modelo', $filters['modelo']);
        }

         if (!empty($filters['turma'])) {
            $qry->palavras('turma', $filters['turma']);
        }

         if (!empty($filters['ano'])) {
            $qry->where('ano', '=', $filters['ano']);
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
            , 'recordsTotal' => ValeCompraModelo::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
