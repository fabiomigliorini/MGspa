<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ValeCompraFormaPagamento;

/**
 * Description of ValeCompraFormaPagamentoRepository
 * 
 * @property  Validator $validator
 * @property  ValeCompraFormaPagamento $model
 */
class ValeCompraFormaPagamentoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ValeCompraFormaPagamento();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codvalecompraformapagamento;
        }
        
        $this->validator = Validator::make($data, [
            'codvalecompra' => [
                'numeric',
                'required',
            ],
            'codformapagamento' => [
                'numeric',
                'required',
            ],
            'valorpagamento' => [
                'digits',
                'numeric',
                'required',
            ],
        ], [
            'codvalecompra.numeric' => 'O campo "codvalecompra" deve ser um número!',
            'codvalecompra.required' => 'O campo "codvalecompra" deve ser preenchido!',
            'codformapagamento.numeric' => 'O campo "codformapagamento" deve ser um número!',
            'codformapagamento.required' => 'O campo "codformapagamento" deve ser preenchido!',
            'valorpagamento.digits' => 'O campo "valorpagamento" deve conter no máximo 2 dígitos!',
            'valorpagamento.numeric' => 'O campo "valorpagamento" deve ser um número!',
            'valorpagamento.required' => 'O campo "valorpagamento" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->TituloS->count() > 0) {
            return 'Vale Compra Forma Pagamento sendo utilizada em "Titulo"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ValeCompraFormaPagamento::query();
        
        // Filtros
         if (!empty($filters['codvalecompraformapagamento'])) {
            $qry->where('codvalecompraformapagamento', '=', $filters['codvalecompraformapagamento']);
        }

         if (!empty($filters['codvalecompra'])) {
            $qry->where('codvalecompra', '=', $filters['codvalecompra']);
        }

         if (!empty($filters['codformapagamento'])) {
            $qry->where('codformapagamento', '=', $filters['codformapagamento']);
        }

         if (!empty($filters['valorpagamento'])) {
            $qry->where('valorpagamento', '=', $filters['valorpagamento']);
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
    
}
