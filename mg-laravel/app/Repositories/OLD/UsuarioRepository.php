<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Usuario;

/**
 * Description of UsuarioRepository
 *
 * @property Validator $validator
 * @property Usuario $model
 */
class UsuarioRepository extends MGRepository {

    private $model;

	public function __construct(Usuario $model)
	{
		$this->model = $model;
	}

    // public function boot() {
    //     $this->model = new Usuario();
    // }

    //put your code here
    public function validate($data = null, $id = null) {

        if (empty($data)) {
            $data = $this->model->getAttributes();
        }

        if (empty($id)) {
            $id = $this->model->codusuario;
        }

        $this->validator = Validator::make($data, [
            'usuario' => ['required','min:2', Rule::unique('tblusuario')->ignore($id, 'codusuario')],
            //'senha' => 'required_if:codusuario,null|min:6',
            'impressoramatricial' => 'required',
            'impressoratermica' => 'required',
        ], [
            'usuario.required' => 'O campo usuário não pode ser vazio!',
            'usuario.unique' => 'Este usuário já esta cadastrado!',
            'impressoramatricial.required' => 'Selecione uma impressora térmica!',
            'impressoratermica.required' => 'Selecione uma impressora matricial!',
        ]);

        return $this->validator->passes();

    }

    public static function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->NegocioS->count() > 0) {
            return 'Usuário sendo utilizada em Negócios!';
        }
        return false;
    }

    public static function search($parametros)
    {
        $query = Usuario::query();

        if(!empty($parametros['codusuario'])) {
            $query->where('codusuario', $parametros['codusuario']);
        }

        if(!empty($parametros['usuario'])) {
            $query->palavras('usuario', $parametros['usuario']);
        }

        if(!empty($parametros['codfilial'])) {
            $query->where('codfilial', $parametros['codfilial']);
        }

        if(!empty($parametros['codpessoa'])) {
            $query->where('codpessoa', $parametros['codpessoa']);
        }

        switch (isset($parametros['ativo']) ? $parametros['ativo']:'9') {
            case 1: //Ativos
                $query->ativo();
                break;
            case 2: //Inativos
                $query->inativo();
                break;
            case 9; //Todos
            default:
        }

        return $query;
    }

}
