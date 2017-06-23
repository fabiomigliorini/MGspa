<?php

namespace App\Repositories;
    
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use Illuminate\Foundation\Bus\DispatchesJobs;

use App\Models\EstoqueMes;
use App\Models\EstoqueSaldo;
use App\Models\EstoqueMovimentoTipo;

use App\Jobs\EstoqueCalculaEstatisticas;
use App\Jobs\EstoqueCalculaCustoMedio;


use App\Repositories\EstoqueSaldoRepository;

/**
 * Description of EstoqueMesRepository
 * 
 * @property  Validator $validator
 * @property  EstoqueMes $model
 */
class EstoqueMesRepository extends MGRepository {
    
    use DispatchesJobs;
    
    public function boot() {
        $this->model = new EstoqueMes();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codestoquemes;
        }
        
        $this->validator = Validator::make($data, [
            'codestoquesaldo' => [
                'numeric',
                'required',
            ],
            'mes' => [
                'date',
                'required',
            ],
            'inicialquantidade' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'inicialvalor' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'entradaquantidade' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'entradavalor' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'saidaquantidade' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'saidavalor' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'saldoquantidade' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'saldovalor' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'customedio' => [
                'digits',
                'numeric',
                'nullable',
            ],
        ], [
            'codestoquesaldo.numeric' => 'O campo "codestoquesaldo" deve ser um número!',
            'codestoquesaldo.required' => 'O campo "codestoquesaldo" deve ser preenchido!',
            'mes.date' => 'O campo "mes" deve ser uma data!',
            'mes.required' => 'O campo "mes" deve ser preenchido!',
            'inicialquantidade.digits' => 'O campo "inicialquantidade" deve conter no máximo 3 dígitos!',
            'inicialquantidade.numeric' => 'O campo "inicialquantidade" deve ser um número!',
            'inicialvalor.digits' => 'O campo "inicialvalor" deve conter no máximo 2 dígitos!',
            'inicialvalor.numeric' => 'O campo "inicialvalor" deve ser um número!',
            'entradaquantidade.digits' => 'O campo "entradaquantidade" deve conter no máximo 3 dígitos!',
            'entradaquantidade.numeric' => 'O campo "entradaquantidade" deve ser um número!',
            'entradavalor.digits' => 'O campo "entradavalor" deve conter no máximo 2 dígitos!',
            'entradavalor.numeric' => 'O campo "entradavalor" deve ser um número!',
            'saidaquantidade.digits' => 'O campo "saidaquantidade" deve conter no máximo 3 dígitos!',
            'saidaquantidade.numeric' => 'O campo "saidaquantidade" deve ser um número!',
            'saidavalor.digits' => 'O campo "saidavalor" deve conter no máximo 2 dígitos!',
            'saidavalor.numeric' => 'O campo "saidavalor" deve ser um número!',
            'saldoquantidade.digits' => 'O campo "saldoquantidade" deve conter no máximo 3 dígitos!',
            'saldoquantidade.numeric' => 'O campo "saldoquantidade" deve ser um número!',
            'saldovalor.digits' => 'O campo "saldovalor" deve conter no máximo 2 dígitos!',
            'saldovalor.numeric' => 'O campo "saldovalor" deve ser um número!',
            'customedio.digits' => 'O campo "customedio" deve conter no máximo 6 dígitos!',
            'customedio.numeric' => 'O campo "customedio" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->EstoqueMovimentoS->count() > 0) {
            return 'Estoque Mes sendo utilizada em "EstoqueMovimento"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = EstoqueMes::query();
        
        // Filtros
         if (!empty($filters['codestoquemes'])) {
            $qry->where('codestoquemes', '=', $filters['codestoquemes']);
        }

         if (!empty($filters['codestoquesaldo'])) {
            $qry->where('codestoquesaldo', '=', $filters['codestoquesaldo']);
        }

         if (!empty($filters['mes'])) {
            $qry->where('mes', '=', $filters['mes']);
        }

         if (!empty($filters['inicialquantidade'])) {
            $qry->where('inicialquantidade', '=', $filters['inicialquantidade']);
        }

         if (!empty($filters['inicialvalor'])) {
            $qry->where('inicialvalor', '=', $filters['inicialvalor']);
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

         if (!empty($filters['saldoquantidade'])) {
            $qry->where('saldoquantidade', '=', $filters['saldoquantidade']);
        }

         if (!empty($filters['saldovalor'])) {
            $qry->where('saldovalor', '=', $filters['saldovalor']);
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

         if (!empty($filters['customedio'])) {
            $qry->where('customedio', '=', $filters['customedio']);
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
            , 'recordsTotal' => EstoqueMes::count()
            , 'data' => $qry->get()
        ];
        
    }
    
    
    public function buscaProximos($qtd = 7, EstoqueMes $model = null)
    {
        if (empty($model)) {
            $model = $this->model;
        }
        $ems = EstoqueMes::where('codestoquesaldo', $model->codestoquesaldo)
               ->where('mes', '>', $model->mes)
               ->orderBy('mes', 'asc')
               ->take($qtd)
               ->get();
        return $ems;
    }
    
    public function buscaAnteriores($qtd = 7, EstoqueMes $model = null)
    {
        if (empty($model)) {
            $model = $this->model;
        }
        $ems = EstoqueMes::where('codestoquesaldo', $model->codestoquesaldo)
               ->where('mes', '<', $model->mes)
               ->orderBy('mes', 'desc')
               ->take($qtd)
               ->get();
        return $ems->reverse();
    }
    
    /**
     * Calcula mês para o movimento
     * @param EstoqueSaldo $sld
     * @param Carbon $data
     * @return Carbon
     */
    public function calculaMes(EstoqueSaldo $sld, Carbon $data) 
    {
        // Padrao primeiro dia do mes
        $mes = Carbon::create($data->year, $data->month, 1);
        
        // Se for fiscal cria somente um mês por ano, dezembro, até 2016
        if ($sld->fiscal && $mes->year <= 2016) {
            $mes->month = 12;
        }
        
        return $mes;
        
    }
    
    /**
     * Busca Mes do Saldo
     * @param EstoqueSaldo $sld
     * @param Carbon $data
     * @return EstoqueMes
     */
    public function busca (EstoqueSaldo $sld, Carbon $data) 
    {
        $mes = $this->calculaMes($sld, $data);
        return $this->model = $sld->EstoqueMesS()->where('mes', $mes)->first();
    }
    
    /**
     * Busca ou cria Mes para o Saldo
     * @param EstoqueSaldo $sld
     * @param Carbon $data
     * @return EstoqueMes
     */
    public function buscaOuCria (EstoqueSaldo $sld, Carbon $data) 
    {
        if ($this->busca($sld, $data)) {
            return $this->model;
        }
        
        $mes = $this->calculaMes($sld, $data);
        
        if (!$this->create([
            'codestoquesaldo' => $sld->codestoquesaldo,
            'mes' => $mes,
        ])) {
            return false;
        }
        
        return $this->model;
        
    }
    
    /**
     * Busca ou cria um Mes e suas dependências pela chave do estoque
     * @param int $codestoquelocal
     * @param int $codprodutovariacao
     * @param bool $fiscal
     * @param Carbon $data
     * @return EstoqueMes
     */
    public function buscaOuCriaPelaChave(int $codestoquelocal, int $codprodutovariacao, bool $fiscal, Carbon $data)
    {
        $repo_elpv = new EstoqueLocalProdutoVariacaoRepository();
        if (!$elpv = $repo_elpv->buscaOuCria($codestoquelocal, $codprodutovariacao)) {
            return false;
        }
        
        $repo_sld = new EstoqueSaldoRepository();
        if (!$sld = $repo_sld->buscaOuCria($elpv, $fiscal)) {
            return false;
        }
        
        return $this->buscaOuCria($sld, $data);
            
    }
    
    public function calculaCustoMedio(EstoqueMes $mes, $ciclo = 0) {

        Log::info('EstoqueMes::CalculaCustoMedio', ['codestoquemes' => $mes->codestoquemes, 'ciclo' => $ciclo]);
        
        if ($ciclo > 50) {
            Log::error('EstoqueMes::CalculaCustoMedio - Ciclo maior que 50', ['codestoquemes' => $mes->codestoquemes, 'ciclo' => $ciclo]);
            return;
        }
        
        $mes = $mes->fresh();
        
        // recalcula valor movimentacao com base no registro de origem
        $sql = "
            update tblestoquemovimento
            set entradavalor = orig.saidavalor / orig.saidaquantidade * tblestoquemovimento.entradaquantidade
                , saidavalor = orig.entradavalor / orig.entradaquantidade * tblestoquemovimento.saidaquantidade
            from tblestoquemovimento orig
            where tblestoquemovimento.codestoquemovimentoorigem = orig.codestoquemovimento
            and tblestoquemovimento.codestoquemes = {$mes->codestoquemes}
            ";
            
        $ret = DB::update($sql);
        
        
        //busca totais de registros nao baseados no custo medio
        $sql = "
            select 
                sum(entradaquantidade) as entradaquantidade
                , sum(entradavalor) as entradavalor
                , sum(saidaquantidade) as saidaquantidade
                , sum(saidavalor) as saidavalor
            from tblestoquemovimento mov
            left join tblestoquemovimentotipo tipo on (tipo.codestoquemovimentotipo = mov.codestoquemovimentotipo)
            where mov.codestoquemes = {$mes->codestoquemes}
            and tipo.preco in (" . EstoqueMovimentoTipo::PRECO_INFORMADO . ", " . EstoqueMovimentoTipo::PRECO_ORIGEM . ")";

        $mov = DB::select($sql);
        $mov = $mov[0];

        //busca saldo inicial
        $inicialquantidade = 0;
        $inicialvalor = 0;
        $anterior = self::buscaAnteriores(1, $mes);
        if (isset($anterior[0]))
        {
            $inicialquantidade = $anterior[0]->saldoquantidade;
            $inicialvalor = $anterior[0]->saldovalor;
        }

        //calcula custo medio
        $valor = $mov->entradavalor - $mov->saidavalor;
        $quantidade = $mov->entradaquantidade - $mov->saidaquantidade;
        if ($inicialquantidade > 0 && $inicialvalor > 0)
        {
            $valor += $inicialvalor;
            $quantidade += $inicialquantidade;
        }
        $customedio = 0;
        if ($quantidade != 0) {
            $customedio = abs($valor/$quantidade);
        }
        
        if (empty($customedio) && isset($anterior[0])) {
            $customedio = $anterior[0]->customedio;
        }

        if ($customedio > 100000) {
            return;
        }
        
        //recalcula valor movimentacao com base custo medio
        $sql = "
            update tblestoquemovimento
            set saidavalor = saidaquantidade * $customedio
                , entradavalor = entradaquantidade * $customedio
            where tblestoquemovimento.codestoquemes = {$mes->codestoquemes} 
            and tblestoquemovimento.codestoquemovimentotipo in 
                (select t.codestoquemovimentotipo from tblestoquemovimentotipo t where t.preco = " . EstoqueMovimentoTipo::PRECO_MEDIO . ")
            ";
            
        $ret = DB::update($sql);
        
        //busca totais movimentados do 
        $sql = "
            select 
                sum(entradaquantidade) entradaquantidade
                , sum(entradavalor) entradavalor
                , sum(saidaquantidade) saidaquantidade
                , sum(saidavalor) saidavalor
            from tblestoquemovimento mov
            left join tblestoquemovimentotipo tipo on (tipo.codestoquemovimentotipo = mov.codestoquemovimentotipo)
            where mov.codestoquemes = {$mes->codestoquemes}
            ";

        $mov = DB::select($sql);
        $mov = $mov[0];
        
        //calcula custo medio e totais novamente
        $mes->inicialquantidade = $inicialquantidade;
        //$mes->inicialvalor = $mes->inicialquantidade * $customedio;
        $mes->inicialvalor = $inicialvalor;
        $mes->entradaquantidade = $mov->entradaquantidade;
        $mes->entradavalor = $mov->entradavalor;
        $mes->saidaquantidade = $mov->saidaquantidade;
        $mes->saidavalor = $mov->saidavalor;
        $mes->saldoquantidade = $inicialquantidade + $mov->entradaquantidade - $mov->saidaquantidade;
        $mes->saldovalor = $mes->saldoquantidade * $customedio;
        $customedioanterior = $mes->customedio;
        $mes->customedio = $customedio;

        $mes->save();
        
        $customediodiferenca = abs($customedio - $customedioanterior);
        
        $mesesRecalcular = [];
        if ($customediodiferenca > 0.01)
        {
            $sql = "
                select distinct dest.codestoquemes
                from tblestoquemovimento orig
                inner join tblestoquemovimento dest on (dest.codestoquemovimentoorigem = orig.codestoquemovimento)
                where orig.codestoquemes = {$mes->codestoquemes}
                ";
            $ret = DB::select($sql);
            foreach ($ret as $row) {
                $mesesRecalcular[] = $row->codestoquemes;
            }
        }
        
        $proximo = $this->buscaProximos(1, $mes);
        if (isset($proximo[0])) {
            $mesesRecalcular[] = $proximo[0]->codestoquemes;
        } else {
            $mes->EstoqueSaldo->saldoquantidade = $mes->saldoquantidade;
            $mes->EstoqueSaldo->saldovalor = $mes->saldovalor;
            $mes->EstoqueSaldo->customedio = $mes->customedio;
            $mes->EstoqueSaldo->save();
            
            //atualiza 'dataentrada'
            DB::update(DB::raw("
                update tblestoquesaldo
                set dataentrada = (
                        select 
                                x.data 
                        from (
                                select 
                                        mov.data
                                        , mov.entradaquantidade
                                        , sum(mov.entradaquantidade) over (order by mov.data desc) as soma
                                from tblestoquemes mes
                                inner join tblestoquemovimento mov on (mov.codestoquemes = mes.codestoquemes)
                                inner join tblestoquemovimentotipo tipo on (tipo.codestoquemovimentotipo = mov.codestoquemovimentotipo)
                                where mes.codestoquesaldo = tblestoquesaldo.codestoquesaldo
                                and mov.entradaquantidade is not null
                                and tipo.atualizaultimaentrada = true
                                ) x
                        where soma >= tblestoquesaldo.saldoquantidade
                        order by data DESC
                        limit 1
                )
                where tblestoquesaldo.codestoquesaldo = {$mes->codestoquesaldo}
            "));
        }
        
        $repo = new Self();
        if (!$mes->EstoqueSaldo->fiscal) {
            $repo->dispatch((new EstoqueCalculaEstatisticas($mes->EstoqueSaldo->EstoqueLocalProdutoVariacao->codprodutovariacao, $mes->EstoqueSaldo->EstoqueLocalProdutoVariacao->codestoquelocal))->onQueue('low'));
        }
        foreach ($mesesRecalcular as $mes) {
            $repo->dispatch((new EstoqueCalculaCustoMedio($mes, $ciclo +1))->onQueue('urgent'));
        }
        
    }
    
    /**
     * Monta array da movimentacao do Kardex
     * @param EstoqueMes $model
     * @return array
     */
    public function movimentoKardex ($model = null) 
    {
        if (empty($model)) {
            $model = $this->model;
        }
        $qry = $model->EstoqueMovimentoS()->select([
            'tblestoquemovimento.codestoquemovimento',
            'tblestoquemovimento.data',
            'tblestoquemovimento.entradaquantidade',
            'tblestoquemovimento.entradavalor',
            'tblestoquemovimento.saidaquantidade',
            'tblestoquemovimento.saidavalor',
            'tblestoquemovimento.observacoes',
            'tblestoquemovimento.manual',
            'tblestoquemovimento.codestoquesaldoconferencia',
            
            'tblestoquemovimentotipo.descricao',
            
            'tblnotafiscalprodutobarra.codnotafiscal',
            'tblnotafiscal.codpessoa as codpessoanotafiscal',
            'tblnotafiscal.emitida as emitidanotafiscal',
            'tblnotafiscal.serie as serienotafiscal',
            'tblnotafiscal.numero as numeronotafiscal',
            'tblnotafiscal.modelo as modelonotafiscal',
            'tblpessoanotafiscal.fantasia as pessoanotafiscal',
            
            'tblnegocioprodutobarra.codnegocio',
            'tblnegocio.codpessoa as codpessoanegocio',
            'tblpessoanegocio.fantasia as pessoanegocio',
            
            'tblestoquemovimentoorigem.codestoquemes as codestoquemesorigem',
            
        ]);
        $qry->orderBy('tblestoquemovimento.data', 'asc');
        $qry->orderBy('tblestoquemovimento.criacao', 'asc');
        
        $qry->join('tblestoquemovimentotipo', 'tblestoquemovimentotipo.codestoquemovimentotipo', '=', 'tblestoquemovimento.codestoquemovimentotipo');
        
        $qry->leftjoin('tblnotafiscalprodutobarra', 'tblnotafiscalprodutobarra.codnotafiscalprodutobarra', '=', 'tblestoquemovimento.codnotafiscalprodutobarra');
        $qry->leftjoin('tblnotafiscal', 'tblnotafiscal.codnotafiscal', '=', 'tblnotafiscalprodutobarra.codnotafiscal');
        $qry->leftjoin('tblpessoa as tblpessoanotafiscal', 'tblpessoanotafiscal.codpessoa', '=', 'tblnotafiscal.codpessoa');
        
        $qry->leftjoin('tblnegocioprodutobarra', 'tblnegocioprodutobarra.codnegocioprodutobarra', '=', 'tblestoquemovimento.codnegocioprodutobarra');
        $qry->leftjoin('tblnegocio', 'tblnegocio.codnegocio', '=', 'tblnegocioprodutobarra.codnegocio');
        $qry->leftjoin('tblpessoa as tblpessoanegocio', 'tblpessoanegocio.codpessoa', '=', 'tblnegocio.codpessoa');

        $qry->leftjoin('tblestoquemovimento as tblestoquemovimentoorigem', 'tblestoquemovimentoorigem.codestoquemovimento', '=', 'tblestoquemovimento.codestoquemovimentoorigem');

        $regs = $qry->get();
        
        $saldoquantidade = $model->inicialquantidade;
        $saldovalor = $model->inicialvalor;
        $customedio = ($saldoquantidade != 0)?$saldovalor/$saldoquantidade:null;
        $ret = [
            'inicial' => [
                'entradaquantidade' => ($model->inicialquantidade >= 0)?$model->inicialquantidade:null,
                'entradavalor' => ($model->inicialvalor >= 0)?$model->inicialvalor:null,
                'saidaquantidade' => ($model->inicialquantidade < 0)?$model->inicialquantidade:null,
                'saidavalor' => ($model->inicialvalor < 0)?$model->inicialvalor:null,
                'saldoquantidade' => $saldoquantidade,
                'saldovalor' => $saldovalor,
                'customedio' => $customedio,
            ],
            'total' => [
                'entradaquantidade' => $regs->sum('entradaquantidade'),
                'entradavalor' => $regs->sum('entradavalor'),
                'saidaquantidade' => $regs->sum('saidaquantidade'),
                'saidavalor' => $regs->sum('saidavalor'),
                'saldoquantidade' => $model->saldoquantidade,
                'saldovalor' => $model->saldovalor,
                'customedio' => $model->customedio,
            ],
            'movimento' => []
        ];
        foreach ($regs as $reg) {
            $saldoquantidade += $reg->entradaquantidade - $reg->saidaquantidade;
            $saldovalor += $reg->entradavalor - $reg->saidavalor;
            $customedio = (($reg->entradaquantidade - $reg->saidaquantidade) != 0)?($reg->entradavalor - $reg->saidavalor)/($reg->entradaquantidade - $reg->saidaquantidade):null;
            
            $urldocumento = null;
            $documento = null;
            $urlpessoa = null;
            $pessoa = null;
            if (!empty($reg->codnotafiscal)) {
                $urldocumento = url('nota-fiscal', $reg->codnotafiscal);
                $documento = formataNumeroNota($reg->emitidanotafiscal, $reg->serienotafiscal, $reg->numeronotafiscal, $reg->modelonotafiscal);
                $urlpessoa = url('pessoa', $reg->codpessoanotafiscal);
                $pessoa = $reg->pessoanotafiscal;
            } elseif (!empty($reg->codnegocio)) {
                $urldocumento = url('negocio', $reg->codnegocio);
                $documento = formataCodigo($reg->codnegocio);
                $urlpessoa = url('pessoa', $reg->codpessoanegocio);
                $pessoa = $reg->pessoanegocio;
            }
            
            $urlestoquemesrelacionado = null;
            if (!empty($reg->codestoquemesorigem)) {
                $urlestoquemesrelacionado = url('estoque-mes', $reg->codestoquemesorigem);
            } elseif ($filho = $reg->EstoqueMovimentoDestinoS()->first()) {
                $urlestoquemesrelacionado = url('estoque-mes', $filho->codestoquemes);
            }
                
            $ret['movimento'][] = [
                'codestoquemovimento' => $reg->codestoquemovimento,
                'data' => $reg->data,
                'descricao' => $reg->descricao,
                'entradaquantidade' => $reg->entradaquantidade,
                'entradavalor' => $reg->entradavalor,
                'saidaquantidade' => $reg->saidaquantidade,
                'saidavalor' => $reg->saidavalor,
                'saldoquantidade' => $saldoquantidade,
                'saldovalor' => $saldovalor,
                'customedio' => $customedio,
                'pessoa' => $pessoa,
                'documento' => $documento,
                'urlpessoa' => $urlpessoa,
                'urlestoquemesrelacionado' => $urlestoquemesrelacionado,
                'urldocumento' => $urldocumento,
                'observacoes' => $reg->observacoes,
                'manual' => $reg->manual,
                'codestoquesaldoconferencia' => $reg->codestoquesaldoconferencia,
            ];
        }
        return $ret;
    }
    
}
