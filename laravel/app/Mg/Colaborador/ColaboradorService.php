<?php

namespace Mg\Colaborador;

use Mg\Pessoa\PessoaGoogleDriveService;

class ColaboradorService
{

    public static function create($data)
    {
        $colaborador = new Colaborador($data);
        $colaborador->save();
        static::criarFolderGoogleDrive($colaborador);
        return $colaborador->refresh();
    }

    public static function update($colaborador, $data)
    {
        $colaborador->fill($data);
        $colaborador->save();
        static::criarFolderGoogleDrive($colaborador);
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
