<?php

namespace Mg\Colaborador;

use App\Jobs\CriarFolderGoogleDriveColaboradorJob;
use Mg\Pessoa\PessoaGoogleDriveService;

class ColaboradorService
{

    public static function create($data)
    {
        // Idempotencia: nao cria colaborador ativo duplicado para a mesma pessoa
        // na mesma filial (alem do indice unico no banco). Protege contra
        // double-submit/retry que escape do frontend.
        $existente = Colaborador::where('codpessoa', $data['codpessoa'] ?? null)
            ->where('codfilial', $data['codfilial'] ?? null)
            ->whereNull('rescisao')
            ->first();
        if ($existente) {
            return $existente->refresh();
        }
        $colaborador = new Colaborador($data);
        $colaborador->save();
        // Pasta no Google Drive vai pra fila (redis): create() responde sem
        // esperar as chamadas externas do Drive.
        CriarFolderGoogleDriveColaboradorJob::dispatch($colaborador);
        return $colaborador->refresh();
    }

    public static function update($colaborador, $data)
    {
        $colaborador->fill($data);
        $colaborador->save();
        CriarFolderGoogleDriveColaboradorJob::dispatch($colaborador);
        return $colaborador->refresh();
    }


    public static function delete($colaborador)
    {
        return $colaborador->delete();
    }

    // public static function inativar(GrupoEconomico $grupo)
    // {
    //     $grupo->update(['inativo' => Carbon::now()]);
    //     return $grupo->refresh();
    // }

    public static function ativar($grupo)
    {
        $grupo->inativo = null;
        $grupo->update();
        return $grupo;
    }

    public static function criarFolderGoogleDrive(Colaborador $colaborador)
    {
        if (!empty($colaborador->googledrivefolderid)) {
            return true;
        }
        $drive = new PessoaGoogleDriveService();
        $result = $drive->createColaboradorFolder(
            $colaborador->Filial->Empresa->empresa,
            $colaborador->Pessoa->pessoa
        );
        $colaborador->googledrivefolderid = $result['folder_id'];
        $colaborador->save();
    }
}
