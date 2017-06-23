<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ProdutoImagem;

/**
 * Description of ProdutoImagemRepository
 * 
 * @property  Validator $validator
 * @property  ProdutoImagem $model
 */
class ProdutoImagemRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ProdutoImagem();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codprodutoimagem;
        }
        
        $this->validator = Validator::make($data, [
            'codproduto' => [
                'numeric',
                'required',
            ],
            'codimagem' => [
                'numeric',
                'required',
            ],
        ], [
            'codproduto.numeric' => 'O campo "codproduto" deve ser um número!',
            'codproduto.required' => 'O campo "codproduto" deve ser preenchido!',
            'codimagem.numeric' => 'O campo "codimagem" deve ser um número!',
            'codimagem.required' => 'O campo "codimagem" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ProdutoImagem::query();
        
        // Filtros
         if (!empty($filters['codprodutoimagem'])) {
            $qry->where('codprodutoimagem', '=', $filters['codprodutoimagem']);
        }

         if (!empty($filters['codproduto'])) {
            $qry->where('codproduto', '=', $filters['codproduto']);
        }

         if (!empty($filters['codimagem'])) {
            $qry->where('codimagem', '=', $filters['codimagem']);
        }

         if (!empty($filters['criacao'])) {
            $qry->where('criacao', '=', $filters['criacao']);
        }

         if (!empty($filters['codusuariocriacao'])) {
            $qry->where('codusuariocriacao', '=', $filters['codusuariocriacao']);
        }

         if (!empty($filters['alteracao'])) {
            $qry->where('alteracao', '=', $filters['alteracao']);
        }

         if (!empty($filters['codusuarioalteracao'])) {
            $qry->where('codusuarioalteracao', '=', $filters['codusuarioalteracao']);
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
            , 'recordsTotal' => ProdutoImagem::count()
            , 'data' => $qry->get()
        ];
        
    }
    
    public function buscaPorProdutos($codprodutos) {
        
        if (is_int($codprodutos)) {
            $codprodutos = [$codprodutos];
        }
        
        $qry = ProdutoImagem::whereIn('codproduto', $codprodutos);
        $repo_img = new ImagemRepository();
        
        $ret = collect();
        foreach ($qry->get() as $item) {
            $imagem = $item->Imagem;
            $imagem->url = $repo_img->url($item->Imagem);
            if (empty($ret[$item->codproduto])) {
                $ret[$item->codproduto] = collect();
            }
            $ret[$item->codproduto][$item->codprodutoimagem] = $imagem;
        }
        
        foreach ($codprodutos as $codproduto) {
            if (empty($ret[$codproduto])) {
                $ret[$codproduto] = collect();
            }
        }
        
        return $ret;
        
    }
    
    public function create($data = null) {
        if (empty($data['ordem'])) {
            $ordem = ProdutoImagem::where('codproduto', $data['codproduto'])->max('ordem');
            $data['ordem'] = $ordem + 1;
        }
        if (!$ret = parent::create($data)) {
            return false;
        }
        $this->defineImagemPadrao();
        return $ret;
    }
    
    public function delete($id = null) {
        if (!$ret = parent::delete($id)) {
            return false;
        }
        $this->defineImagemPadrao();
        return $ret;
    }
    
    public function activate($id = null) {
        if (!$ret = parent::activate($id)) {
            return false;
        }
        $this->defineImagemPadrao();
        return $ret;
    }
    
    public function inactivate($id = null) {
        if (!$ret = parent::inactivate($id)) {
            return false;
        }
        $this->defineImagemPadrao();
        return $ret;
    }
    
    
    public function defineImagemPadrao() {
        if (!empty($this->model->Produto->codprodutoimagem)) {
            return;
        }
        $repo_prod = new ProdutoRepository();
        $repo_prod->findOrFail($this->model->codproduto);
        return $repo_prod->alterarImagemPadrao();
    }

   public function findByCodprodutoCodimagem($codproduto, $codimagem) {
       return $this->model = ProdutoImagem::where('codproduto', $codproduto)->where('codimagem', $codimagem)->fist();
   }
    
}
