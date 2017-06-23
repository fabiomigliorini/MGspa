<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Cheque;
use App\Models\Pessoa;
use App\Repositories\BancoRepository;
use App\Library\Cmc7\Cmc7;

/**
 * Description of ChequeRepository
 * 
 * @property  Validator $validator
 * @property  Cheque $model
 */
class ChequeRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Cheque();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codcheque;
        }
        
        $this->validator = Validator::make($data, [
            'cmc7' => [
                'max:34',
                'min:34',
                'required',
            ],
            'codbanco' => [
                'numeric',
                'required',
            ],
            'agencia' => [
                'max:10',
                'required',
            ],
            'contacorrente' => [
                'max:15',
                'required',
            ],
            'numero' => [
                'max:15',
                'required',
            ],
            'emissao' => [
                'date',
                'required',
            ],
            'vencimento' => [
                'date',
                'required',
            ],
            'repasse' => [
                'date',
                'nullable',
            ],
            'destino' => [
                'max:50',
                'nullable',
            ],
            'devolucao' => [
                'date',
                'nullable',
            ],
            'motivodevolucao' => [
                'max:50',
                'nullable',
            ],
            'observacao' => [
                'max:200',
                'nullable',
            ],
            'lancamento' => [
                'date',
                'nullable',
            ],
            'cancelamento' => [
                'date',
                'nullable',
            ],
            'valor' => [
                'numeric',
                'required',
            ],
            'codpessoa' => [
                'numeric',
                'required',
            ],
            'indstatus' => [
                'numeric',
                'required',
            ],
            'codtitulo' => [
                'numeric',
                'nullable',
            ],
        ], [
            'cmc7.required' => 'O campo "cmc7" deve ser preenchido!',
            'cmc7.max' => 'O campo "cmc7" não pode conter mais que 34 caracteres!',
            'cmc7.min' => 'O campo "cmc7" não pode conter menos que 34 caracteres!',
            'codbanco.numeric' => 'O campo "codbanco" deve ser um número!',
            'codbanco.required' => 'O campo "codbanco" deve ser preenchido!',
            'agencia.max' => 'O campo "agencia" não pode conter mais que 10 caracteres!',
            'agencia.required' => 'O campo "agencia" deve ser preenchido!',
            'contacorrente.max' => 'O campo "contacorrente" não pode conter mais que 15 caracteres!',
            'contacorrente.required' => 'O campo "contacorrente" deve ser preenchido!',
            'numero.max' => 'O campo "numero" não pode conter mais que 15 caracteres!',
            'numero.required' => 'O campo "numero" deve ser preenchido!',
            'emissao.date' => 'O campo "emissao" deve ser uma data!',
            'emissao.required' => 'O campo "emissao" deve ser preenchido!',
            'vencimento.date' => 'O campo "vencimento" deve ser uma data!',
            'vencimento.required' => 'O campo "vencimento" deve ser preenchido!',
            'repasse.date' => 'O campo "repasse" deve ser uma data!',
            'destino.max' => 'O campo "destino" não pode conter mais que 50 caracteres!',
            'devolucao.date' => 'O campo "devolucao" deve ser uma data!',
            'motivodevolucao.max' => 'O campo "motivodevolucao" não pode conter mais que 50 caracteres!',
            'observacao.max' => 'O campo "observacao" não pode conter mais que 200 caracteres!',
            'lancamento.date' => 'O campo "lancamento" deve ser uma data!',
            'cancelamento.date' => 'O campo "cancelamento" deve ser uma data!',
            'valor.numeric' => 'O campo "valor" deve ser um número!',
            'valor.required' => 'O campo "valor" deve ser preenchido!',
            'codpessoa.numeric' => 'O campo "codpessoa" deve ser um número!',
            'codpessoa.required' => 'O campo "codpessoa" deve ser preenchido!',
            'indstatus.numeric' => 'O campo "indstatus" deve ser um número!',
            'indstatus.required' => 'O campo "indstatus" deve ser preenchido!',
            'codtitulo.numeric' => 'O campo "codtitulo" deve ser um número!',
        ]);
        
        if (!$this->validator->passes()) {
            return false;
        }
        
        
        /*
        $repoEmit = new ChequeEmitenteRepository();
        
        foreach ($data['emitentes'] as $emit) {
            if (!$repoEmit->validate($emit, $emit['codchequeemitente'])) {
                return false;
            }
        }*/
        return true;
        
    }
    
    public function parseData ($data) {
        
        $cmc7 = new Cmc7($data['cmc7']);
        
        $bancoRepo = new BancoRepository();
        $bancoRepo->findOrCreateByNumero($cmc7->banco());
        
        $data['codbanco'] = $bancoRepo->model->codbanco;
        $data['agencia'] = $cmc7->agencia();
        $data['contacorrente'] = $cmc7->contacorrente();
        $data['numero'] = $cmc7->numero();
        
        if (empty($data['indstatus'])) {
            $data['indstatus'] = 1;
        }
       
           
        if(isset($data['chequeemitente_cnpj'])){
            $cnpjs = $data['chequeemitente_cnpj'];
            $emitentes = $data['chequeemitente_emitente'];
            $cods = $data['chequeemitente_codchequeemitente'];

            $data['emitentes'] = [];
            foreach ($cnpjs as $i => $cnpj) {
                $emit = [
                    'cnpj' => $cnpjs[$i],
                    'emitente' => $emitentes[$i],
                    'codchequeemitente' => $cods[$i],
                ];
                $data['emitentes'][] = $emit;
            }
           
        }else{
            $data['emitentes'][] = [];
        }
         
        return $data;
    }
    
    public function create($data = null) {
       
        if (!parent::create($data)) {
            return false;
        }
        
        return $this->salvaEmitentes($this->model, $data['emitentes']);
    
    }

    public function update($id = null, $data = null) {
        if (!parent::update($id, $data)) {
            return false;
        }
        if(!empty($data['emitentes'])){
            return $this->salvaEmitentes($this->model, $data['emitentes']);
        }else{
            return true;
        }
    }
    
    public function salvaEmitentes($model, $emitentes) {
        
        $repoEmit = new ChequeEmitenteRepository();
        $conEmitentes = $this->model->ChequeEmitenteS;
        
        $codchequeemitentes_existentes = [];
        $codchequeemitentes = '';
        foreach ($emitentes as $emit) {
           //salvar emitentes e guardar num array os ids
            $data = [
               'codcheque'=>$model->codcheque,
               'emitente'=>$emit['emitente'],
               'cnpj'=>$emit['cnpj']
            ];
            if($emit['codchequeemitente']<>''){
                $repoEmit->update($emit['codchequeemitente'],$data);
            }else{
                $repoEmit->create($data);  
            }
            $codchequeemitentes[] = $repoEmit->model->codchequeemitente;
        }
        if(!empty($codchequeemitentes)){
            $emits = $this->model->ChequeEmitenteS()->whereNotIn('codchequeemitente', $codchequeemitentes)->get();
            foreach ($emits as $emit) {
                $repoEmit->delete($emit->codchequeemitente);
            }
        }
        return true;
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ChequeRepasseChequeS->count() > 0) {
            return 'Cheque sendo utilizada em "ChequeRepasseCheque"!';
        }
        
        if ($this->model->ChequeEmitenteS->count() > 0) {
            return 'Cheque sendo utilizada em "ChequeEmitente"!';
        }
        
        if ($this->model->CobrancaS->count() > 0) {
            return 'Cheque sendo utilizada em "Cobranca"!';
        }
        
        return false;
       
    }
    
    public function delete($id = null) {
        
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        foreach ($this->model->ChequeEmitenteS as $emits){
            $emits->delete();
        }
        return $this->model->delete();
        
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
       
        // Query da Entidade
        $qry = Cheque::query();
        
        // Filtros
         if (!empty($filters['codcheque'])) {
            $qry->where('codcheque', '=', $filters['codcheque']);
        }

        if (!empty($filters['cmc7'])) {
            $qry->palavras('cmc7', $filters['cmc7']);
        }

         if (!empty($filters['codbanco'])) {
            $qry->where('codbanco', '=', $filters['codbanco']);
        }

         if (!empty($filters['agencia'])) {
            $qry->palavras('agencia', $filters['agencia']);
        }

         if (!empty($filters['contacorrente'])) {
            $qry->palavras('contacorrente', $filters['contacorrente']);
        }

         if (!empty($filters['numero'])) {
            $qry->palavras('numero', $filters['numero']);
        }

         if (!empty($filters['emissao'])) {
            $qry->where('emissao', '=', $filters['emissao']);
        }

        if(!empty($filters['emitente'])) {
            $palavras = explode(' ', $filters['emitente']);
            foreach ($palavras as $palavra) {
                $qry->where('emitente', 'ilike', "%{$palavra}%");
            }
        }
        
        if (!empty($filters['valor_de'])) {
            $qry->where('valor','>=', $filters['valor_de']);
        }
        if (!empty($filters['valor_ate'])) {
            $qry->where('valor','<=', $filters['valor_ate']);
        }
        if (!empty($filters['indstatus'])) {
            $qry->where('indstatus','=',$filters['indstatus']);
        }

        if (!empty($filters['vencimento_de'])){
            $qry->where('vencimento','>=', $filters['vencimento_de']);
        }
        if (!empty($filters['vencimento_ate'])){
            $qry->where('vencimento','<=', $filters['vencimento_ate']);
        }
         
         if (!empty($filters['repasse'])) {
            $qry->where('repasse', '=', $filters['repasse']);
        }

         if (!empty($filters['destino'])) {
            $qry->palavras('destino', $filters['destino']);
        }

         if (!empty($filters['devolucao'])) {
            $qry->where('devolucao', '=', $filters['devolucao']);
        }

         if (!empty($filters['motivodevolucao'])) {
            $qry->palavras('motivodevolucao', $filters['motivodevolucao']);
        }

         if (!empty($filters['observacao'])) {
            $qry->palavras('observacao', $filters['observacao']);
        }

         if (!empty($filters['lancamento'])) {
            $qry->where('lancamento', '=', $filters['lancamento']);
        }

         if (!empty($filters['alteracao'])) {
            $qry->where('alteracao', '=', $filters['alteracao']);
        }

         if (!empty($filters['cancelamento'])) {
            $qry->where('cancelamento', '=', $filters['cancelamento']);
        }

         if (!empty($filters['valor'])) {
            $qry->where('valor', '=', $filters['valor']);
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

        if (!empty($filters['codpessoa'])) {
            $qry->where('codpessoa', '=', $filters['codpessoa']);
        }


        if (!empty($filters['codtitulo'])) {
            $qry->where('codtitulo', '=', $filters['codtitulo']);
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
            , 'recordsTotal' => Cheque::count()
            , 'data' => $qry->get()
        ];
        
    }
    
    
    public function findUltimoMesmoEmitente($banco, $agencia, $contacorrente) {

        $query = Cheque::query();
        $query->where('codbanco', $banco);
        $query->where('agencia', $agencia);
        $query->where('contacorrente', $contacorrente);
        $query->orderBy('criacao', 'DESC');

        return $query->first();

    }
    
    public function consultaCmc7 ($cmc7) {
        
        if ($consultacmc7 = Cheque::where('cmc7','=',$cmc7)->first()){
            
            return [
                'valido' => false,
                'error' => 'Já existe um cadastro com esse CMC7. #'.$consultacmc7->codcheque
            ];
           
           exit;
        }

        $cmc7n = new Cmc7($cmc7);

        //dd($cmc7n->banco());
        $ultimo = [
            'codpessoa' => null,
            'emitentes' => [],
        ];
        
        //------- Pesquisa se há emitentes para o cheque cadastrado
        if ($retorno = $this->findUltimoMesmoEmitente($cmc7n->banco(), $cmc7n->agencia(), $cmc7n->contacorrente())) {
            
            $ultimo['codpessoa'] = $retorno->codpessoa;

            foreach ($retorno->ChequeEmitenteS as $emit) {
                $ultimo['emitentes'][] = [
                    'cnpj' => $emit->cnpj,
                    'emitente' => $emit->emitente,
                ];
                if($ultimo['codpessoa']== null){
                    if($pessoa = $this->pessoaRepository->model->where('cnpj', $emit->cnpj)->first()){
                        $ultimo['codpessoa'] = $pessoa['codpessoa'];
                    }
                }
            }
        }
        
        $repoBanco = new BancoRepository();
        
        //------- Consulta Banco 001052990860853885621000879557 99682184134
        if($banco = $repoBanco->findByNumero($cmc7n->banco())) {
            $banco_nome = $banco['banco'];
        }else{
            $banco_nome = $cmc7n->banco();
        }
               
        //------ Consultar pelo emitente
        if($cmc7n->valido()==false){
            $error = 'CMC7 Inválido';
        }else{
            $error = null;
        }
        return [
            'valido' => $cmc7n->valido(),
            'error' => $error,
            'banco' => $banco_nome,
            'agencia' => $cmc7n->agencia(),
            'contacorrente' => $cmc7n->contacorrente(),
            'numero' => $cmc7n->numero(),
            'ultimo' => $ultimo,
        ];
        
    }
    
    public function consultaEmitente($cnpj){
        
        $retorno = [
            'codpessoa' => null,
            'pessoa' => null,
        ];
        if($pessoa = Pessoa::where('cnpj', $cnpj)->first()){
            $retorno['codpessoa'] = $pessoa->codpessoa;
            $retorno['pessoa'] = $pessoa->pessoa;
        }
        return $retorno;
        
    }
    
    public function status($status){
        foreach($this->status_lista() as $sts){
            if($sts['codigo']==$status){
                return $sts;
            }
        }
    }
    public function status_lista(){
        return array(
            array('codigo'=> 1,'status'=>'À Repassar','label'=>'badge badge-primary'),
            array('codigo'=> 2,'status'=>'Repassado','label'=>'badge badge-warning'),
            array('codigo'=> 3,'status'=>'Devolvido','label'=>'badge badge-danger'),
            array('codigo'=> 4,'status'=>'Em Cobranca','label'=>'badge badge-danger'),
            array('codigo'=> 5,'status'=>'Liquidado','label'=>'badge badge-success')
        );
    }
    public function status_select2(){
        $std = [];
        //$std[''] = 'Todos';
        foreach($this->status_lista() as $sts){
            $std[$sts['codigo']] = $sts['status'];
        }
        return $std;
    }
}
