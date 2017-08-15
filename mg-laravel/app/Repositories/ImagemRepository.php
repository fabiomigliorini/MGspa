<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Imagem;
use DB;
use Storage;
use Carbon\Carbon;

class ImagemRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\Imagem';

    public static function validationRules ($model = null)
    {
        $rules = [
            'observacoes' => [
                'max:200',
                'nullable',
            ],
            'arquivo' => [
                'max:150',
                'nullable',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'observacoes.max' => 'O campo "observacoes" não pode conter mais que 200 caracteres!',
            'arquivo.max' => 'O campo "arquivo" não pode conter mais que 150 caracteres!',
        ];

        return $messages;
    }

    public static function details($model)
    {
        return parent::details ($model);
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        return parent::query ($filter, $sort, $fields);
    }

    public static function create ($model = null, array $data = null)
    {
        if (empty($model)) {
            $model = static::new();
        }

        // Busca Codigo
        $seq = DB::select('select nextval(\'tblimagem_codimagem_seq\') as codimagem');
        $model->codimagem = $seq[0]->codimagem;
        $model->arquivo = $model->codimagem .'.jpg';

        // TODO: Remover isto depois que desativar o MGLara
        $model->observacoes = $model->arquivo;

        // Salva o arquivo
        $data['file']->storeAs('imagens', $model->arquivo);

        if (!$model->save()){
            return false;
        }

        if (!empty($data['codproduto'])) {
            $repo = new ProdutoImagemRepository();
            $repo->create([
                'codproduto' => $data['codproduto'],
                'codimagem' => $this->model->codimagem
            ]);
        }

        if (!empty($data['codsecaoproduto'])) {
            $codsecaoproduto = SecaoProdutoRepository::findOrFail($data['codsecaoproduto']);
            SecaoProdutoRepository::update($codsecaoproduto, [
                'codimagem' => $this->model->codimagem
            ]);
        }

        if (!empty($data['codfamiliaproduto'])) {
            $codfamiliaproduto = FamiliaProdutoRepository::findOrFail($data['codfamiliaproduto']);
            FamiliaProdutoRepository::update($codfamiliaproduto, [
                'codimagem' => $this->model->codimagem
            ]);
        }

        if (!empty($data['codgrupoproduto'])) {
            $grupoproduto = GrupoProdutoRepository::findOrFail($data['codgrupoproduto']);
            GrupoProdutoRepository::update($grupoproduto, [
                'codimagem' => $this->model->codimagem
            ]);
        }

        if (!empty($data['codsubgrupoproduto'])) {
            $subgrupoproduto = SubGrupoProdutoRepository::findOrFail($data['codsubgrupoproduto']);
            SubGrupoProdutoRepository::update($subgrupoproduto, [
                'codimagem' => $this->model->codimagem
            ]);
        }

        if (!empty($data['codmarca'])) {
            $marca = MarcaRepository::findOrFail($data['codmarca']);
            MarcaRepository::update($marca, [
                'codimagem' => $this->model->codimagem
            ]);
        }

        if (!empty($data['codusuario'])) {
            $usuario = UsuarioRepository::findOrFail($data['codusuario']);
            UsuarioRepository::update($usuario, [
                'codimagem' => $model->codimagem
            ]);
        }

        return $model;
    }

    public static function update($model, array $data = null)
    {
        if (!empty($data)) {
            static::fill($model, $data);
        }

        static::inactivateImagem($model, $data);

        if (!$model->save()) {
            return false;
        }

        static::create(null, $data);
        return $model;
    }

    public static function inactivateImagem ($model, $data) {
        $model->inativo = Carbon::now();
        $model->save();

        // Limpa relacionamento com Usuario
        foreach ($model->UsuarioS as $row) {
            $usuario = UsuarioRepository::findOrFail($data['codusuario']);
            UsuarioRepository::update($usuario, [
                'codimagem' => null
            ]);
        }

        return $model;
    }
}
