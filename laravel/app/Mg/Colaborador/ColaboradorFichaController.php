<?php

namespace Mg\Colaborador;

use Mg\MgController;
use Mg\Pessoa\PessoaGoogleDriveService;
use Dompdf\Dompdf;

class ColaboradorFichaController extends MgController
{
    public function ficha($codcolaborador)
    {
        $colaborador = Colaborador::with([
            'Pessoa.EstadoCivil',
            'Pessoa.GrauInstrucao',
            'Pessoa.Etnia',
            'Pessoa.CidadeNascimento.Estado',
            'Pessoa.Cidade.Estado',
            'Pessoa.EstadoCtps',
            'ColaboradorCargoS' => function ($query) {
                $query->whereNull('fim')
                    ->orderBy('inicio', 'desc');
            },
            'ColaboradorCargoS.Cargo'
        ])->findOrFail($codcolaborador);

        $html = view('colaborador.ficha-colaborador', [
            'colaborador' => $colaborador,
            'pessoa' => $colaborador->Pessoa
        ])->render();

        $dompdf = new Dompdf();
        $options = $dompdf->getOptions();
        $options->setDefaultFont('helvetica');
        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        $nomeArquivo = 'Ficha_Colaborador_' . str_replace(['/', '\\', ' '], '_', $colaborador->Pessoa->pessoa) . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $nomeArquivo . '"');
    }

    public function uploadFicha($codcolaborador)
    {
        $colaborador = Colaborador::with([
            'Pessoa.EstadoCivil',
            'Pessoa.GrauInstrucao',
            'Pessoa.Etnia',
            'Pessoa.CidadeNascimento.Estado',
            'Pessoa.Cidade.Estado',
            'Pessoa.EstadoCtps',
            'ColaboradorCargoS' => function ($query) {
                $query->whereNull('fim')
                    ->orderBy('inicio', 'desc');
            },
            'ColaboradorCargoS.Cargo',
            'Filial.Empresa'
        ])->findOrFail($codcolaborador);

        // Garante que o colaborador tem uma pasta no Google Drive
        if (empty($colaborador->googledrivefolderid)) {
            ColaboradorService::criarFolderGoogleDrive($colaborador);
            $colaborador->refresh();
        }

        // Gera o PDF
        $html = view('colaborador.ficha-colaborador', [
            'colaborador' => $colaborador,
            'pessoa' => $colaborador->Pessoa
        ])->render();

        $dompdf = new Dompdf();
        $options = $dompdf->getOptions();
        $options->setDefaultFont('helvetica');
        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        $nomeArquivo = 'Ficha_Colaborador_' . str_replace(['/', '\\', ' '], '_', $colaborador->Pessoa->pessoa) . '.pdf';

        // Salva temporariamente o PDF
        $tempPath = sys_get_temp_dir() . '/' . $nomeArquivo;
        file_put_contents($tempPath, $dompdf->output());

        // Faz upload para o Google Drive
        $drive = new PessoaGoogleDriveService();
        $uploadedFile = $drive->uploadFile(
            $tempPath,
            $nomeArquivo,
            $colaborador->googledrivefolderid,
            'application/pdf'
        );

        // Remove o arquivo temporário
        unlink($tempPath);

        // Retorna as URLs
        return response()->json([
            'folder_url' => 'https://drive.google.com/drive/folders/' . $colaborador->googledrivefolderid,
            'file_url' => $uploadedFile->getWebViewLink(),
            'file_id' => $uploadedFile->getId(),
            'file_name' => $uploadedFile->getName()
        ], 200);
    }
}
