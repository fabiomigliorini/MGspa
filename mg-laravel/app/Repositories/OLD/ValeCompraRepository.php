<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ValeCompra;

/**
 * Description of ValeCompraRepository
 * 
 * @property  Validator $validator
 * @property  ValeCompra $model
 */
class ValeCompraRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ValeCompra();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codvalecompra;
        }
        
        $this->validator = Validator::make($data, [
            'codvalecompramodelo' => [
                'numeric',
                'required',
            ],
            'codpessoafavorecido' => [
                'numeric',
                'required',
            ],
            'codpessoa' => [
                'numeric',
                'required',
            ],
            'observacoes' => [
                'size:200',
            ],
            'aluno' => [
                'size:50',
                'required',
            ],
            'turma' => [
                'size:30',
            ],
            'totalprodutos' => [
                'numeric',
            ],
            'desconto' => [
                'numeric',
            ],
            'total' => [
                'numeric',
            ],
            'codtitulo' => [
                'numeric',
            ],
            'inativo' => [
                'date',
            ],
            'criacao' => [
                'date',
            ],
            'codusuariocriacao' => [
                'numeric',
            ],
            'alteracao' => [
                'date',
            ],
            'codusuarioalteracao' => [
                'numeric',
            ],
            'codfilial' => [
                'numeric',
                'required',
            ],
        ], [
            'codvalecompramodelo.numeric' => 'O campo "codvalecompramodelo" deve ser um número!',
            'codvalecompramodelo.required' => 'O campo "codvalecompramodelo" deve ser preenchido!',
            'codpessoafavorecido.numeric' => 'O campo "codpessoafavorecido" deve ser um número!',
            'codpessoafavorecido.required' => 'O campo "codpessoafavorecido" deve ser preenchido!',
            'codpessoa.numeric' => 'O campo "codpessoa" deve ser um número!',
            'codpessoa.required' => 'O campo "codpessoa" deve ser preenchido!',
            'observacoes.size' => 'O campo "observacoes" não pode conter mais que 200 caracteres!',
            'aluno.size' => 'O campo "aluno" não pode conter mais que 50 caracteres!',
            'aluno.required' => 'O campo "aluno" deve ser preenchido!',
            'turma.size' => 'O campo "turma" não pode conter mais que 30 caracteres!',
            'totalprodutos.digits' => 'O campo "totalprodutos" deve conter no máximo 2 dígitos!',
            'totalprodutos.numeric' => 'O campo "totalprodutos" deve ser um número!',
            'desconto.digits' => 'O campo "desconto" deve conter no máximo 2 dígitos!',
            'desconto.numeric' => 'O campo "desconto" deve ser um número!',
            'total.digits' => 'O campo "total" deve conter no máximo 2 dígitos!',
            'total.numeric' => 'O campo "total" deve ser um número!',
            'codtitulo.numeric' => 'O campo "codtitulo" deve ser um número!',
            'inativo.date' => 'O campo "inativo" deve ser uma data!',
            'criacao.date' => 'O campo "criacao" deve ser uma data!',
            'codusuariocriacao.numeric' => 'O campo "codusuariocriacao" deve ser um número!',
            'alteracao.date' => 'O campo "alteracao" deve ser uma data!',
            'codusuarioalteracao.numeric' => 'O campo "codusuarioalteracao" deve ser um número!',
            'codfilial.numeric' => 'O campo "codfilial" deve ser um número!',
            'codfilial.required' => 'O campo "codfilial" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ValeCompraFormaPagamentoS->count() > 0) {
            return 'Vale Compras sendo utilizada em "ValeCompraFormaPagamento"!';
        }
        
        if ($this->model->ValeCompraProdutoBarraS->count() > 0) {
            return 'Vale Compras sendo utilizada em "ValeCompraProdutoBarra"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        //dd($filters);
        // Query da Entidade
        $qry = ValeCompra::query();
        
        // Filtros
         if (!empty($filters['codvalecompra'])) {
            $qry->where('codvalecompra', '=', $filters['codvalecompra']);
        }

        if (!empty($filters['codvalecompramodelo'])) {
            $qry->where('codvalecompramodelo', '=', $filters['codvalecompramodelo']);
        }

        if (!empty($filters['codpessoafavorecido'])) {
            $qry->where('codpessoafavorecido', '=', $filters['codpessoafavorecido']);
        }

        if (!empty($filters['codpessoa'])) {
            $qry->where('codpessoa', '=', $filters['codpessoa']);
        }

        if (!empty($filters['aluno'])) {
            $qry->palavras('aluno', $filters['aluno']);
        }
        
        if (!empty($filters['criacao_de'])) {
            $qry->where('criacao', '>=', $filters['criacao_de']);
        }

        if (!empty($filters['criacao_ate'])) {
            $qry->where('criacao', '<=', $filters['criacao_ate']);
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
            , 'recordsTotal' => ValeCompra::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
