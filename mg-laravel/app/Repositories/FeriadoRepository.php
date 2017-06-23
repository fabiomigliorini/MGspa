<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Feriado;

/**
 * Description of FeriadoRepository
 * 
 * @property  Validator $validator
 * @property  Feriado $model
 */
class FeriadoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Feriado();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codferiado;
        }
        
        $this->validator = Validator::make($data, [
            'data' => [
                'date',
                'required',
            ],
            'feriado' => [
                'max:100',
                'required',
            ],
        ], [
            'data.date' => 'O campo "data" deve ser uma data!',
            'data.required' => 'O campo "data" deve ser preenchido!',
            'feriado.max' => 'O campo "feriado" não pode conter mais que 100 caracteres!',
            'feriado.required' => 'O campo "feriado" deve ser preenchido!',
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
        $qry = Feriado::query();
        
        // Filtros
         if (!empty($filters['codferiado'])) {
            $qry->where('codferiado', '=', $filters['codferiado']);
        }

         if (!empty($filters['data'])) {
            $qry->where('data', '=', $filters['data']);
        }

         if (!empty($filters['feriado'])) {
            $qry->palavras('feriado', $filters['feriado']);
        }

         if (!empty($filters['codusuarioalteracao'])) {
            $qry->where('codusuarioalteracao', '=', $filters['codusuarioalteracao']);
        }

         if (!empty($filters['criacao'])) {
            $qry->where('criacao', '=', $filters['criacao']);
        }

         if (!empty($filters['alteracao'])) {
            $qry->where('alteracao', '=', $filters['alteracao']);
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
        
        $count = $qry->count();
        
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
            , 'recordsTotal' => Feriado::count()
            , 'data' => $qry->get()
        ];        
    }

    /**
     * Retorna se a data é um feriado
     * @param Carbon $data Data para consulta
     * @return boolean
     */
    public function feriado (Carbon $data) {
        if (Feriado::where('data', '=', $data)->count() > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Retorna se a data é um dia útil
     * @param Carbon $data Data para consulta
     * @param bool $sabado_dia_util Considera sabado como dia util ou nao
     * @return boolean
     */
    public function diaUtil (Carbon $data, bool $sabado_dia_util = false) {
        if ($data->dayOfWeek == Carbon::SUNDAY) {
            return false;
        }
        if (!$sabado_dia_util && $data->dayOfWeek == Carbon::SATURDAY) {
            return false;
        }
        return !self::feriado($data);
    }
    
    /**
     * Retorna array com os dias úteis entre a $data_inicial e a $data_final
     * @param Carbon $data_inicial Data Incial
     * @param Carbon $data_final Data Final
     * @param bool $sabado_dia_util Considera sabado como dia util ou nao
     * @return Carbon[]
     */
    public function diasUteis (Carbon $data_inicial, Carbon $data_final, bool $sabado_dia_util = false) {
        if ($data_final->lt($data_inicial)) {
            return false;
        }
        $data = clone $data_inicial;
        $dias_uteis = [];
        
        $feriados = Feriado::where('data', '>=', $data_inicial)->where('data', '<=', $data_final)->get();
        
        while ($data->lte($data_final)) {
            if ($data->dayOfWeek == Carbon::SUNDAY) {
                $data->addDay();
                continue;
            }
            if (!$sabado_dia_util && $data->dayOfWeek == Carbon::SATURDAY) {
                $data->addDay();
                continue;
            }
            if ($feriados->contains('data', $data)) {
                $data->addDay();
                continue;
            }
            $dias_uteis[] = clone $data;
            $data->addDay();
        }
        return $dias_uteis;
    }
    
    /**
     * Retorna número de dias úteis entre a $data_inicial e a $data_final
     * @param Carbon $data_inicial Data Incial
     * @param Carbon $data_final Data Final
     * @param bool $sabado_dia_util Considera sabado como dia util ou nao
     * @return int
     */
    public function numeroDiasUteis (Carbon $data_inicial, Carbon $data_final, bool $sabado_dia_util = false) {
        $ret = self::diasUteis($data_inicial, $data_final, $sabado_dia_util);
        return sizeof($ret);
    }

        /**
     * Retorna Proximo Dia Util
     * @param Carbon $data Data de inicio
     * @param bool $sabado_dia_util Considera sabado como dia util ou nao
     * @return Carbon
     */
    public function proximoDiaUtil(Carbon $data, bool $sabado_dia_util = false) {
        do {
            if (self::diaUtil($data, $sabado_dia_util)) {
                return $data;
            }
            $data->addDay();
            continue;
        } while (true);
    }    
}
