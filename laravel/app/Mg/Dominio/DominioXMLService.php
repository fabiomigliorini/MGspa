<?php

namespace Mg\Dominio;

use ZipArchive;

use DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use Mg\Filial\Filial;
use Mg\NFePHP\NFePHPPathService;

class DominioXMLService
{
    public static function nfeSaida(int $codfilial, Carbon $mes, int $modelo)
    {
        //Busca Filial
        $filial = Filial::findOrFail($codfilial);

        // Monta Caminho e nome do Arquivo ZIP
        $pathZip = Storage::disk('dominio')->getDriver()->getAdapter()->getPathPrefix();
        $arquivoZip = $mes->format('Ym') . '-' . str_pad($filial->empresadominio, 4, '0', STR_PAD_LEFT) . '-';
        switch ($modelo) {
            case 55:
                $arquivoZip .= 'NFe';
                break;

            case 65:
                $arquivoZip .= 'NFCe';
                break;

            default:
                $arquivoZip .= $modelo;
                break;
        }
        $arquivoZip .= '-Saidas.zip';

        // Busca Notas de Saída Aprovadas
        $sql = '
            select nf.codnotafiscal, nf.nfechave
            from tblnotafiscal nf
            where nf.codfilial = :codfilial
            and nf.emissao between :inicio and :fim
            and nf.emitida  = true
            and nf.codoperacao  = 2 -- SAIDAS
            and nf.modelo = :modelo
            and nf.nfeinutilizacao is null
            and nf.nfecancelamento is null
            and nf.nfeautorizacao is not null
        ';
        $notas = DB::select($sql, [
            'codfilial' => $codfilial,
            'inicio' => $mes->startOfMonth()->format('Y-m-d H:i:s'),
            'fim' => $mes->endOfMonth()->format('Y-m-d H:i:s'),
            'modelo' => $modelo,
        ]);

        // Cria o Arquivo ZIP
        $za = new ZipArchive();
        $za->open($pathZip . $arquivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Monta Path dos XML
        $pathXml = NFePHPPathService::pathNFe($filial) . "enviadas/aprovadas/" . $mes->format('Ym');

        // Percorre todas as notas
        $registros = 0;
        foreach ($notas as $nota) {

            // tenta adicionar o XML no arquivo ZIP
            $pattern = $pathXml . '/*' . $nota->nfechave . '*.[xX][mM][lL]';
            if ($adicionados = $za->addGlob($pattern, 0, ['remove_all_path' => true])) {
                $registros += 1;
            }

        }

        // Fecha o ZIP
        try {
            $za->close();
        } catch (\Exception $e) {
            $registros = 0;
        }

        // Retorna total de registros
        $total = count($notas);
        return [
            'arquivo' => $arquivoZip,
            'registrosCompactados' => $registros,
            'registrosNaoLocalizados' => $total - $registros,
            'registrosTotal' => $total,
        ];
    }

    public static function nfeEntrada(int $codfilial, Carbon $mes)
    {
        //Busca Filial
        $filial = Filial::findOrFail($codfilial);

        // Monta Caminho e nome do Arquivo ZIP
        $pathZip = Storage::disk('dominio')->getDriver()->getAdapter()->getPathPrefix();
        $arquivoZip = $mes->format('Ym') . '-' . str_pad($filial->empresadominio, 4, '0', STR_PAD_LEFT) . '-Entradas.zip';

        // Busca Notas de Entrada Aprovadas emitidas pela própria Filial
        // mais as notas de saida emitidas por outra filial,
        // cujo destino seja esta filial
        $sql = '
            select nf.codfilial, nf.codnotafiscal, nf.nfechave
            from tblnotafiscal nf
            where nf.codfilial = :codfilial
            and nf.emissao between :inicio and :fim
            and nf.emitida  = true
            and nf.codoperacao  = 1 -- ENTRADA
            and nf.nfeinutilizacao is null
            and nf.nfecancelamento is null
            and nf.nfeautorizacao is not null
            union
            select nf.codfilial, nf.codnotafiscal, nf.nfechave
            from tblfilial f
            inner join tblpessoa p  on (p.codpessoa = f.codpessoa)
            inner join tblnotafiscal nf on (nf.codpessoa = p.codpessoa)
            where f.codfilial = :codfilial
            and nf.emissao between :inicio and :fim
            and nf.emitida  = true
            and nf.codoperacao  = 2 -- SAIDA
            and nf.nfeinutilizacao is null
            and nf.nfecancelamento is null
            and nf.nfeautorizacao is not null
            order by nfechave
        ';
        $notas = DB::select($sql, [
            'codfilial' => $codfilial,
            'inicio' => $mes->startOfMonth()->format('Y-m-d H:i:s'),
            'fim' => $mes->endOfMonth()->format('Y-m-d H:i:s'),
        ]);

        // Cria o Arquivo ZIP
        $za = new ZipArchive();
        $za->open($pathZip . $arquivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);


        // Percorre todas as notas
        $registros = 0;
        $codfilialUltima = null;
        foreach ($notas as $nota) {

            // Monta Path dos XML
            if ($codfilialUltima != $nota->codfilial) {
                $filial = Filial::findOrFail($nota->codfilial);
                $pathXml = NFePHPPathService::pathNFe($filial) . "enviadas/aprovadas/" . $mes->format('Ym');
            }

            // tenta adicionar o XML no arquivo ZIP
            $pattern = $pathXml . '/*' . $nota->nfechave . '*.[xX][mM][lL]';
            if ($adicionados = $za->addGlob($pattern, 0, ['remove_all_path' => true])) {
                $registros += 1;
            }

        }

        // Fecha o ZIP
        try {
            $za->close();
        } catch (\Exception $e) {
            $registros = 0;
        }

        // Retorna total de registros
        $total = count($notas);
        return [
            'arquivo' => $arquivoZip,
            'registrosCompactados' => $registros,
            'registrosNaoLocalizados' => $total - $registros,
            'registrosTotal' => $total,
        ];
    }

}
