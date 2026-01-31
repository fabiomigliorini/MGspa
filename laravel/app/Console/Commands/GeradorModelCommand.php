<?php

namespace App\Console\Commands;

use App\User;
use App\DripEmailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;


class GeradorModelCommand extends Command
{
    protected $signature = 'gerador:model {tabela}';
    protected $description = 'Gera o codigo fonte de um model';

    protected $namespace;
    protected $classe;
    protected $tabela;
    protected $campocodigo;
    protected $conteudo;
    protected $arquivo;

    public function __construct()
    {
        parent::__construct();
    }

    public function inicializarParametros()
    {
        $this->tabela = $this->argument('tabela');

        // carrega arquivo de Indice
        $this->arquivoIndice = base_path('app/Mg/IndiceModels.json');
        if (file_exists($this->arquivoIndice)) {
            $this->models = json_decode(file_get_contents($this->arquivoIndice), true);
        } else {
            $this->models = [];
        }

        $model = $this->descobrirModel($this->tabela);

        $this->namespace = $model['namespace'];
        $this->campocodigo = $model['campocodigo'];
        $this->classe = $model['classe'];
        $this->arquivo = $model['arquivo'];
    }

    public function descobrirCampoCodigo($tabela)
    {
        $sql = 'SELECT a.attname
                FROM   pg_index i
                JOIN   pg_attribute a ON a.attrelid = i.indrelid
                AND a.attnum = ANY(i.indkey)
                WHERE  i.indrelid = :tabela::regclass
                AND    i.indisprimary';
        $codigos = DB::select($sql, [
            'tabela' => $tabela
        ]);
        foreach ($codigos as $codigo) {
            return $codigo->attname;
        }
    }

    public function salvarArquivo()
    {
        // se diretornio nao existe, cria
        $caminho = dirname($this->arquivo);
        if (!is_dir($caminho)) {
            mkdir($caminho, '0775', true);
        }
        // grava o arquivo
        $code = $this->conteudo['Cabecalho'];
        $code .= $this->conteudo['Fillable'];
        $code .= $this->conteudo['Dates'];
        $code .= $this->conteudo['Casts'];
        if (!empty($this->conteudo['BelongsTo'])) {
            $code .= $this->conteudo['BelongsTo'];
        }
        if (!empty($this->conteudo['HasMany'])) {
            $code .= $this->conteudo['HasMany'];
        }
        $code .= $this->conteudo['Rodape'];
        file_put_contents($this->arquivo, $code);
    }

    public function montarCabecalho()
    {
        $this->conteudo['Cabecalho'] = "<?php
/**
 * Created by php artisan gerador:model.
 * Date: " . date('d/M/Y H:i:s') . "
 */

namespace {$this->namespace};

use Mg\MgModel;
";

        $uses = [];
        foreach ($this->models[$this->tabela]['hasMany'] as $rel) {
            $uses[$rel['tabela']] = $rel['tabela'];
        }
        foreach ($this->models[$this->tabela]['belongsTo'] as $rel) {
            $uses[$rel['tabela']] = $rel['tabela'];
        }
        foreach ($uses as $use) {
            $namespace = $this->models[$use]['namespace'];
            $classe = $this->models[$use]['classe'];
            $this->conteudo['Cabecalho'] .= "use $namespace\\$classe;\n";
        }

        $this->conteudo['Cabecalho'] .= "
class {$this->classe} extends MgModel
{
    protected \$table = '{$this->tabela}';
    protected \$primaryKey = '{$this->campocodigo}';

";
    }

    public function montarRodape()
    {
        $this->conteudo['Rodape'] = "
}";
    }

    public function buscarCamposTabela()
    {
        $sql = "
            SELECT
                column_name as nome,
             	  data_type as tipo
            FROM information_schema.columns
            WHERE table_schema = 'mgsis'
            and table_name = '{$this->tabela}'
            order by column_name
        ";
        $this->colunas = DB::select($sql);
    }

