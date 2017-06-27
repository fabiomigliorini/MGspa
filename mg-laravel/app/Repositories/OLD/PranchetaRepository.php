<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Prancheta;
use Illuminate\Support\Facades\DB;

/**
 * Description of PranchetaRepository
 * 
 * @property  Validator $validator
 * @property  Prancheta $model
 */
class PranchetaRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Prancheta();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codprancheta;
        }
        
        $this->validator = Validator::make($data, [
            'prancheta' => [
                'max:50',
                'required',
            ],
            'observacoes' => [
                'max:200',
                'nullable',
            ],
        ], [
            'prancheta.max' => 'O campo "prancheta" não pode conter mais que 50 caracteres!',
            'prancheta.required' => 'O campo "prancheta" deve ser preenchido!',
            'observacoes.max' => 'O campo "observacoes" não pode conter mais que 200 caracteres!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->PranchetaProdutoS->count() > 0) {
            return 'Prancheta sendo utilizada em "PranchetaProdutoS"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Prancheta::query();
        
        // Filtros
         if (!empty($filters['codprancheta'])) {
            $qry->where('codprancheta', '=', $filters['codprancheta']);
        }

         if (!empty($filters['prancheta'])) {
            $qry->palavras('prancheta', $filters['prancheta']);
        }

         if (!empty($filters['alteracao'])) {
            $qry->where('alteracao', '=', $filters['alteracao']);
        }

         if (!empty($filters['criacao'])) {
            $qry->where('criacao', '=', $filters['criacao']);
        }

         if (!empty($filters['codusuarioalteracao'])) {
            $qry->where('codusuarioalteracao', '=', $filters['codusuarioalteracao']);
        }

         if (!empty($filters['codusuariocriacao'])) {
            $qry->where('codusuariocriacao', '=', $filters['codusuariocriacao']);
        }

         if (!empty($filters['observacoes'])) {
            $qry->palavras('observacoes', $filters['observacoes']);
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
            , 'recordsTotal' => Prancheta::count()
            , 'data' => $qry->get()
        ];
        
    }
    
    public function listagemProdutos($codestoquelocal = null) {
        
        $filtro_codestoquelocal = empty($codestoquelocal)?'':"and elpv.codestoquelocal = $codestoquelocal";
        
        $sql = "
            select 
                pr.codproduto, 
                pr.codmarca, 
                p.codprancheta, 
                p.prancheta, 
                pp.codpranchetaproduto, 
                pr.produto, 
                pr.preco, 
                (
                    select sum(es.saldoquantidade) as saldoquantidade
                    from tblprodutovariacao pv
                    inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao $filtro_codestoquelocal)
                    inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
                    where pv.codproduto = pp.codproduto
                ) as saldoquantidade
            from tblprancheta p
            inner join tblpranchetaproduto pp on (pp.codprancheta = p.codprancheta)
            inner join tblproduto pr on (pr.codproduto = pp.codproduto)
            where p.inativo is null
            and pp.inativo is null
            order by pr.produto
        ";
        
        $itens = DB::select($sql);
        
        $marca = collect();
        $prancheta = collect();
        $produto = collect();
        foreach ($itens as $item) {
            
            if (!isset($prancheta[$item->codprancheta])) {
                $prancheta[$item->codprancheta] = Prancheta::findOrFail($item->codprancheta);
            }
            
            if (!isset($marca[$item->codmarca])) {
                $marca[$item->codmarca] = \App\Models\Marca::findOrFail($item->codmarca);
            }
            
            $produto[$item->codproduto] = $item;
            
        }
        
        $repo_img = new ProdutoImagemRepository();
        $imagem = $repo_img->buscaPorProdutos($produto->keys());
        
        $ret = [
            'produto' => $produto,
            'marca' => $marca,
            'prancheta' => $prancheta,
            'imagem' => $imagem,
        ];
        
        return $ret;
    }
    
    public function detalhesProduto ($codpranchetaproduto, $codestoquelocal = null) {
        
        $repo_prancheta_produto = new PranchetaProdutoRepository();
        
        if (!$repo_prancheta_produto->find($codpranchetaproduto)) {
            return false;
        }
        
        $repo_prod = new ProdutoRepository();
        $detalhes = $repo_prod->detalhes($repo_prancheta_produto->model->Produto, $codestoquelocal);
        
        return $detalhes;
    }
    
}
