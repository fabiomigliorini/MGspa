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

        // Limpa relacionamento com SecaoProduto
        foreach ($model->SecaoProdutoS as $model) {
            $data['codsecaoproduto'] = $model->codsecaoproduto;
        }

        // Limpa relacionamento com FamiliaProduto
        foreach ($model->FamiliaProdutoS as $model) {
            $data['codfamiliaproduto'] = $model->codfamiliaproduto;
        }

        // Limpa relacionamento com GrupoProduto
        foreach ($model->GrupoProdutoS as $model) {
            $data['codgrupoproduto'] = $model->codgrupoproduto;
        }

        // Limpa relacionamento com SubGrupoProduto
        foreach ($model->SubGrupoProdutoS as $model) {
            $data['codsubgrupoproduto'] = $model->codsubgrupoproduto;
        }

        // Limpa relacionamento com Marca
        foreach ($model->MarcaS as $model) {
            $data['codmarca'] = $model->codmarca;
        }

        // Limpa relacionamento com ProdutoImagem
        foreach ($model->ProdutoImagemS as $model) {
            $data['codproduto'] = $model->codproduto;
        }

        static::inactivateImagem($model, $data);

        if (!$model->save()) {
            return false;
        }

        return $model->create($model, $data);
    }

    public static function inactivateImagem ($model, $data) {
        $date = Carbon::now();

        // Limpa relacionamento com Usuario
        foreach ($model->UsuarioS as $model) {
            $usuario = UsuarioRepository::findOrFail($data['codusuario']);
            UsuarioRepository::update($usuario, [
                'codimagem' => null
            ]);
        }

        // // Limpa relacionamento com SecaoProduto
        // foreach ($model->SecaoProdutoS as $model) {
        //     $repo = new SecaoProdutoRepository();
        //     $model->codimagem = null;
        //     $repo->model = $model;
        //     $repo->update();
        // }
        //
        // // Limpa relacionamento com FamiliaProduto
        // foreach ($model->FamiliaProdutoS as $model) {
        //     $repo = new FamiliaProdutoRepository();
        //     $model->codimagem = null;
        //     $repo->model = $model;
        //     $repo->update();
        // }
        //
        // // Limpa relacionamento com GrupoProduto
        // foreach ($model->GrupoProdutoS as $model) {
        //     $repo = new GrupoProdutoRepository();
        //     $model->codimagem = null;
        //     $repo->model = $model;
        //     $repo->update();
        // }
        //
        // // Limpa relacionamento com SubGrupoProduto
        // foreach ($model->SubGrupoProdutoS as $model) {
        //     $repo = new SubGrupoProdutoRepository();
        //     $model->codimagem = null;
        //     $repo->model = $model;
        //     $repo->update();
        // }
        //
        // // Limpa relacionamento com Marca
        // foreach ($model->MarcaS as $model) {
        //     $repo = new MarcaRepository();
        //     $model->codimagem = null;
        //     $repo->model = $model;
        //     $repo->update();
        // }
        //
        // // Limpa relacionamento com ProdutoImagem
        // foreach ($model->ProdutoImagemS as $model) {
        //     $repo = new ProdutoImagemRepository();
        //     $repo->model = $model;
        //     $repo->delete();
        // }

        $model->inativo = $date;

        return static::update($model);
    }



    public static function inactivate_($model) {

        if (!empty($model->inativo)) {
            return true;
        }


        $model->inativo = Carbon::now();

        return $model->save();
    }


}
