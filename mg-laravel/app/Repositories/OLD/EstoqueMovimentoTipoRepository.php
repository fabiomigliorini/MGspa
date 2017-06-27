<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\EstoqueMovimentoTipo;

/**
 * Description of EstoqueMovimentoTipoRepository
 * 
 * @property  Validator $validator
 * @property  EstoqueMovimentoTipo $model
 */
class EstoqueMovimentoTipoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new EstoqueMovimentoTipo();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codestoquemovimentotipo;
        }
        
        $this->validator = Validator::make($data, [
            'descricao' => [
                'max:100',
                'required',
            ],
            'sigla' => [
                'max:3',
                'required',
            ],
            'preco' => [
                'numeric',
                'required',
            ],
            'codestoquemovimentotipoorigem' => [
                'numeric',
                'nullable',
            ],
            'manual' => [
                'boolean',
                'required',
            ],
            'atualizaultimaentrada' => [
                'boolean',
                'required',
            ],
        ], [
            'descricao.max' => 'O campo "descricao" não pode conter mais que 100 caracteres!',
            'descricao.required' => 'O campo "descricao" deve ser preenchido!',
            'sigla.max' => 'O campo "sigla" não pode conter mais que 3 caracteres!',
            'sigla.required' => 'O campo "sigla" deve ser preenchido!',
            'preco.numeric' => 'O campo "preco" deve ser um número!',
            'preco.required' => 'O campo "preco" deve ser preenchido!',
            'codestoquemovimentotipoorigem.numeric' => 'O campo "codestoquemovimentotipoorigem" deve ser um número!',
            'manual.boolean' => 'O campo "manual" deve ser um verdadeiro/falso (booleano)!',
            'manual.required' => 'O campo "manual" deve ser preenchido!',
            'atualizaultimaentrada.boolean' => 'O campo "atualizaultimaentrada" deve ser um verdadeiro/falso (booleano)!',
            'atualizaultimaentrada.required' => 'O campo "atualizaultimaentrada" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->EstoqueMovimentoTipoS->count() > 0) {
            return 'Estoque Movimento Tipo sendo utilizada em "EstoqueMovimentoTipo"!';
        }
        
        if ($this->model->NaturezaOperacaoS->count() > 0) {
            return 'Estoque Movimento Tipo sendo utilizada em "NaturezaOperacao"!';
        }
        
        if ($this->model->EstoqueMovimentoS->count() > 0) {
            return 'Estoque Movimento Tipo sendo utilizada em "EstoqueMovimento"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = EstoqueMovimentoTipo::query();
        
        // Filtros
         if (!empty($filters['codestoquemovimentotipo'])) {
            $qry->where('codestoquemovimentotipo', '=', $filters['codestoquemovimentotipo']);
        }

         if (!empty($filters['descricao'])) {
            $qry->palavras('descricao', $filters['descricao']);
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

         if (!empty($filters['preco'])) {
            $qry->where('preco', '=', $filters['preco']);
        }

         if (!empty($filters['codestoquemovimentotipoorigem'])) {
            $qry->where('codestoquemovimentotipoorigem', '=', $filters['codestoquemovimentotipoorigem']);
        }

         if (!empty($filters['manual'])) {
            $qry->where('manual', '=', $filters['manual']);
        }

         if (!empty($filters['atualizaultimaentrada'])) {
            $qry->where('atualizaultimaentrada', '=', $filters['atualizaultimaentrada']);
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
            , 'recordsTotal' => EstoqueMovimentoTipo::count()
            , 'data' => $qry->get()
        ];
        
    }
    
    public function tipoDestino (EstoqueMovimentoTipo $model = null) {
        
        if (empty($model)) {
            $model = $this->model;
        }
        
        return $model->EstoqueMovimentoTipoDestinoS()->first();
        
    }
    
}
