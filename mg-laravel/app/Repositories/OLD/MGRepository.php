<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Gate;
use App\Models\MGModel;
use Carbon\Carbon;
/**
 * Description of Repository
 *
 * @property string $model_class
 * @property Validator $validator
 * @property Array $data
 * @property MGModel $model
 * @property array $sort
 */
abstract class MGRepository {

    protected $model;

    // public function __construct() {
    //     $this->boot();
    //     $this->model_class = get_class($this->model);
    // }

    public function validate($data = null, $id = null) {
        return true;
    }


    /**
     * Verifica se usuario tem permissao
     *
     * @param type $ability
     * @return boolean
     */
    public static function authorize($ability) {
        if (!Gate::allows($ability, $this->model)) {
            abort(403);
        }
        return true;
    }


    /**
     * cria um novo model com base nos atributos
     *
     * @return MGModel
     */
    public static function new($attributes = []) {
        if (!$this->model = new $this->model_class()) {
            return false;
        }
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
        return $this->model;
    }

    /**
     * salva um novo model
     *
     * @param type $data
     * @return boolean
     */
    public static function create($data = null) {

        if (!empty($data)) {
            $this->new($data);
        }

        if ($this->model->exists) {
            return false;
        }

        return $this->model->save();

    }


    /**
     * preenche o model
     *
     * @param type $data
     */
    public static function fill($data) {
        $this->data = $data;
        $this->model->fill($data);
    }

    /**
     * altera o model
     *
     * @param type $id
     * @param type $data
     * @return boolean
     */
    public static function update($id = null, $data = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if (!empty($data)) {
            $this->fill($data);
        }
        return $this->model->save();
    }


    /**
     * Exclui um model
     *
     * @param type $id
     * @return type
     */
    public static function delete($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        return $this->model->delete();
    }


    /**
     * Verifica se o model esta sendo referenciado por outros
     *
     * @param type $id
     * @return boolean
     */
    public static function used($id = null) {
        return false;
    }


    /**
     * Ativa um model
     *
     * @param type $id
     * @return boolean
     */
    public static function activate($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if (empty($this->model->inativo)) {
            return true;
        }
        $this->model->inativo = null;
        return $this->model->save();
    }

    /**
     * inativa um model
     *
     * @param type $id
     * @return boolean
     */
    public static function inactivate($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if (!empty($this->model->inativo)) {
            return true;
        }
        $this->model->inativo = Carbon::now();
        return $this->model->save();
    }

    /**
     * total de registros do model
     *
     * @return integer
     */
    public static function count() {
        return $this->model_class::count();
    }

    /**
     * busca um registro
     *
     * @param integer $id
     * @return MGModel
     */
    public static function findOrFail($id) {
        return $this->model = $this->model_class::findOrFail($id);
    }

    /**
     * busca um registro
     *
     * @param integer $id
     * @return MGModel
     */
    public static function find($id) {
        return $this->model = $this->model_class::find($id);
    }

    public static function save() {
        if ($this->model->exists) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    /**
     *
     * @param array $active 1 - Ativos / 2 - Inativos / 9 - Todos
     * @param array $sort
     */
    public static function all(int $active = 1, array $sort = null)
    {

        $qry = $this->model_class::query();

        switch ($active) {
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

        if (empty($sort) && !empty($this->sort)) {
            $sort = $this->sort;
        }

        if (!empty($sort)) {
            foreach ($sort as $field => $dir) {
                $qry->orderBy($field, $dir);
            }
        }

        return $qry->get();
    }

}