    public function montarFillable()
    {
        $ignorar = [
            $this->campocodigo,
            'criacao',
            'codusuariocriacao',
            'alteracao',
            'codusuarioalteracao',
            // 'inativo'
        ];
        $campos = [];
        foreach ($this->colunas as $coluna) {
            if (in_array($coluna->nome, $ignorar)) {
                continue;
            }
            $campos[] = "'{$coluna->nome}'";
        }
        $campos = implode(',
        ', $campos);

        $this->conteudo['Fillable'] = "
    protected \$fillable = [
        $campos
    ];
";
    }

    public function montarDates()
    {
        $considerar = [
            'date',
            'timestamp without time zone',
        ];
        $campos = [];
        foreach ($this->colunas as $coluna) {
            if (!in_array($coluna->tipo, $considerar)) {
                continue;
            }
            $campos[] = "'{$coluna->nome}'";
        }
        $campos = implode(',
        ', $campos);

        $this->conteudo['Dates'] = "
    protected \$dates = [
        $campos
    ];
";
    }

    public function montarCasts()
    {
        $tipos = [

            'bigint' => 'integer',
            'integer' => 'integer',
            'smallint' => 'integer',

            'boolean' => 'boolean',

            'double precision' => 'float',
            'numeric' => 'float',

        ];
        $campos = [];
        foreach ($this->colunas as $coluna) {
            if (!isset($tipos[$coluna->tipo])) {
                continue;
            }
            $campos[] = "'{$coluna->nome}' => '{$tipos[$coluna->tipo]}'";
        }
        $campos = implode(',
        ', $campos);

        $this->conteudo['Casts'] = "
    protected \$casts = [
        $campos
    ];
";
    }

    public function testar()
    {
        $cmd = "{$this->namespace}\\{$this->classe}";

        // TESTE CONTAGEM REGISTROS
        $this->line('');
        $this->info('Teste contagem Registros na tabela...');
        $count = $cmd::count();
        $this->line("$count Registros na tabela");

        // TESTE INSTANCIAR
        $this->line('');
        $this->info('Teste Instanciar Classe...');
        if ($count > 0) {
            $obj = $cmd::first();
        } else {
            $obj = new $cmd();
        }
        print_r($obj->getAttributes());
        $this->line('OK');

        // TESTE RELACIONAMENTOS BELONGS TO
        $this->line('');
        $this->info('Teste de Relacionamentos belongsTo...');
        foreach ($this->models[$this->tabela]['belongsTo'] as $relacao) {
            $metodo = $relacao['metodo'];
            $this->info("{$this->classe}::$metodo()...");
            $count = $obj->$metodo()->count();
            $this->line("$count registros - OK");
        }

        // TESTES RELACIONAMENTOS HAS MANY
        $this->line('');
        $this->info('Teste de Relacionamentos hasMany...');
        foreach ($this->models[$this->tabela]['hasMany'] as $relacao) {
            $metodo = $relacao['metodo'];
            $this->info("{$this->classe}::$metodo()...");
            $count = $obj->$metodo()->count();
            $this->line("$count registros");
        }
    }

    public function montarChavesExtrangeiras()
    {
        $sql = '
          SELECT
              kcu.column_name as coluna,
              ccu.table_name AS tabela,
              ccu.column_name AS pk
          FROM
              information_schema.table_constraints AS tc
              JOIN information_schema.key_column_usage
                  AS kcu ON tc.constraint_name = kcu.constraint_name
              JOIN information_schema.constraint_column_usage
                  AS ccu ON ccu.constraint_name = tc.constraint_name
          WHERE constraint_type = \'FOREIGN KEY\'
          and tc.table_name = :tabela
          ORDER BY kcu.column_name
        ';
        $chaves = DB::select($sql, [
            'tabela' => $this->tabela
        ]);
        foreach ($chaves as $chave) {
            $this->montarBelongsTo($chave->coluna, $chave->tabela, $chave->pk);
        }

        $sql = '
          SELECT
              tc.table_name as tabela,
              kcu.column_name as coluna,
              ccu.column_name AS pk
          FROM
              information_schema.table_constraints AS tc
              JOIN information_schema.key_column_usage
                  AS kcu ON tc.constraint_name = kcu.constraint_name
              JOIN information_schema.constraint_column_usage
                  AS ccu ON ccu.constraint_name = tc.constraint_name
          WHERE constraint_type = \'FOREIGN KEY\'
          and ccu.table_name = :tabela
          ORDER BY tc.table_name, kcu.column_name
        ';
        $chaves = DB::select($sql, [
            'tabela' => $this->tabela
        ]);
        foreach ($chaves as $chave) {
            $this->montarHasMany($chave->coluna, $chave->tabela, $chave->pk);
        }
    }

    public function descobrirModel($tabela)
    {
        if (@$model = $this->models[$tabela]) {
            return $model;
        }
        $namespace = "Mg";
        $palavras = explode('_', $tabela);
        $class = [];
        foreach ($palavras as $palavra) {
            $classe[] = Str::singular($palavra);
        }
        $classe = implode(' ', $classe);
        $classe = preg_replace('/(tbl(?!.tbl*))/', '', $classe);
        $classe = Str::studly($classe);
        do {
            $this->line('');
            $this->line("A tabela {$tabela} não possui model listado no Indice. Por favor confirme o nome da classe e o namespace.");
            $classe = $this->ask("Qual a Classe para a tabela '{$tabela}'?", $classe);
            $namespace = "$namespace\\$classe";
            $namespace = $this->ask("Qual a Namespace para a tabela '{$tabela}'?", $namespace);
            $arquivo = $this->montarCaminhoArquivoModel($namespace, $classe);
        } while (!$this->confirm("Confirmar {$arquivo}?", true));
        $model = [
            'tabela' => $tabela,
            'campocodigo' => $this->descobrirCampoCodigo($tabela),
            'namespace' => $namespace,
            'classe' => $classe,
            'arquivo' => $arquivo,
            'belongsTo' => [],
            'hasMany' => [],
        ];
        $this->models[$tabela] = $model;
        return $model;
    }

    public function montarCaminhoArquivoModel($namespace, $classe = null)
    {
        // monta caminho diretorio
        $caminho = explode('\\', "app\\$namespace");
        $caminho = base_path(implode('/', $caminho));
        if (empty($classe)) {
            return $caminho;
        }
        return "{$caminho}/{$classe}.php";
    }

    public function sugereNomeMetodoRelacionamento($coluna, $tabela)
    {
        if (isset($this->models[$tabela])) {
            $metodo = $this->models[$tabela]['classe'];
            if ($this->models[$tabela]['campocodigo'] != $coluna) {
                $sufixo = str_replace($this->models[$tabela]['campocodigo'], '', $coluna);
                $metodo = $metodo . Str::studly($sufixo);
                return $metodo;
            }
            return $metodo;
        }
        $metodo = preg_replace('/(cod(?!.cod*))/', '', $coluna);
        $metodo = Str::studly($metodo);
        return $metodo;
    }

    public function descobrirMetodoBelongsTo($coluna, $tabela)
    {
        if (!@$metodo = $this->models[$this->tabela]['belongsTo'][$coluna]['metodo']) {
            switch ($coluna) {
                case 'codusuariocriacao':
                    $metodo = 'UsuarioCriacao';
                    break;

                case 'codusuarioalteracao':
                    $metodo = 'UsuarioAlteracao';
                    break;

                default:
                    $metodo = $this->sugereNomeMetodoRelacionamento($coluna, $tabela);
                    break;
            }
        }
        $metodo = $this->ask("Metodo belongsTo: '{$coluna}'", $metodo);
        $this->models[$this->tabela]['belongsTo'][$coluna] = [
            'tabela' => $tabela,
            'coluna' => $coluna,
            'metodo' => $metodo
        ];
        return $metodo;
    }

    public function montarBelongsTo($coluna, $tabela, $pk)
    {
        $model = $this->descobrirModel($tabela);
        $metodo = $this->descobrirMetodoBelongsTo($coluna, $tabela);
        if (empty($this->conteudo['BelongsTo'])) {
            $this->conteudo['BelongsTo'] = "\n\n    // Chaves Estrangeiras";
        }
        $this->conteudo['BelongsTo'] .= "
    public function {$metodo}()
    {
        return \$this->belongsTo({$model['classe']}::class, '{$coluna}', '{$pk}');
    }
";
    }

    public function descobrirMetodoHasMany($coluna, $tabela)
    {
        $chave = "{$tabela}.{$coluna}";
        if (!@$metodo = $this->models[$this->tabela]['hasMany'][$chave]['metodo']) {
            if (!@$metodo = $this->models[$tabela]['classe']) {
                $metodo = preg_replace('/(tbl(?!.tbl*))/', '', $tabela);
                $metodo = Str::studly($metodo);
            }
            $metodo .= "S";
        }
        $metodo = $this->ask("Metodo HasMany: '{$chave}'", $metodo);
        $this->models[$this->tabela]['hasMany'][$chave] = [
            'tabela' => $tabela,
            'coluna' => $coluna,
            'metodo' => $metodo
        ];
        return $metodo;
    }

    public function montarHasMany($coluna, $tabela, $pk)
    {
        $model = $this->descobrirModel($tabela);
        $metodo = $this->descobrirMetodoHasMany($coluna, $tabela);
        if (empty($this->conteudo['HasMany'])) {
            $this->conteudo['HasMany'] = "\n\n    // Tabelas Filhas";
        }
        $this->conteudo['HasMany'] .= "
    public function {$metodo}()
    {
        return \$this->hasMany({$model['classe']}::class, '{$coluna}', '{$pk}');
    }
";
    }

    public function salvarIndiceModels()
    {
        $json = json_encode($this->models, JSON_PRETTY_PRINT);
        file_put_contents($this->arquivoIndice, $json);
    }

    public function handle()
    {
        $this->inicializarParametros();
        $this->buscarCamposTabela();
        $this->montarFillable();
        $this->montarDates();
        $this->montarCasts();
        $this->montarChavesExtrangeiras();
        $this->montarCabecalho();
        $this->montarRodape();
        $this->salvarArquivo();
        $this->salvarIndiceModels();
        $this->testar();
    }
}
