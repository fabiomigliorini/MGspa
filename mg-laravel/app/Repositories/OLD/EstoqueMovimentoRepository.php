<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\EstoqueMovimento;
use App\Models\EstoqueMovimentoTipo;

use App\Models\EstoqueSaldoConferencia;

/**
 * Description of EstoqueMovimentoRepository
 * 
 * @property  Validator $validator
 * @property  EstoqueMovimento $model
 * @property  EstoqueMesRepository $repoMes
 */
class EstoqueMovimentoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new EstoqueMovimento();
        $this->repoMes = new EstoqueMesRepository();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codestoquemovimento;
        }
        
        $rules = [
            'transferencia' => [
                'boolean',
                'required',
            ],
            'entradaquantidade' => [
                'numeric',
                'nullable',
            ],
            'entradavalor' => [
                'numeric',
                'nullable',
            ],
            'saidaquantidade' => [
                'numeric',
                'nullable',
            ],
            'saidavalor' => [
                'numeric',
                'nullable',
            ],
            'codnegocioprodutobarra' => [
                'numeric',
                'nullable',
            ],
            'codnotafiscalprodutobarra' => [
                'numeric',
                'nullable',
            ],
            'data' => [
                'date',
                'required',
            ],
            'codestoquemovimentoorigem' => [
                'numeric',
                'nullable',
            ],
            'observacoes' => [
                'max:200',
                'nullable',
            ],
            'codestoquesaldoconferencia' => [
                'numeric',
                'nullable',
            ],
        ];
        
        if (empty($data['codestoquemes'])) {
            $rules['codproduto'] = [
                'numeric',
                'required',
            ];
            $rules['codprodutovariacao'] = [
                'numeric',
                'required',
            ];
            $rules['codestoquelocal'] = [
                'numeric',
                'required',
            ];
            $rules['fiscal'] = [
                'numeric',
                'required',
            ];
        }
        
        if (!$data['transferencia']) {
            $rules['codestoquemovimentotipo'] = [
                'numeric',
                'required',
            ];
        }
        
        $this->validator = Validator::make($data, $rules, [
            'codestoquemovimentotipo.numeric' => 'O campo "codestoquemovimentotipo" deve ser um número!',
            'codestoquemovimentotipo.required' => 'O campo "codestoquemovimentotipo" deve ser preenchido!',
            'entradaquantidade.numeric' => 'O campo "entradaquantidade" deve ser um número!',
            'entradavalor.numeric' => 'O campo "entradavalor" deve ser um número!',
            'saidaquantidade.numeric' => 'O campo "saidaquantidade" deve ser um número!',
            'saidavalor.numeric' => 'O campo "saidavalor" deve ser um número!',
            'codnegocioprodutobarra.numeric' => 'O campo "codnegocioprodutobarra" deve ser um número!',
            'codnotafiscalprodutobarra.numeric' => 'O campo "codnotafiscalprodutobarra" deve ser um número!',
            'codestoquemes.numeric' => 'O campo "codestoquemes" deve ser um número!',
            'codestoquemes.required' => 'O campo "codestoquemes" deve ser preenchido!',
            'data.date' => 'O campo "data" deve ser uma data!',
            'data.required' => 'O campo "data" deve ser preenchido!',
            'codestoquemovimentoorigem.numeric' => 'O campo "codestoquemovimentoorigem" deve ser um número!',
            'observacoes.max' => 'O campo "observacoes" não pode conter mais que 200 caracteres!',
            'codestoquesaldoconferencia.numeric' => 'O campo "codestoquesaldoconferencia" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->EstoqueMovimentoS->count() > 0) {
            return 'Estoque Movimento sendo utilizada em "EstoqueMovimento"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = EstoqueMovimento::query();
        
        // Filtros
         if (!empty($filters['codestoquemovimento'])) {
            $qry->where('codestoquemovimento', '=', $filters['codestoquemovimento']);
        }

         if (!empty($filters['codestoquemovimentotipo'])) {
            $qry->where('codestoquemovimentotipo', '=', $filters['codestoquemovimentotipo']);
        }

         if (!empty($filters['entradaquantidade'])) {
            $qry->where('entradaquantidade', '=', $filters['entradaquantidade']);
        }

         if (!empty($filters['entradavalor'])) {
            $qry->where('entradavalor', '=', $filters['entradavalor']);
        }

         if (!empty($filters['saidaquantidade'])) {
            $qry->where('saidaquantidade', '=', $filters['saidaquantidade']);
        }

         if (!empty($filters['saidavalor'])) {
            $qry->where('saidavalor', '=', $filters['saidavalor']);
        }

         if (!empty($filters['codnegocioprodutobarra'])) {
            $qry->where('codnegocioprodutobarra', '=', $filters['codnegocioprodutobarra']);
        }

         if (!empty($filters['codnotafiscalprodutobarra'])) {
            $qry->where('codnotafiscalprodutobarra', '=', $filters['codnotafiscalprodutobarra']);
        }

         if (!empty($filters['codestoquemes'])) {
            $qry->where('codestoquemes', '=', $filters['codestoquemes']);
        }

         if (!empty($filters['manual'])) {
            $qry->where('manual', '=', $filters['manual']);
        }

         if (!empty($filters['data'])) {
            $qry->where('data', '=', $filters['data']);
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

         if (!empty($filters['codestoquemovimentoorigem'])) {
            $qry->where('codestoquemovimentoorigem', '=', $filters['codestoquemovimentoorigem']);
        }

         if (!empty($filters['observacoes'])) {
            $qry->palavras('observacoes', $filters['observacoes']);
        }

         if (!empty($filters['codestoquesaldoconferencia'])) {
            $qry->where('codestoquesaldoconferencia', '=', $filters['codestoquesaldoconferencia']);
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
            , 'recordsTotal' => EstoqueMovimento::count()
            , 'data' => $qry->get()
        ];
        
    }
    
    public function movimentoDestino($model = null) {
        if (empty($model)) {
            $model = $this->model;
        }
        return $model->EstoqueMovimentoDestinoS()->first();
    }
    
    public function fill($data) {
        if (! $data['data'] instanceof Carbon) {
            $data['data'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['data']);
        }
        
        parent::fill($data);
        
        if (empty($data['codprodutovariacao'])) {
            $data['codestoquelocal'] = $this->model->EstoqueMes->EstoqueSaldo->EstoqueLocalProdutoVariacao->codestoquelocal;
            $data['codprodutovariacao'] = $this->model->EstoqueMes->EstoqueSaldo->EstoqueLocalProdutoVariacao->codprodutovariacao;
            $data['fiscal'] = $this->model->EstoqueMes->EstoqueSaldo->fiscal;
        }
        $mes = $this->repoMes->buscaOuCriaPelaChave($data['codestoquelocal'], $data['codprodutovariacao'], $data['fiscal'], $data['data']);
        $this->model->codestoquemes = $mes->codestoquemes;
    }
    
    public function movimentaEstoqueSaldoConferencia (EstoqueSaldoConferencia $conf) 
    {
        $repoSaldo = new EstoqueSaldoRepository();
        $repoSaldo->atualizaUltimaConferencia($conf->EstoqueSaldo);

        $codestoquemovimentoGerado = [];
        
        $repo = new EstoqueMovimentoRepository();
        
        if (empty($conf->inativo)) {

            if ($mov = $conf->EstoqueMovimentoS->first()) {
                $repo->model = $mov;
            } else {
                $repo->new();
            }
            
            $mes = $this->repoMes->buscaOuCria($conf->EstoqueSaldo, $conf->data);

            $repo->model->codestoquemes = $mes->codestoquemes;
            $repo->model->codestoquemovimentotipo = EstoqueMovimentoTipo::AJUSTE;
            $repo->model->manual = false;
            $repo->model->data = $conf->data;

            $quantidade = $conf->quantidadeinformada - $conf->quantidadesistema;
            $valor = $quantidade * $conf->customedioinformado;

            if ($quantidade >= 0) {
                $repo->model->entradaquantidade = $quantidade;
                $repo->model->saidaquantidade = null;
                $repo->model->entradavalor = $valor;
                $repo->model->saidavalor = null;
            } else {
                $repo->model->entradaquantidade = null;
                $repo->model->saidaquantidade = abs($quantidade);
                $repo->model->entradavalor = null;
                $repo->model->saidavalor = abs($valor);
            }

            $repo->model->codestoquesaldoconferencia = $conf->codestoquesaldoconferencia;

            $repo->save();

            //armazena estoquemovimento gerado
            $codestoquemovimentoGerado[] = $repo->model->codestoquemovimento;
        }

        //Apaga estoquemovimento excedente que existir anexado ao negocioprodutobarra
        $movExcedente = 
                EstoqueMovimento
                ::whereNotIn('codestoquemovimento', $codestoquemovimentoGerado)
                ->where('codestoquesaldoconferencia', $conf->codestoquesaldoconferencia)
                ->get();
        foreach ($movExcedente as $mov) {
            foreach ($mov->EstoqueMovimentoDestinoS()->get() as $movDest) {
                $repo->update($movDest->codestoquemovimento, [
                    codestoquemovimentoorigem => null
                ]);
            }
            $repo->model = $mov;
            $repo->delete();
        }
        
        return true;
       
    }
    
    private function geraMovimentoDestino($data = null) {
        if (empty($data)) {
            $data = $this->data;
        }
        
        // se nao e transferencia
        if (!$data['transferencia']) {
            return true;
        }
        
        // Se nao tem tipo de destino
        $repoTipo = new EstoqueMovimentoTipoRepository();
        if (!$tipo = $repoTipo->tipoDestino($this->model->EstoqueMovimentoTipo)) {
            return true;
        }

        // Procura se ja existe movimento de destino
        $repoDest = new EstoqueMovimentoRepository();
        $movs = [];
        if ($mov = $this->movimentoDestino()) {
            $repoDest->model = $mov;
        } else {
            $mov = $repoDest->new();
        }
        
        // inverte valores
        $data['codestoquelocal'] = $data['codestoquelocaldestino'];
        $data['codprodutovariacao'] = $data['codprodutovariacaodestino'];
        $data['fiscal'] = $data['codestoquelocaldestino'];
        $data['codestoquemovimentotipo'] = $tipo->codestoquemovimentotipo;
        $data['codestoquemovimentoorigem'] = $this->model->codestoquemovimento;
        $data['entradaquantidade'] = $this->model->saidaquantidade;
        $data['entradavalor'] = $this->model->saidavalor;
        $data['saidaquantidade'] = $this->model->entradaquantidade;
        $data['saidavalor'] = $this->model->entradavalor;
        
        // Salva
        $repoDest->fill($data);
        if (!$repoDest->save()) {
            return false;
        }

        $movs[] = $mov->codestoquemovimento;
        
        return true;
    }
    
    public function create($data = null) {
        $ret = parent::create($data);
        $this->repoMes->calculaCustoMedio($this->model->EstoqueMes);
        $this->geraMovimentoDestino();
        return $ret;
    }
    
    public function update($id = null, $data = null) {
        $anterior = $this->model->fresh();
        $ret = parent::update($id, $data);
        $this->repoMes->calculaCustoMedio($this->model->EstoqueMes);
        if ($anterior->codestoquemes != $this->model->codestoquemes) {
            $this->repoMes->calculaCustoMedio($anterior->EstoqueMes);
        }
        $this->geraMovimentoDestino();
        return $ret;
    }

    public function delete($id = null) {
        $mes = $this->model->EstoqueMes;
        $ret = parent::delete($id);
        $this->repoMes->calculaCustoMedio($mes);
        return $ret;
    }
    
    
    public function activate($id = null) {
        $ret = parent::activate($id);
        $this->repoMes->calculaCustoMedio($this->model->EstoqueMes);
        return $ret;
    }
    
    public function inactivate($id = null) {
        $ret = parent::inactivate($id);
        $this->repoMes->calculaCustoMedio($this->model->EstoqueMes);
        return $ret;
    }    
}
