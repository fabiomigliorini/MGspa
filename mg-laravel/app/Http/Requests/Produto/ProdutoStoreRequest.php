<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

use App\Models\Usuario;

use App\Repositories\ProdutoRepository;
use App\Repositories\UsuarioRepository;

class ProdutoStoreRequest extends FormRequest
{
    public function authorize(Usuario $user)
    {
        return UsuarioRepository::permitido($this->user(), 'Estoque', UsuarioRepository::PERMISSAO_MANUTENCAO);
    }

    public function rules()
    {
        return ProdutoRepository::rules($this->all());
    }

    public function messages()
    {
        return ProdutoRepository::rulesMessages();
    }
}
