<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Titulo;

/**
 * Description of TituloRepository
 * 
 * @property  Validator $validator
 * @property  Titulo $model
 */
class TituloRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Titulo();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codtitulo;
        }
        
        $this->validator = Validator::make($data, [
            'codtipotitulo' => [
                'numeric',
                'required',
            ],
            'codfilial' => [
                'numeric',
                'required',
            ],
            'codportador' => [
                'numeric',
                'nullable',
            ],
            'codpessoa' => [
                'numeric',
                'required',
            ],
            'codcontacontabil' => [
                'numeric',
                'required',
            ],
            'numero' => [
                'max:20',
                'required',
            ],
            'fatura' => [
                'max:50',
                'nullable',
            ],
            'transacao' => [
                'date',
                'required',
            ],
            'sistema' => [
                'date',
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
            'vencimentooriginal' => [
                'date',
                'required',
            ],
            'debito' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'credito' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'gerencial' => [
                'boolean',
                'required',
            ],
            'observacao' => [
                'max:255',
                'nullable',
            ],
            'boleto' => [
                'boolean',
                'required',
            ],
            'nossonumero' => [
                'max:20',
                'nullable',
            ],
            'debitototal' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'creditototal' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'saldo' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'debitosaldo' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'creditosaldo' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'transacaoliquidacao' => [
                'date',
                'nullable',
            ],
            'codnegocioformapagamento' => [
                'numeric',
                'nullable',
            ],
            'codtituloagrupamento' => [
                'numeric',
                'nullable',
            ],
            'remessa' => [
                'numeric',
                'nullable',
            ],
            'estornado' => [
                'date',
                'nullable',
            ],
            'codvalecompraformapagamento' => [
                'numeric',
                'nullable',
            ],
        ], [
            'codtipotitulo.numeric' => 'O campo "codtipotitulo" deve ser um número!',
            'codtipotitulo.required' => 'O campo "codtipotitulo" deve ser preenchido!',
            'codfilial.numeric' => 'O campo "codfilial" deve ser um número!',
            'codfilial.required' => 'O campo "codfilial" deve ser preenchido!',
            'codportador.numeric' => 'O campo "codportador" deve ser um número!',
            'codpessoa.numeric' => 'O campo "codpessoa" deve ser um número!',
            'codpessoa.required' => 'O campo "codpessoa" deve ser preenchido!',
            'codcontacontabil.numeric' => 'O campo "codcontacontabil" deve ser um número!',
            'codcontacontabil.required' => 'O campo "codcontacontabil" deve ser preenchido!',
            'numero.max' => 'O campo "numero" não pode conter mais que 20 caracteres!',
            'numero.required' => 'O campo "numero" deve ser preenchido!',
            'fatura.max' => 'O campo "fatura" não pode conter mais que 50 caracteres!',
            'transacao.date' => 'O campo "transacao" deve ser uma data!',
            'transacao.required' => 'O campo "transacao" deve ser preenchido!',
            'sistema.date' => 'O campo "sistema" deve ser uma data!',
            'sistema.required' => 'O campo "sistema" deve ser preenchido!',
            'emissao.date' => 'O campo "emissao" deve ser uma data!',
            'emissao.required' => 'O campo "emissao" deve ser preenchido!',
            'vencimento.date' => 'O campo "vencimento" deve ser uma data!',
            'vencimento.required' => 'O campo "vencimento" deve ser preenchido!',
            'vencimentooriginal.date' => 'O campo "vencimentooriginal" deve ser uma data!',
            'vencimentooriginal.required' => 'O campo "vencimentooriginal" deve ser preenchido!',
            'debito.digits' => 'O campo "debito" deve conter no máximo 2 dígitos!',
            'debito.numeric' => 'O campo "debito" deve ser um número!',
            'credito.digits' => 'O campo "credito" deve conter no máximo 2 dígitos!',
            'credito.numeric' => 'O campo "credito" deve ser um número!',
            'gerencial.boolean' => 'O campo "gerencial" deve ser um verdadeiro/falso (booleano)!',
            'gerencial.required' => 'O campo "gerencial" deve ser preenchido!',
            'observacao.max' => 'O campo "observacao" não pode conter mais que 255 caracteres!',
            'boleto.boolean' => 'O campo "boleto" deve ser um verdadeiro/falso (booleano)!',
            'boleto.required' => 'O campo "boleto" deve ser preenchido!',
            'nossonumero.max' => 'O campo "nossonumero" não pode conter mais que 20 caracteres!',
            'debitototal.digits' => 'O campo "debitototal" deve conter no máximo 2 dígitos!',
            'debitototal.numeric' => 'O campo "debitototal" deve ser um número!',
            'creditototal.digits' => 'O campo "creditototal" deve conter no máximo 2 dígitos!',
            'creditototal.numeric' => 'O campo "creditototal" deve ser um número!',
            'saldo.digits' => 'O campo "saldo" deve conter no máximo 2 dígitos!',
            'saldo.numeric' => 'O campo "saldo" deve ser um número!',
            'debitosaldo.digits' => 'O campo "debitosaldo" deve conter no máximo 2 dígitos!',
            'debitosaldo.numeric' => 'O campo "debitosaldo" deve ser um número!',
            'creditosaldo.digits' => 'O campo "creditosaldo" deve conter no máximo 2 dígitos!',
            'creditosaldo.numeric' => 'O campo "creditosaldo" deve ser um número!',
            'transacaoliquidacao.date' => 'O campo "transacaoliquidacao" deve ser uma data!',
            'codnegocioformapagamento.numeric' => 'O campo "codnegocioformapagamento" deve ser um número!',
            'codtituloagrupamento.numeric' => 'O campo "codtituloagrupamento" deve ser um número!',
            'remessa.numeric' => 'O campo "remessa" deve ser um número!',
            'estornado.date' => 'O campo "estornado" deve ser uma data!',
            'codvalecompraformapagamento.numeric' => 'O campo "codvalecompraformapagamento" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ChequeS->count() > 0) {
            return 'Titulo sendo utilizada em "Cheque"!';
        }
        
        if ($this->model->ValeCompraS->count() > 0) {
            return 'Titulo sendo utilizada em "ValeCompra"!';
        }
        
        if ($this->model->BoletoretornoS->count() > 0) {
            return 'Titulo sendo utilizada em "Boletoretorno"!';
        }
        
        if ($this->model->CobrancaS->count() > 0) {
            return 'Titulo sendo utilizada em "Cobranca"!';
        }
        
        if ($this->model->CobrancaHistoricoTituloS->count() > 0) {
            return 'Titulo sendo utilizada em "CobrancaHistoricoTitulo"!';
        }
        
        if ($this->model->MovimentoTituloS->count() > 0) {
            return 'Titulo sendo utilizada em "MovimentoTitulo"!';
        }
        
        if ($this->model->MovimentoTituloS->count() > 0) {
            return 'Titulo sendo utilizada em "MovimentoTitulo"!';
        }
        
        if ($this->model->NfeterceiroduplicataS->count() > 0) {
            return 'Titulo sendo utilizada em "Nfeterceiroduplicata"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Titulo::query();
        
        // Filtros
         if (!empty($filters['codtitulo'])) {
            $qry->where('codtitulo', '=', $filters['codtitulo']);
        }

         if (!empty($filters['codtipotitulo'])) {
            $qry->where('codtipotitulo', '=', $filters['codtipotitulo']);
        }

         if (!empty($filters['codfilial'])) {
            $qry->where('codfilial', '=', $filters['codfilial']);
        }

         if (!empty($filters['codportador'])) {
            $qry->where('codportador', '=', $filters['codportador']);
        }

         if (!empty($filters['codpessoa'])) {
            $qry->where('codpessoa', '=', $filters['codpessoa']);
        }

         if (!empty($filters['codcontacontabil'])) {
            $qry->where('codcontacontabil', '=', $filters['codcontacontabil']);
        }

         if (!empty($filters['numero'])) {
            $qry->palavras('numero', $filters['numero']);
        }

         if (!empty($filters['fatura'])) {
            $qry->palavras('fatura', $filters['fatura']);
        }

         if (!empty($filters['transacao'])) {
            $qry->where('transacao', '=', $filters['transacao']);
        }

         if (!empty($filters['sistema'])) {
            $qry->where('sistema', '=', $filters['sistema']);
        }

         if (!empty($filters['emissao'])) {
            $qry->where('emissao', '=', $filters['emissao']);
        }

         if (!empty($filters['vencimento'])) {
            $qry->where('vencimento', '=', $filters['vencimento']);
        }

         if (!empty($filters['vencimentooriginal'])) {
            $qry->where('vencimentooriginal', '=', $filters['vencimentooriginal']);
        }

         if (!empty($filters['debito'])) {
            $qry->where('debito', '=', $filters['debito']);
        }

         if (!empty($filters['credito'])) {
            $qry->where('credito', '=', $filters['credito']);
        }

         if (!empty($filters['gerencial'])) {
            $qry->where('gerencial', '=', $filters['gerencial']);
        }

         if (!empty($filters['observacao'])) {
            $qry->palavras('observacao', $filters['observacao']);
        }

         if (!empty($filters['boleto'])) {
            $qry->where('boleto', '=', $filters['boleto']);
        }

         if (!empty($filters['nossonumero'])) {
            $qry->palavras('nossonumero', $filters['nossonumero']);
        }

         if (!empty($filters['debitototal'])) {
            $qry->where('debitototal', '=', $filters['debitototal']);
        }

         if (!empty($filters['creditototal'])) {
            $qry->where('creditototal', '=', $filters['creditototal']);
        }

         if (!empty($filters['saldo'])) {
            $qry->where('saldo', '=', $filters['saldo']);
        }

         if (!empty($filters['debitosaldo'])) {
            $qry->where('debitosaldo', '=', $filters['debitosaldo']);
        }

         if (!empty($filters['creditosaldo'])) {
            $qry->where('creditosaldo', '=', $filters['creditosaldo']);
        }

         if (!empty($filters['transacaoliquidacao'])) {
            $qry->where('transacaoliquidacao', '=', $filters['transacaoliquidacao']);
        }

         if (!empty($filters['codnegocioformapagamento'])) {
            $qry->where('codnegocioformapagamento', '=', $filters['codnegocioformapagamento']);
        }

         if (!empty($filters['codtituloagrupamento'])) {
            $qry->where('codtituloagrupamento', '=', $filters['codtituloagrupamento']);
        }

         if (!empty($filters['remessa'])) {
            $qry->where('remessa', '=', $filters['remessa']);
        }

         if (!empty($filters['estornado'])) {
            $qry->where('estornado', '=', $filters['estornado']);
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

         if (!empty($filters['codvalecompraformapagamento'])) {
            $qry->where('codvalecompraformapagamento', '=', $filters['codvalecompraformapagamento']);
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
