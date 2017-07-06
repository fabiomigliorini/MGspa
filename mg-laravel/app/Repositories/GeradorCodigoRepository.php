<?php

namespace App\Repositories;

use DB;
use Route;

use RecursiveDirectoryIterator;

class GeradorCodigoRepository
{
    public static function caminhoModel($arquivo = '')
    {
        if (!empty($arquivo)) {
            if (!strstr(strtolower($arquivo), '.php')) {
                $arquivo .= '.php';
            }
        }
        return app_path() . '/Models/' . $arquivo;
    }

    public static function caminhoRepository($arquivo = '')
    {
        if (!empty($arquivo)) {
            if (!strstr(strtolower($arquivo), 'Repository.php')) {
                $arquivo .= 'Repository.php';
            }
        }
        return app_path() . '/Repositories/' . $arquivo;
    }

    public static function caminhoController($arquivo = '')
    {
        if (!empty($arquivo)) {
            if (!strstr(strtolower($arquivo), 'Controller.php')) {
                $arquivo .= 'Controller.php';
            }
        }
        return app_path() . '/Http/Controllers/' . $arquivo;
    }

    public static function buscaArquivoModel($tabela)
    {
        $path = static::caminhoModel();
        $files = glob("$path*");
        $model = str_replace('tbl', '', $tabela);
        $arquivos =  preg_grep("/\/$model\.php$/i", $files);
        foreach ($arquivos as $arquivo) {
            return str_replace('.php', '', str_replace($path, '', $arquivo));
        }
        return ucfirst(strtolower($model));
    }

    public static function buscaCamposTabela($tabela)
    {
        $sql = "
            SELECT
                    column_name
                  , data_type
                  , udt_name
                  , character_maximum_length
                  , numeric_precision
                  , numeric_scale
                  , is_nullable
                  , column_default
            FROM information_schema.columns
            WHERE table_name = '{$tabela}'
            --ORDER BY column_name
        ";

        $cols = DB::select($sql);

        foreach ($cols as $col) {
            $tipo = trim($col->data_type);

            if (strstr($tipo, ' ')) {
                $tipo = $col->udt_name;
            }

            switch ($tipo) {
                case 'numeric':
                        $tipo .= "({$col->numeric_precision},{$col->numeric_scale})";
                        break;
                case 'varchar':
                        $tipo .= "({$col->character_maximum_length})";
                        break;
            }

            $col->tipo = $tipo;


            $comentario = ($col->is_nullable == 'NO')?'NOT NULL':'';
            if (!empty($col->column_default)) {
                $comentario .= ' DEFAULT ' . $col->column_default;
            }
            $col->comentario = trim($comentario);
        }

        return $cols;
    }


    public static function buscaChavesEstrangeiras($tabela)
    {
        $sql = "
            SELECT
                  ccu.table_name AS foreign_table_name
                , ccu.column_name AS foreign_column_name
                , kcu.column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
            WHERE constraint_type = 'FOREIGN KEY'
            AND tc.table_name='{$tabela}';
        ";

        $pais = DB::select($sql);

        // Busca nome dos arquivos de model
        foreach ($pais as $pai) {
            $pai->model = static::buscaArquivoModel($pai->foreign_table_name);

            switch ($pai->column_name) {

                case 'codusuariocriacao':
                    $pai->propriedade = 'UsuarioCriacao';
                    break;

                case 'codusuarioalteracao':
                    $pai->propriedade = 'UsuarioAlteracao';
                    break;

                default:
                    $pai->propriedade = $pai->model;
                    break;
            }
        }

        return $pais;
    }

    public static function buscaChavesEstrangeirasFilhas($tabela)
    {
        $sql = "
            SELECT
                  tc.table_name AS foreign_table_name
                , kcu.column_name AS foreign_column_name
                , ccu.column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
            WHERE constraint_type = 'FOREIGN KEY'
            AND ccu.table_name='{$tabela}';
        ";

        $filhas = DB::select($sql);

        // Busca nome dos arquivos de model
        foreach ($filhas as $filha) {
            $filha->model = static::buscaArquivoModel($filha->foreign_table_name);
        }

        return $filhas;
    }

