<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\TipoMovimentoTitulo;

/**
 * Description of TipoMovimentoTituloRepository
 * 
 * @property  Validator $validator
 * @property  TipoMovimentoTitulo $model
 */
class TipoMovimentoTituloRepository extends MGRepository {
    
    public function boot() {
        $this->model = new TipoMovimentoTitulo();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codtipomovimentotitulo;
        }
        
        $this->validator = Validator::make($data, [
            'tipomovimentotitulo' => [
                'max:20',
                'nullable',
            ],
            'implantacao' => [
                'boolean',
                'required',
            ],
            'ajuste' => [
                'boolean',
                'required',
            ],
            'armotizacao' => [
                'boolean',
                'required',
            ],
            'juros' => [
                'boolean',
                'required',
            ],
            'desconto' => [
                'boolean',
                'required',
            ],
            'pagamento' => [
                'boolean',
                'required',
            ],
            'estorno' => [
                'boolean',
                'required',
            ],
            'observacao' => [
                'max:255',
                'nullable',
            ],
        ], [
            'tipomovimentotitulo.max' => 'O campo "tipomovimentotitulo" não pode conter mais que 20 caracteres!',
            'implantacao.boolean' => 'O campo "implantacao" deve ser um verdadeiro/falso (booleano)!',
            'implantacao.required' => 'O campo "implantacao" deve ser preenchido!',
            'ajuste.boolean' => 'O campo "ajuste" deve ser um verdadeiro/falso (booleano)!',
            'ajuste.required' => 'O campo "ajuste" deve ser preenchido!',
            'armotizacao.boolean' => 'O campo "armotizacao" deve ser um verdadeiro/falso (booleano)!',
            'armotizacao.required' => 'O campo "armotizacao" deve ser preenchido!',
            'juros.boolean' => 'O campo "juros" deve ser um verdadeiro/falso (booleano)!',
            'juros.required' => 'O campo "juros" deve ser preenchido!',
            'desconto.boolean' => 'O campo "desconto" deve ser um verdadeiro/falso (booleano)!',
            'desconto.required' => 'O campo "desconto" deve ser preenchido!',
            'pagamento.boolean' => 'O campo "pagamento" deve ser um verdadeiro/falso (booleano)!',
            'pagamento.required' => 'O campo "pagamento" deve ser preenchido!',
            'estorno.boolean' => 'O campo "estorno" deve ser um verdadeiro/falso (booleano)!',
            'estorno.required' => 'O campo "estorno" deve ser preenchido!',
            'observacao.max' => 'O campo "observacao" não pode conter mais que 255 caracteres!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->MovimentoTituloS->count() > 0) {
            return 'Tipo Movimento Titulo sendo utilizada em "MovimentoTitulo"!';
        }
        
        if ($this->model->TipotituloS->count() > 0) {
            return 'Tipo Movimento Titulo sendo utilizada em "Tipotitulo"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = TipoMovimentoTitulo::query();
        
        // Filtros
         if (!empty($filters['codtipomovimentotitulo'])) {
            $qry->where('codtipomovimentotitulo', '=', $filters['codtipomovimentotitulo']);
        }

         if (!empty($filters['tipomovimentotitulo'])) {
            $qry->palavras('tipomovimentotitulo', $filters['tipomovimentotitulo']);
        }

         if (!empty($filters['implantacao'])) {
            $qry->where('implantacao', '=', $filters['implantacao']);
        }

         if (!empty($filters['ajuste'])) {
            $qry->where('ajuste', '=', $filters['ajuste']);
        }

         if (!empty($filters['armotizacao'])) {
            $qry->where('armotizacao', '=', $filters['armotizacao']);
        }

         if (!empty($filters['juros'])) {
            $qry->where('juros', '=', $filters['juros']);
        }

         if (!empty($filters['desconto'])) {
            $qry->where('desconto', '=', $filters['desconto']);
        }

         if (!empty($filters['pagamento'])) {
            $qry->where('pagamento', '=', $filters['pagamento']);
        }

         if (!empty($filters['estorno'])) {
            $qry->where('estorno', '=', $filters['estorno']);
        }

         if (!empty($filters['observacao'])) {
            $qry->palavras('observacao', $filters['observacao']);
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
            , 'recordsTotal' => TipoMovimentoTitulo::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
