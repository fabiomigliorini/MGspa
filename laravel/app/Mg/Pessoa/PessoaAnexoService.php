<?php

namespace Mg\Pessoa;

// use Illuminate\Support\Facades\Auth;

use Exception;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

// use Mg\Negocio\Negocio;

class PessoaAnexoService
{

    public static function diretorio(int $codpessoa)
    {
        return mascarar(numeroLimpo($codpessoa), '##/##/##/##/');
    }

    public static function response($codpessoa, $status, $arquivo)
    {
        $dir = static::diretorio($codpessoa);
        $caminho = "{$dir}/{$status}/{$arquivo}";
        return Storage::disk('pessoa-anexo')->response($caminho);
    }

    public static function upload($codpessoa, string $nome, string $anexoBase64)
    {
        // tira o anexo da string
        $data = explode(',', $anexoBase64);
        $arquivo = base64_decode($data[1]);
        $dir = static::diretorio($codpessoa);
        $unq = uniqid();
        $caminho = "{$dir}/ativos/{$unq}-{$nome}";
        Storage::disk('pessoa-anexo')->put($caminho, $arquivo);
        $indice = static::indice($dir);
        $codusuario = Auth::user()->codusuario;
        $data = date('Y-m-d H:i:s');
        $indice['ativos']["{$unq}-{$nome}"] = [
            'label' => "$nome",
            'observacoes' => null,
            'codusuariocriacao' => $codusuario,
            'criacao' => $data,
            'codusuarioalteracao' => $codusuario,
            'alteracao' => $data,
        ];
        $indice = static::salvarIndice($dir, $indice);
        return true;
    }

    public static function update($codpessoa, $nome, $label, $observacoes)
    {
        $dir = static::diretorio($codpessoa);
        $indice = static::indice($dir);
        $indice['ativos'][$nome]['label'] = $label;
        $indice['ativos'][$nome]['observacoes'] = $observacoes;
        $indice['ativos'][$nome]['codusuarioalteracao'] = Auth::user()->codusuario;
        $indice['ativos'][$nome]['alteracao'] = date('Y-m-d H:i:s');
        $indice = static::salvarIndice($dir, $indice);
        return true;
    }

    public static function inativar($codpessoa, $nome)
    {
        // move pro diretorio de inativos
        $dir = static::diretorio($codpessoa);
        $caminho = "{$dir}/ativos/{$nome}";
        $caminhoInativo = "{$dir}/inativos/{$nome}";
        Storage::disk('pessoa-anexo')->move($caminho, $caminhoInativo);

        // muda do indice de ativos pra inativos
        $indice = static::indice($dir);
        $tmp = $indice['ativos'][$nome];
        $tmp['codusuarioalteracao'] = Auth::user()->codusuario;
        $tmp['alteracao'] = date('Y-m-d H:i:s');
        unset($indice['ativos'][$nome]);
        $indice['inativos'][$nome] = $tmp;
        $indice = static::salvarIndice($dir, $indice);

        return true;
    }

    public static function ativar($codpessoa, $nome)
    {
        // move pro diretorio de ativos
        $dir = static::diretorio($codpessoa);
        $caminho = "{$dir}/ativos/{$nome}";
        $caminhoInativo = "{$dir}/inativos/{$nome}";
        Storage::disk('pessoa-anexo')->move($caminhoInativo, $caminho);

        // muda do indice de inativos pra ativos
        $indice = static::indice($dir);
        $tmp = $indice['inativos'][$nome];
        $tmp['codusuarioalteracao'] = Auth::user()->codusuario;
        $tmp['alteracao'] = date('Y-m-d H:i:s');
        unset($indice['inativos'][$nome]);
        $indice['ativos'][$nome] = $tmp;
        $indice = static::salvarIndice($dir, $indice);

        return true;
    }

    public static function delete($codpessoa, $nome)
    {
        // exclui arquivo
        $dir = static::diretorio($codpessoa);
        $caminho = "{$dir}/inativos/{$nome}";
        Storage::disk('pessoa-anexo')->delete($caminho);

        // apaga do indice
        $indice = static::indice($dir);
        unset($indice['inativos'][$nome]);
        $indice = static::salvarIndice($dir, $indice);

        return true;
    }

    public static function indice($dir)
    {
        try {
            $indice = Storage::disk('pessoa-anexo')->get("$dir/indice.json");
            $indice = json_decode($indice, true);
        } catch (Exception $e) {
            $indice = [
                'ativos' => [],
                'inativos' => []
            ];
        }
        return $indice;
    }

    public static function salvarIndice($dir, $indice)
    {
        try {
            $indice = json_encode($indice);
            Storage::disk('pessoa-anexo')->put("$dir/indice.json", $indice);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function listagem(int $codpessoa)
    {
        $ret = [
            'ativos' => [],
            'inativos' => []
        ];
        $dir = static::diretorio($codpessoa);
        $indice = static::indice($dir);
        // dd($indice);
        foreach (['ativos', 'inativos'] as $status) {
            $caminho = "{$dir}/{$status}/";
            $arqs = Storage::disk('pessoa-anexo')->allFiles($caminho);
            foreach ($arqs as $arq) {
                $nome = basename($arq);
                $ret[$status][] = [
                    'nome' => $nome,
                    'size' => Storage::disk('pessoa-anexo')->size("{$arq}"),
                    'lastModified' => Carbon::parse(Storage::disk('pessoa-anexo')->lastModified("{$arq}")),
                    'label' => $indice[$status][$nome]['label'] ?? null,
                    'tipo' => pathinfo($nome, PATHINFO_EXTENSION),
                    'observacoes' => $indice[$status][$nome]['observacoes'] ?? null,
                ];
            }
        }
        return $ret;
    }

    public static function base64(int $codpessoa)
    {
        $listagem = static::listagem($codpessoa, true);
        foreach ($listagem as $pasta => $anexos) {
            if (!in_array($pasta, ['confissao', 'imagem'])) {
                continue;
            }
            foreach ($anexos as $i => $anexo) {
                $base64 = 'data:image/jpeg;base64,' . base64_encode(Storage::disk('pessoa-anexo')->get($anexo));
                $listagem[$pasta][$i] = $base64;
            }
        }
        return $listagem;
    }

    public static function excluir(int $codpessoa, string $pasta, string $anexo)
    {
        $dir = static::diretorio($codpessoa);
        $antiga = "{$dir}/{$pasta}/{$anexo}";
        $nova = "{$dir}/lixeira/{$anexo}";
        Storage::disk('pessoa-anexo')->move($antiga, $nova);
        static::marcaDataUsuarioConfissao($codpessoa);
        return $nova;
    }
}
