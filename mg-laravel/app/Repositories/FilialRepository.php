<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;


use App\Models\Filial;

/**
 * Description of FilialRepository
 * 
 * @property Validator $validator
 * @property Filial $model
 */
class FilialRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Filial();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $model->getAttributes();
        }
        
        if (empty($id)) {
            $id = $model->codfilial;
        }

        $this->validator = Validator::make($data, [
            //...
        ], [
            //..
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        // ...
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        /*
        // Query da Entidade
        $qry = Filial::query();
        $qry->select([
            'tblusuario.codusuario',
            'tblusuario.inativo', 
            'tblusuario.usuario', 
            'tblpessoa.pessoa', 
            'tblfilial.filial']);
        $qry->leftJoin('tblpessoa', 'tblpessoa.codpessoa', '=', 'tblusuario.codpessoa');
        $qry->leftJoin('tblfilial', 'tblfilial.codfilial', '=', 'tblusuario.codfilial');

        // Filtros
        if (!empty($filters['codusuario'])) {
            $qry->where('tblusuario.codusuario', '=', $filters['codusuario']);
        }
        
        if (!empty($filters['usuario'])) {
            foreach(explode(' ', $filters['usuario']) as $palavra) {
                if (!empty($palavra)) {
                    $qry->where('tblusuario.usuario', 'ilike', "%$palavra%");
                }
            }
        }
        
        if (!empty($filters['codfilial'])) {
            $qry->where('tblusuario.codfilial', '=', $filters['codfilial']);
        }
        
        if (!empty($filters['codpessoa'])) {
            $qry->where('tblusuario.codpessoa', '=', $filters['codpessoa']);
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
         * 
         */
    }
}
