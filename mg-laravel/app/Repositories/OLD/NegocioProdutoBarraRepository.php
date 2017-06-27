<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\NegocioProdutoBarra;

/**
 * Description of NegocioProdutoBarraRepository
 * 
 * @property  Validator $validator
 * @property  NegocioProdutoBarra $model
 */
class NegocioProdutoBarraRepository extends MGRepository {
    
    public function boot() {
        $this->model = new NegocioProdutoBarra();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codnegocioprodutobarra;
        }
        
        $this->validator = Validator::make($data, [
            'codnegocio' => [
                'numeric',
                'required',
            ],
            'quantidade' => [
                'numeric',
                'required',
            ],
            'valorunitario' => [
                'numeric',
                'required',
            ],
            'valortotal' => [
                'numeric',
                'required',
            ],
            'codprodutobarra' => [
                'numeric',
                'required',
            ],
            'codnegocioprodutobarradevolucao' => [
                'numeric',
                'nullable',
            ],
        ], [
            'codnegocio.numeric' => 'O campo "codnegocio" deve ser um número!',
            'codnegocio.required' => 'O campo "codnegocio" deve ser preenchido!',
            'quantidade.numeric' => 'O campo "quantidade" deve ser um número!',
            'quantidade.required' => 'O campo "quantidade" deve ser preenchido!',
            'valorunitario.numeric' => 'O campo "valorunitario" deve ser um número!',
            'valorunitario.required' => 'O campo "valorunitario" deve ser preenchido!',
            'valortotal.numeric' => 'O campo "valortotal" deve ser um número!',
            'valortotal.required' => 'O campo "valortotal" deve ser preenchido!',
            'codprodutobarra.numeric' => 'O campo "codprodutobarra" deve ser um número!',
            'codprodutobarra.required' => 'O campo "codprodutobarra" deve ser preenchido!',
            'codnegocioprodutobarradevolucao.numeric' => 'O campo "codnegocioprodutobarradevolucao" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->NegocioProdutoBarraS->count() > 0) {
            return 'Negocio Produto Barra sendo utilizada em "NegocioProdutoBarra"!';
        }
        
        if ($this->model->CupomfiscalprodutobarraS->count() > 0) {
            return 'Negocio Produto Barra sendo utilizada em "Cupomfiscalprodutobarra"!';
        }
        
        if ($this->model->EstoqueMovimentoS->count() > 0) {
            return 'Negocio Produto Barra sendo utilizada em "EstoqueMovimento"!';
        }
        
        if ($this->model->NotaFiscalProdutoBarraS->count() > 0) {
            return 'Negocio Produto Barra sendo utilizada em "NotaFiscalProdutoBarra"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        $length = 10;
        // Query da Entidade
        $qry = NegocioProdutoBarra::query();
        
        // Filtros
        $qry = $qry->join('tblnegocio', function($join) use ($filters) {
            $join->on('tblnegocio.codnegocio', '=', 'tblnegocioprodutobarra.codnegocio');
        });
        
        if (!empty($filters['negocio_codpessoa']))
            $qry = $qry->where('tblnegocio.codpessoa', '=', $filters['negocio_codpessoa']);
        
        if (!empty($filters['negocio_codnaturezaoperacao']))
            $qry = $qry->where('tblnegocio.codnaturezaoperacao', '=', $filters['negocio_codnaturezaoperacao']);
        
        if (!empty($filters['negocio_codfilial']))
            $qry = $qry->where('tblnegocio.codfilial', '=', $filters['negocio_codfilial']);
        
        if (!empty($filters['negocio_lancamento_de']))
            $qry = $qry->where('tblnegocio.lancamento', '>=', $filters['negocio_lancamento_de']);
        
        if (!empty($filters['negocio_lancamento_ate']))
            $qry = $qry->where('tblnegocio.lancamento', '<=', $filters['negocio_lancamento_ate']);
        
        if (!empty($filters['negocio_codproduto']))
        {
            $qry = $qry->join('tblprodutobarra', function($join) use ($filters) {
                $join->on('tblprodutobarra.codprodutobarra', '=', 'tblnegocioprodutobarra.codprodutobarra');
            });
            $qry = $qry->join('tblprodutovariacao', function($join) use ($filters) {
                $join->on('tblprodutovariacao.codprodutovariacao', '=', 'tblprodutobarra.codprodutovariacao');
            });
            $qry = $qry->where('tblprodutovariacao.codproduto', '=', $filters['negocio_codproduto']);
        }

        if (!empty($filters['negocio_codprodutovariacao']))
            $qry->where('tblprodutovariacao.codprodutovariacao', '=', $filters['negocio_codprodutovariacao']);
        
        /*
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
        */
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
            , 'recordsTotal' => NegocioProdutoBarra::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
