<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;

use App\Repositories\UsuarioRepository;

class ProdutoShowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return UsuarioRepository::permitido($this->user(), 'Estoque', UsuarioRepository::PERMISSAO_CONSULTA);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
