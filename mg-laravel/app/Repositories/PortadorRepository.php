<?php
namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Portador;

/**
 * Description of PortadorRepository
 * 
 * @property  Validator $validator
 * @property  Portador $model
 */
class PortadorRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Portador();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codportador;
        }
        
        $this->validator = Validator::make($data, [
            'portador' => [
                'size:50',
            ],
            'codbanco' => [
                'numeric',
            ],
            'agencia' => [
                'numeric',
            ],
            'agenciadigito' => [
                'numeric',
            ],
            'conta' => [
                'numeric',
            ],
            'contadigito' => [
                'numeric',
            ],
            'emiteboleto' => [
                'boolean',
                'required',
            ],
            'codfilial' => [
                'numeric',
            ],
            'convenio' => [
                'digits',
                'numeric',
            ],
            'diretorioremessa' => [
                'size:100',
            ],
            'diretorioretorno' => [
                'size:100',
            ],
            'carteira' => [
                'numeric',
            ],
            'alteracao' => [
                'date',
            ],
            'codusuarioalteracao' => [
                'numeric',
            ],
            'criacao' => [
                'date',
            ],
            'codusuariocriacao' => [
                'numeric',
            ],
        ], [
            'portador.size' => 'O campo "portador" não pode conter mais que 50 caracteres!',
            'codbanco.numeric' => 'O campo "codbanco" deve ser um número!',
            'agencia.numeric' => 'O campo "agencia" deve ser um número!',
            'agenciadigito.numeric' => 'O campo "agenciadigito" deve ser um número!',
            'conta.numeric' => 'O campo "conta" deve ser um número!',
            'contadigito.numeric' => 'O campo "contadigito" deve ser um número!',
            'emiteboleto.boolean' => 'O campo "emiteboleto" deve ser um verdadeiro/falso (booleano)!',
            'emiteboleto.required' => 'O campo "emiteboleto" deve ser preenchido!',
            'codfilial.numeric' => 'O campo "codfilial" deve ser um número!',
            'convenio.digits' => 'O campo "convenio" deve conter no máximo 0 dígitos!',
            'convenio.numeric' => 'O campo "convenio" deve ser um número!',
            'diretorioremessa.size' => 'O campo "diretorioremessa" não pode conter mais que 100 caracteres!',
            'diretorioretorno.size' => 'O campo "diretorioretorno" não pode conter mais que 100 caracteres!',
            'carteira.numeric' => 'O campo "carteira" deve ser um número!',
            'alteracao.date' => 'O campo "alteracao" deve ser uma data!',
            'codusuarioalteracao.numeric' => 'O campo "codusuarioalteracao" deve ser um número!',
            'criacao.date' => 'O campo "criacao" deve ser uma data!',
            'codusuariocriacao.numeric' => 'O campo "codusuariocriacao" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ChequeRepasseS->count() > 0) {
            return 'Portador sendo utilizada em "ChequeRepasse"!';
        }
        
        if ($this->model->BoletoretornoS->count() > 0) {
            return 'Portador sendo utilizada em "Boletoretorno"!';
        }
        
        if ($this->model->CobrancaS->count() > 0) {
            return 'Portador sendo utilizada em "Cobranca"!';
        }
        
        if ($this->model->LiquidacaotituloS->count() > 0) {
            return 'Portador sendo utilizada em "Liquidacaotitulo"!';
        }
        
        if ($this->model->MovimentoTituloS->count() > 0) {
            return 'Portador sendo utilizada em "MovimentoTitulo"!';
        }
        
        if ($this->model->TituloS->count() > 0) {
            return 'Portador sendo utilizada em "Titulo"!';
        }
        
        if ($this->model->UsuarioS->count() > 0) {
            return 'Portador sendo utilizada em "Usuario"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Portador::query();
        
        // Filtros
         if (!empty($filters['codportador'])) {
            $qry->where('codportador', '=', $filters['codportador']);
        }

         if (!empty($filters['portador'])) {
            $qry->palavras('portador', $filters['portador']);
        }

         if (!empty($filters['codbanco'])) {
            $qry->where('codbanco', '=', $filters['codbanco']);
        }

         if (!empty($filters['agencia'])) {
            $qry->where('agencia', '=', $filters['agencia']);
        }

         if (!empty($filters['agenciadigito'])) {
            $qry->where('agenciadigito', '=', $filters['agenciadigito']);
        }

         if (!empty($filters['conta'])) {
            $qry->where('conta', '=', $filters['conta']);
        }

         if (!empty($filters['contadigito'])) {
            $qry->where('contadigito', '=', $filters['contadigito']);
        }

         if (!empty($filters['emiteboleto'])) {
            $qry->where('emiteboleto', '=', $filters['emiteboleto']);
        }

         if (!empty($filters['codfilial'])) {
            $qry->where('codfilial', '=', $filters['codfilial']);
        }

         if (!empty($filters['convenio'])) {
            $qry->where('convenio', '=', $filters['convenio']);
        }

         if (!empty($filters['diretorioremessa'])) {
            $qry->palavras('diretorioremessa', $filters['diretorioremessa']);
        }

         if (!empty($filters['diretorioretorno'])) {
            $qry->palavras('diretorioretorno', $filters['diretorioretorno']);
        }

         if (!empty($filters['carteira'])) {
            $qry->where('carteira', '=', $filters['carteira']);
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