    public static function montaValidacoes($cols, $instancia_model)
    {
        $validacoes = [];
        foreach ($cols as $col) {
            if (in_array($col->column_name, [$instancia_model->getKeyName(), 'codusuariocriacao', 'codusuarioalteracao', 'alteracao', 'criacao', 'inativo'])) {
                continue;
            }
            switch ($col->udt_name) {
                case 'varchar':
                    $validacoes[$col->column_name]['max'] = [
                        'rule' => "max:$col->character_maximum_length",
                        'mensagem' => "O campo \"$col->column_name\" não pode conter mais que $col->character_maximum_length caracteres!",
                    ];
                    break;

                case 'date':
                case 'timestamp':
                    $validacoes[$col->column_name]['date'] = [
                        'rule' => 'date',
                        'mensagem' => "O campo \"$col->column_name\" deve ser uma data!",
                    ];
                    break;

                case 'bool':
                    $validacoes[$col->column_name]['boolean'] = [
                        'rule' => 'boolean',
                        'mensagem' => "O campo \"$col->column_name\" deve ser um verdadeiro/falso (booleano)!",
                    ];
                    break;

                case 'numeric':
                case 'float8':
                case 'int8':
                case 'int4':
                case 'int2':
                    $validacoes[$col->column_name]['numeric'] = [
                        'rule' => 'numeric',
                        'mensagem' => "O campo \"$col->column_name\" deve ser um número!",
                    ];
                    break;

                default:
                    dd($col);
            }
            if ($col->is_nullable == 'NO') {
                $validacoes[$col->column_name]['required'] = [
                    'rule' => 'required',
                    'mensagem' => "O campo \"$col->column_name\" deve ser preenchido!",
                ];
            } else {
                $validacoes[$col->column_name]['nullable'] = [
                    'rule' => 'nullable',
                ];
            }
        }

        return $validacoes;
    }

    public static function geraModel($tabela, $model)
    {
        $cols = static::buscaCamposTabela($tabela);
        $pais = static::buscaChavesEstrangeiras($tabela);
        $filhas = static::buscaChavesEstrangeirasFilhas($tabela);

        return view('gerador-codigo.arquivos.model', compact('tabela', 'model', 'cols', 'pais', 'filhas'))->render();
    }

    public static function geraRepository($tabela, $model)
    {
        $cols = static::buscaCamposTabela($tabela);

        $instancia_model = "\\App\\Models\\{$model}";
        $instancia_model = new $instancia_model();

        $validacoes = static::montaValidacoes($cols, $instancia_model);

        return view('gerador-codigo.arquivos.repository', compact('model', 'validacoes'))->render();
    }

    public static function geraController($model)
    {
        return view('gerador-codigo.arquivos.controller', compact('model'))->render();
    }

    public static function salvaArquivo($arquivo, $conteudo)
    {
        if (file_exists($arquivo)) {
            $i = 1;
            while (file_exists("{$arquivo}.old.{$i}")) {
                $i++;
            }
            rename($arquivo, "{$arquivo}.old.{$i}");
        }
        if (file_put_contents($arquivo, $conteudo)) {
            chmod($arquivo, 0666);
            return true;
        }
        return false;
    }

    public static function salvaModel($tabela, $model)
    {
        $conteudo = static::geraModel($tabela, $model);
        $arquivo = static::caminhoModel($model);
        return (static::salvaArquivo($arquivo, $conteudo));
    }

    public static function salvaRepository($tabela, $model)
    {
        $conteudo = static::geraRepository($tabela, $model);
        $arquivo = static::caminhoRepository($model);
        return (static::salvaArquivo($arquivo, $conteudo));
    }

    public static function salvaController($model)
    {
        $conteudo = static::geraController($model);
        $arquivo = static::caminhoController($model);
        return (static::salvaArquivo($arquivo, $conteudo));
    }

    public static function stringRegistroRota($model, $url)
    {
        return "Route::resource('{$url}', '{$model}Controller');";
    }

    public static function verificaRegistroRota($url)
    {
        $routes = Route::getRoutes();
        foreach ($routes as $route) {
            if (in_array($route->getName(), ["$url.index", "$url.show", "$url.create", "$url.edit", "$url.update", "$url.store"])) {
                return true;
            }
        }
        return false;
    }

    public static function registraRota($model, $url)
    {
        if (static::verificaRegistroRota($url)) {
            return true;
        }
        $arquivo = base_path('routes/api.php');
        $linhas = file($arquivo);
        $saida = [];
        foreach ($linhas as $linha) {
            $saida[] = $linha;
            if (strpos($linha, '// Rotas Dinamicas')) {
                $saida[] = "\n";
                $saida[] = "    // {$model}\n";
                $saida[] = "    " . static::stringRegistroRota($model, $url) . "\n";
            }
        }
        file_put_contents($arquivo, $saida);
        return true;
    }


    public static function arquivosOld()
    {
        function glob_recursive($directory, &$directories = array())
        {
            foreach (glob($directory, GLOB_ONLYDIR | GLOB_NOSORT) as $folder) {
                $directories[] = $folder;
                glob_recursive("{$folder}/*", $directories);
            }
        }
        glob_recursive(base_path(), $directories);
        $files = array();
        foreach ($directories as $directory) {
            foreach (glob("{$directory}/*.old.*") as $file) {
                $files[] = $file;
            }
        }
        return $files;
    }
}
