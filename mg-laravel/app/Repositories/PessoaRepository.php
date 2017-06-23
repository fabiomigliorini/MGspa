<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Pessoa;

/**
 * Description of PessoaRepository
 * 
 * @property  Validator $validator
 * @property  Pessoa $model
 */
class PessoaRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Pessoa();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codpessoa;
        }
        
        $this->validator = Validator::make($data, [
            'pessoa' => [
                'max:100',
                'required',
            ],
            'fantasia' => [
                'max:50',
                'required',
            ],
            'cliente' => [
                'boolean',
                'required',
            ],
            'fornecedor' => [
                'boolean',
                'required',
            ],
            'fisica' => [
                'boolean',
                'required',
            ],
            'codsexo' => [
                'numeric',
                'nullable',
            ],
            'cnpj' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'ie' => [
                'max:20',
                'nullable',
            ],
            'consumidor' => [
                'boolean',
                'required',
            ],
            'contato' => [
                'max:100',
                'nullable',
            ],
            'codestadocivil' => [
                'numeric',
                'nullable',
            ],
            'conjuge' => [
                'max:100',
                'nullable',
            ],
            'endereco' => [
                'max:100',
                'nullable',
            ],
            'numero' => [
                'max:10',
                'nullable',
            ],
            'complemento' => [
                'max:50',
                'nullable',
            ],
            'codcidade' => [
                'numeric',
                'nullable',
            ],
            'bairro' => [
                'max:50',
                'nullable',
            ],
            'cep' => [
                'max:8',
                'nullable',
            ],
            'enderecocobranca' => [
                'max:100',
                'nullable',
            ],
            'numerocobranca' => [
                'max:10',
                'nullable',
            ],
            'complementocobranca' => [
                'max:50',
                'nullable',
            ],
            'codcidadecobranca' => [
                'numeric',
                'nullable',
            ],
            'bairrocobranca' => [
                'max:50',
                'nullable',
            ],
            'cepcobranca' => [
                'max:8',
                'nullable',
            ],
            'telefone1' => [
                'max:50',
                'nullable',
            ],
            'telefone2' => [
                'max:50',
                'nullable',
            ],
            'telefone3' => [
                'max:50',
                'nullable',
            ],
            'email' => [
                'max:100',
                'nullable',
            ],
            'emailnfe' => [
                'max:100',
                'nullable',
            ],
            'emailcobranca' => [
                'max:100',
                'nullable',
            ],
            'codformapagamento' => [
                'numeric',
                'nullable',
            ],
            'credito' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'creditobloqueado' => [
                'boolean',
                'required',
            ],
            'observacoes' => [
                'max:255',
                'nullable',
            ],
            'mensagemvenda' => [
                'max:500',
                'nullable',
            ],
            'vendedor' => [
                'boolean',
                'required',
            ],
            'rg' => [
                'max:30',
                'nullable',
            ],
            'desconto' => [
                'digits',
                'numeric',
                'nullable',
            ],
            'notafiscal' => [
                'numeric',
                'required',
            ],
            'toleranciaatraso' => [
                'numeric',
                'required',
            ],
            'codgrupocliente' => [
                'numeric',
                'nullable',
            ],
        ], [
            'pessoa.max' => 'O campo "pessoa" não pode conter mais que 100 caracteres!',
            'pessoa.required' => 'O campo "pessoa" deve ser preenchido!',
            'fantasia.max' => 'O campo "fantasia" não pode conter mais que 50 caracteres!',
            'fantasia.required' => 'O campo "fantasia" deve ser preenchido!',
            'cliente.boolean' => 'O campo "cliente" deve ser um verdadeiro/falso (booleano)!',
            'cliente.required' => 'O campo "cliente" deve ser preenchido!',
            'fornecedor.boolean' => 'O campo "fornecedor" deve ser um verdadeiro/falso (booleano)!',
            'fornecedor.required' => 'O campo "fornecedor" deve ser preenchido!',
            'fisica.boolean' => 'O campo "fisica" deve ser um verdadeiro/falso (booleano)!',
            'fisica.required' => 'O campo "fisica" deve ser preenchido!',
            'codsexo.numeric' => 'O campo "codsexo" deve ser um número!',
            'cnpj.digits' => 'O campo "cnpj" deve conter no máximo 0 dígitos!',
            'cnpj.numeric' => 'O campo "cnpj" deve ser um número!',
            'ie.max' => 'O campo "ie" não pode conter mais que 20 caracteres!',
            'consumidor.boolean' => 'O campo "consumidor" deve ser um verdadeiro/falso (booleano)!',
            'consumidor.required' => 'O campo "consumidor" deve ser preenchido!',
            'contato.max' => 'O campo "contato" não pode conter mais que 100 caracteres!',
            'codestadocivil.numeric' => 'O campo "codestadocivil" deve ser um número!',
            'conjuge.max' => 'O campo "conjuge" não pode conter mais que 100 caracteres!',
            'endereco.max' => 'O campo "endereco" não pode conter mais que 100 caracteres!',
            'numero.max' => 'O campo "numero" não pode conter mais que 10 caracteres!',
            'complemento.max' => 'O campo "complemento" não pode conter mais que 50 caracteres!',
            'codcidade.numeric' => 'O campo "codcidade" deve ser um número!',
            'bairro.max' => 'O campo "bairro" não pode conter mais que 50 caracteres!',
            'cep.max' => 'O campo "cep" não pode conter mais que 8 caracteres!',
            'enderecocobranca.max' => 'O campo "enderecocobranca" não pode conter mais que 100 caracteres!',
            'numerocobranca.max' => 'O campo "numerocobranca" não pode conter mais que 10 caracteres!',
            'complementocobranca.max' => 'O campo "complementocobranca" não pode conter mais que 50 caracteres!',
            'codcidadecobranca.numeric' => 'O campo "codcidadecobranca" deve ser um número!',
            'bairrocobranca.max' => 'O campo "bairrocobranca" não pode conter mais que 50 caracteres!',
            'cepcobranca.max' => 'O campo "cepcobranca" não pode conter mais que 8 caracteres!',
            'telefone1.max' => 'O campo "telefone1" não pode conter mais que 50 caracteres!',
            'telefone2.max' => 'O campo "telefone2" não pode conter mais que 50 caracteres!',
            'telefone3.max' => 'O campo "telefone3" não pode conter mais que 50 caracteres!',
            'email.max' => 'O campo "email" não pode conter mais que 100 caracteres!',
            'emailnfe.max' => 'O campo "emailnfe" não pode conter mais que 100 caracteres!',
            'emailcobranca.max' => 'O campo "emailcobranca" não pode conter mais que 100 caracteres!',
            'codformapagamento.numeric' => 'O campo "codformapagamento" deve ser um número!',
            'credito.digits' => 'O campo "credito" deve conter no máximo 2 dígitos!',
            'credito.numeric' => 'O campo "credito" deve ser um número!',
            'creditobloqueado.boolean' => 'O campo "creditobloqueado" deve ser um verdadeiro/falso (booleano)!',
            'creditobloqueado.required' => 'O campo "creditobloqueado" deve ser preenchido!',
            'observacoes.max' => 'O campo "observacoes" não pode conter mais que 255 caracteres!',
            'mensagemvenda.max' => 'O campo "mensagemvenda" não pode conter mais que 500 caracteres!',
            'vendedor.boolean' => 'O campo "vendedor" deve ser um verdadeiro/falso (booleano)!',
            'vendedor.required' => 'O campo "vendedor" deve ser preenchido!',
            'rg.max' => 'O campo "rg" não pode conter mais que 30 caracteres!',
            'desconto.digits' => 'O campo "desconto" deve conter no máximo 2 dígitos!',
            'desconto.numeric' => 'O campo "desconto" deve ser um número!',
            'notafiscal.numeric' => 'O campo "notafiscal" deve ser um número!',
            'notafiscal.required' => 'O campo "notafiscal" deve ser preenchido!',
            'toleranciaatraso.numeric' => 'O campo "toleranciaatraso" deve ser um número!',
            'toleranciaatraso.required' => 'O campo "toleranciaatraso" deve ser preenchido!',
            'codgrupocliente.numeric' => 'O campo "codgrupocliente" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ChequeS->count() > 0) {
            return 'Pessoa sendo utilizada em "Cheque"!';
        }
        
        if ($this->model->MetaFilialPessoaS->count() > 0) {
            return 'Pessoa sendo utilizada em "MetaFilialPessoa"!';
        }
        
        if ($this->model->ValeCompraModeloS->count() > 0) {
            return 'Pessoa sendo utilizada em "ValeCompraModelo"!';
        }
        
        if ($this->model->ValeCompraS->count() > 0) {
            return 'Pessoa sendo utilizada em "ValeCompra"!';
        }
        
        if ($this->model->ValeCompraS->count() > 0) {
            return 'Pessoa sendo utilizada em "ValeCompra"!';
        }
        
        if ($this->model->CobrancaHistoricoS->count() > 0) {
            return 'Pessoa sendo utilizada em "CobrancaHistorico"!';
        }
        
        if ($this->model->CupomfiscalS->count() > 0) {
            return 'Pessoa sendo utilizada em "Cupomfiscal"!';
        }
        
        if ($this->model->FilialS->count() > 0) {
            return 'Pessoa sendo utilizada em "Filial"!';
        }
        
        if ($this->model->LiquidacaotituloS->count() > 0) {
            return 'Pessoa sendo utilizada em "Liquidacaotitulo"!';
        }
        
        if ($this->model->NegocioS->count() > 0) {
            return 'Pessoa sendo utilizada em "Negocio"!';
        }
        
        if ($this->model->NegocioS->count() > 0) {
            return 'Pessoa sendo utilizada em "Negocio"!';
        }
        
        if ($this->model->NfeterceiroS->count() > 0) {
            return 'Pessoa sendo utilizada em "Nfeterceiro"!';
        }
        
        if ($this->model->NotaFiscalS->count() > 0) {
            return 'Pessoa sendo utilizada em "NotaFiscal"!';
        }
        
        if ($this->model->RegistroSpcS->count() > 0) {
            return 'Pessoa sendo utilizada em "RegistroSpc"!';
        }
        
        if ($this->model->TituloagrupamentoS->count() > 0) {
            return 'Pessoa sendo utilizada em "Tituloagrupamento"!';
        }
        
        if ($this->model->TituloS->count() > 0) {
            return 'Pessoa sendo utilizada em "Titulo"!';
        }
        
        if ($this->model->UsuarioS->count() > 0) {
            return 'Pessoa sendo utilizada em "Usuario"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Pessoa::query();
        
        // Filtros
         if (!empty($filters['codpessoa'])) {
            $qry->where('codpessoa', '=', $filters['codpessoa']);
        }

         if (!empty($filters['pessoa'])) {
            $qry->palavras('pessoa', $filters['pessoa']);
        }

         if (!empty($filters['fantasia'])) {
            $qry->palavras('fantasia', $filters['fantasia']);
        }

          if (!empty($filters['cliente'])) {
            $qry->where('cliente', '=', $filters['cliente']);
        }

         if (!empty($filters['fornecedor'])) {
            $qry->where('fornecedor', '=', $filters['fornecedor']);
        }

         if (!empty($filters['fisica'])) {
            $qry->where('fisica', '=', $filters['fisica']);
        }

         if (!empty($filters['codsexo'])) {
            $qry->where('codsexo', '=', $filters['codsexo']);
        }

         if (!empty($filters['cnpj'])) {
            $qry->where('cnpj', '=', $filters['cnpj']);
        }

         if (!empty($filters['ie'])) {
            $qry->palavras('ie', $filters['ie']);
        }

         if (!empty($filters['consumidor'])) {
            $qry->where('consumidor', '=', $filters['consumidor']);
        }

         if (!empty($filters['contato'])) {
            $qry->palavras('contato', $filters['contato']);
        }

         if (!empty($filters['codestadocivil'])) {
            $qry->where('codestadocivil', '=', $filters['codestadocivil']);
        }

         if (!empty($filters['conjuge'])) {
            $qry->palavras('conjuge', $filters['conjuge']);
        }

         if (!empty($filters['endereco'])) {
            $qry->palavras('endereco', $filters['endereco']);
        }

         if (!empty($filters['numero'])) {
            $qry->palavras('numero', $filters['numero']);
        }

         if (!empty($filters['complemento'])) {
            $qry->palavras('complemento', $filters['complemento']);
        }

         if (!empty($filters['codcidade'])) {
            $qry->where('codcidade', '=', $filters['codcidade']);
        }

         if (!empty($filters['bairro'])) {
            $qry->palavras('bairro', $filters['bairro']);
        }

         if (!empty($filters['cep'])) {
            $qry->palavras('cep', $filters['cep']);
        }

         if (!empty($filters['enderecocobranca'])) {
            $qry->palavras('enderecocobranca', $filters['enderecocobranca']);
        }

         if (!empty($filters['numerocobranca'])) {
            $qry->palavras('numerocobranca', $filters['numerocobranca']);
        }

         if (!empty($filters['complementocobranca'])) {
            $qry->palavras('complementocobranca', $filters['complementocobranca']);
        }

         if (!empty($filters['codcidadecobranca'])) {
            $qry->where('codcidadecobranca', '=', $filters['codcidadecobranca']);
        }

         if (!empty($filters['bairrocobranca'])) {
            $qry->palavras('bairrocobranca', $filters['bairrocobranca']);
        }

         if (!empty($filters['cepcobranca'])) {
            $qry->palavras('cepcobranca', $filters['cepcobranca']);
        }

         if (!empty($filters['telefone1'])) {
            $qry->palavras('telefone1', $filters['telefone1']);
        }

         if (!empty($filters['telefone2'])) {
            $qry->palavras('telefone2', $filters['telefone2']);
        }

         if (!empty($filters['telefone3'])) {
            $qry->palavras('telefone3', $filters['telefone3']);
        }

         if (!empty($filters['email'])) {
            $qry->palavras('email', $filters['email']);
        }

         if (!empty($filters['emailnfe'])) {
            $qry->palavras('emailnfe', $filters['emailnfe']);
        }

         if (!empty($filters['emailcobranca'])) {
            $qry->palavras('emailcobranca', $filters['emailcobranca']);
        }

         if (!empty($filters['codformapagamento'])) {
            $qry->where('codformapagamento', '=', $filters['codformapagamento']);
        }

         if (!empty($filters['credito'])) {
            $qry->where('credito', '=', $filters['credito']);
        }

         if (!empty($filters['creditobloqueado'])) {
            $qry->where('creditobloqueado', '=', $filters['creditobloqueado']);
        }

         if (!empty($filters['observacoes'])) {
            $qry->palavras('observacoes', $filters['observacoes']);
        }

         if (!empty($filters['mensagemvenda'])) {
            $qry->palavras('mensagemvenda', $filters['mensagemvenda']);
        }

         if (!empty($filters['vendedor'])) {
            $qry->where('vendedor', '=', $filters['vendedor']);
        }

         if (!empty($filters['rg'])) {
            $qry->palavras('rg', $filters['rg']);
        }

         if (!empty($filters['desconto'])) {
            $qry->where('desconto', '=', $filters['desconto']);
        }

         if (!empty($filters['notafiscal'])) {
            $qry->where('notafiscal', '=', $filters['notafiscal']);
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

         if (!empty($filters['toleranciaatraso'])) {
            $qry->where('toleranciaatraso', '=', $filters['toleranciaatraso']);
        }

         if (!empty($filters['codgrupocliente'])) {
            $qry->where('codgrupocliente', '=', $filters['codgrupocliente']);
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
