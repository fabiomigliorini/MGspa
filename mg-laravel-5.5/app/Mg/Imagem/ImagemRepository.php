<?php

namespace Imagem;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Imagem;
use DB;
use Storage;
use Carbon\Carbon;
use App\Libraries\SlimImageCropper\Slim;
use Marca\Marca;
use Usuario\Usuario;

class ImagemRepository
{

    public static function criar($model = null, array $data = null)
    {
        // Busca Codigo
        $seq = DB::select('select nextval(\'tblimagem_codimagem_seq\') as codimagem');
        $model->codimagem = $seq[0]->codimagem;
        $model->arquivo = $model->codimagem .'.jpg';

        // TODO: Remover isto depois que desativar o MGLara
        $model->observacoes = $model->arquivo;

        // Salva o arquivo
        //$data['file']->storeAs('imagens', $model->arquivo);

        Slim::saveFile($data['imagem'], $model->arquivo, $model->directory, false);

        if (!$model->save()) {
            return false;
        }

        if (!empty($data['codproduto'])) {
            $repo = new ProdutoImagemRepository();
            $repo->create([
                'codproduto' => $data['codproduto'],
                'codimagem' => $model->codimagem
            ]);
        }

        if (!empty($data['codsecaoproduto'])) {
            $codsecaoproduto = SecaoProdutoRepository::findOrFail($data['codsecaoproduto']);
            SecaoProdutoRepository::update($codsecaoproduto, [
                'codimagem' => $model->codimagem
            ]);
        }

        if (!empty($data['codfamiliaproduto'])) {
            $codfamiliaproduto = FamiliaProdutoRepository::findOrFail($data['codfamiliaproduto']);
            FamiliaProdutoRepository::update($codfamiliaproduto, [
                'codimagem' => $model->codimagem
            ]);
        }

        if (!empty($data['codgrupoproduto'])) {
            $grupoproduto = GrupoProdutoRepository::findOrFail($data['codgrupoproduto']);
            GrupoProdutoRepository::update($grupoproduto, [
                'codimagem' => $model->codimagem
            ]);
        }

        if (!empty($data['codsubgrupoproduto'])) {
            $subgrupoproduto = SubGrupoProdutoRepository::findOrFail($data['codsubgrupoproduto']);
            SubGrupoProdutoRepository::update($subgrupoproduto, [
                'codimagem' => $model->codimagem
            ]);
        }

        if (!empty($data['codmarca'])) {
            $marca = Marca::findOrFail($data['codmarca']);
            $marca->codimagem = $model->codimagem;
            $marca->save();
        }

        if (!empty($data['codusuario'])) {
            $usuario = Usuario::findOrFail($data['codusuario']);
            $usuario->codimagem = $model->codimagem;
            $usuario->save();
        }

        return $model;
    }

    public static function atualizar($model, array $data = null)
    {
        static::inativarImagem($model, $data);

        // if (!empty($data)) {
        //     $model->fill($data);
        // }

        if (!$model->save()) {
            return false;
        }

        static::criar($model, $data);
        return $model;
    }

    public static function inativarImagem ($model, $data) {
        $model->inativo = Carbon::now();
        $model->save();

        // Limpa relacionamento com Usuario
        foreach ($model->UsuarioS as $row) {
            $usuario = Usuario::findOrFail($data['codusuario']);
            $usuario->codimagem = null;
            $usuario->save();
        }

        // Limpa relacionamento com Marca
        foreach ($model->MarcaS as $row) {
            $marca = Marca::findOrFail($data['codmarca']);
            $marca->codimagem = null;
            $marca->save();
        }

        return $model;
    }
}
