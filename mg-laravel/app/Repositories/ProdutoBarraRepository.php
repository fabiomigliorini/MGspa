<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ProdutoBarra;

/**
 * Description of ProdutoBarraRepository
 * 
 * @property  Validator $validator
 * @property  ProdutoBarra $model
 */
class ProdutoBarraRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ProdutoBarra();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codprodutobarra;
        }
        
        $this->validator = Validator::make($data, [
            'codproduto' => [
                'numeric',
                'required',
            ],
            'variacao' => [
                'max:100',
                'nullable',
            ],
            'barras' => [
                'max:50',
                Rule::unique('tblprodutobarra')->ignore($id, 'codprodutobarra'),

            ],
            'referencia' => [
                'max:50',
                'nullable',
            ],
            'codmarca' => [
                'numeric',
                'nullable',
            ],
            'codprodutoembalagem' => [
                'numeric',
                'nullable',
            ],
            'codprodutovariacao' => [
                'numeric',
                'required',
            ],
        ], [
            'codproduto.numeric' => 'O campo "codproduto" deve ser um número!',
            'codproduto.required' => 'O campo "codproduto" deve ser preenchido!',
            'variacao.max' => 'O campo "variacao" não pode conter mais que 100 caracteres!',
            'barras.max' => 'O campo "barras" não pode conter mais que 50 caracteres!',
            'barras.unique'        => 'Este Código de Barras já existe!',
            'referencia.max' => 'O campo "referencia" não pode conter mais que 50 caracteres!',
            'codmarca.numeric' => 'O campo "codmarca" deve ser um número!',
            'codprodutoembalagem.numeric' => 'O campo "codprodutoembalagem" deve ser um número!',
            'codprodutovariacao.numeric' => 'O campo "codprodutovariacao" deve ser um número!',
            'codprodutovariacao.required' => 'O campo "codprodutovariacao" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ValeCompraModeloProdutoBarraS->count() > 0) {
            return 'Produto Barra sendo utilizada em "ValeCompraModeloProdutoBarra"!';
        }
        
        if ($this->model->ValeCompraProdutoBarraS->count() > 0) {
            return 'Produto Barra sendo utilizada em "ValeCompraProdutoBarra"!';
        }
        
        if ($this->model->CupomfiscalprodutobarraS->count() > 0) {
            return 'Produto Barra sendo utilizada em "Cupomfiscalprodutobarra"!';
        }
        
        if ($this->model->NegocioProdutoBarraS->count() > 0) {
            return 'Produto Barra sendo utilizada em "NegocioProdutoBarra"!';
        }
        
        if ($this->model->NfeterceiroitemS->count() > 0) {
            return 'Produto Barra sendo utilizada em "Nfeterceiroitem"!';
        }
        
        if ($this->model->NotaFiscalProdutoBarraS->count() > 0) {
            return 'Produto Barra sendo utilizada em "NotaFiscalProdutoBarra"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ProdutoBarra::query();
        
        // Filtros
         if (!empty($filters['codprodutobarra'])) {
            $qry->where('codprodutobarra', '=', $filters['codprodutobarra']);
        }

         if (!empty($filters['codproduto'])) {
            $qry->where('codproduto', '=', $filters['codproduto']);
        }

         if (!empty($filters['variacao'])) {
            $qry->palavras('variacao', $filters['variacao']);
        }

         if (!empty($filters['barras'])) {
            $qry->palavras('barras', $filters['barras']);
        }

         if (!empty($filters['referencia'])) {
            $qry->palavras('referencia', $filters['referencia']);
        }

         if (!empty($filters['codmarca'])) {
            $qry->where('codmarca', '=', $filters['codmarca']);
        }

         if (!empty($filters['codprodutoembalagem'])) {
            $qry->where('codprodutoembalagem', '=', $filters['codprodutoembalagem']);
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

         if (!empty($filters['codprodutovariacao'])) {
            $qry->where('codprodutovariacao', '=', $filters['codprodutovariacao']);
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

    public function buscaPorBarras($barras)
    {
        //Procura pelo Codigo de Barras
        if ($ret = ProdutoBarra::where('barras', '=', $barras)->first()) {
            return $ret;
        }

        //Procura pelo Codigo Interno
        if (strlen($barras) == 6 && ($barras == numeroLimpo($barras))) {
            if ($ret = ProdutoBarra::where('codproduto', '=', $barras)->whereNull('codprodutoembalagem')->first()) {
                return $ret;
            }
        }

        //Procura pelo Codigo Interno * Embalagem
        if (strstr($barras, '-')) {
            $arr = explode('-', $barras);
            if (count($arr == 2)) {
                $codigo = numeroLimpo($arr[0]);
                $quantidade = numeroLimpo($arr[1]);

                if ($barras == "$codigo-$quantidade") {
                    if ($ret = ProdutoBarra::where('codproduto', $codigo)->whereHas('ProdutoEmbalagem', function($query) use ($quantidade){ 
                            $query->where('quantidade', $quantidade);
                        })->first()) {
                    return $ret;
                    }
                }
            }
        }
        
        return false;

    }
    
    public function converteQuantidade($quantidade)
    {
        if (!$this->model->codprodutoembalagem){
            return $quantidade;
        }
        
        return $quantidade * $this->model->ProdutoEmbalagem->quantidade;
    }
    
    public function calculaDigitoGtin($barras = null)
    {
        if (empty($barras)) {
            $barras = $this->model->barras;
        }
        
        //preenche com zeros a esquerda
        $codigo = "000000000000000000" . $barras;
        
        //pega 18 digitos
        $codigo = substr($codigo, -18);
        $soma = 0;

        //soma digito par *1 e impar *3
        for ($i = 1; $i<strlen($codigo); $i++)
        {
            $digito = substr($codigo, $i-1, 1);
            if ($i === 0 || !!($i && !($i%2))) {
                $multiplicador = 1;
            } else {
                $multiplicador = 3;
            }
            $soma +=  $digito * $multiplicador;
        }
        
        //subtrai da maior dezena
        $digito = (ceil($soma/10)*10) - $soma;	

        //retorna digitocalculado
        return $digito;
    }
    
    public function geraBarrasInterno()
    {
        $barras = (234000000000 + $this->model->codprodutobarra);
        $this->model->barras = $barras . $this->calculaDigitoGtin($barras . '0');
    }
    
    public function save(array $options = [])
    {
        if (is_null($this->model->barras)) {
            if (!$this->model->codprodutobarra) {
                $codprodutobarra = \DB::select("select nextval('tblprodutobarra_codprodutobarra_seq') codprodutobarra");
                $codprodutobarra = intval($codprodutobarra['0']->codprodutobarra);
                $this->model->codprodutobarra = $codprodutobarra;
            }
            $this->geraBarrasInterno();
        }
        return parent::save();
    }

    public function UnidadeMedida()
    {
        if ($this->model->codprodutoembalagem) {
            return $this->model->ProdutoEmbalagem->UnidadeMedida();
        } 
        return $this->model->Produto->UnidadeMedida();
    }
    
    public function referencia()
    {
        if ($this->model->referencia) {
            return $this->model->referencia;
        }
        if ($this->model->ProdutoVariacao->referencia) {
            return $this->model->ProdutoVariacao->referencia;
        } 
        return $this->model->Produto->referencia;
    }

    public function Marca()
    {
        if ($this->model->ProdutoVariacao->codmarca) {
            return $this->model->ProdutoVariacao->Marca();
        } 
        return $this->model->Produto->Marca();
    }

    public function Preco()
    {
        if ($this->codprodutoembalagem) {
            if (!$this->model->ProdutoEmbalagem->preco) {
                return $this->model->ProdutoEmbalagem->quantidade * $this->model->produto->preco;
            }
            return (float) $this->model->ProdutoEmbalagem->preco;
        }
        return (float) $this->model->Produto->preco;
    }    
}
