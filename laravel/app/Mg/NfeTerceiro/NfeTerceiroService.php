<?php

namespace Mg\NfeTerceiro;

use Exception;
use Illuminate\Support\Facades\Auth;
use Mg\Dfe\DfeTipo;
use Mg\NFePHP\NFePHPManifestacaoService;
use Mg\NFePHP\NFePHPPathService;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Mg\NFePHP\NFePHPConfigService;
use Mg\NFePHP\NFePHPDistDfeService;
use NFePHP\NFe\Common\Standardize;

class NfeTerceiroService
{
    public static function pesquisar(array $filtros)
    {
        $query = NfeTerceiro::query();

        if (!empty($filtros['codfilial'])) {
            $query->where('codfilial', $filtros['codfilial']);
        }

        if (!empty($filtros['codpessoa'])) {
            $query->where('codpessoa', $filtros['codpessoa']);
        }

        if (!empty($filtros['codgrupoeconomico'])) {
            $query->whereHas('Pessoa', function ($q) use ($filtros) {
                $q->where('codgrupoeconomico', $filtros['codgrupoeconomico']);
            });
        }

        if (!empty($filtros['codnaturezaoperacao'])) {
            $query->where('codnaturezaoperacao', $filtros['codnaturezaoperacao']);
        }

        if (!empty($filtros['nfechave'])) {
            $query->where('nfechave', 'like', '%' . $filtros['nfechave'] . '%');
        }

        if (!empty($filtros['emissao_inicio'])) {
            $query->where('emissao', '>=', "{$filtros['emissao_inicio']} 00:00:00");
        }

        if (!empty($filtros['emissao_fim'])) {
            $query->where('emissao', '<=', "{$filtros['emissao_fim']} 23:59:59");
        }

        if (!empty($filtros['indsituacao'])) {
            $query->where('indsituacao', $filtros['indsituacao']);
        }

        if (!empty($filtros['indmanifestacao'])) {
            $query->where('indmanifestacao', $filtros['indmanifestacao']);
        }

        if (isset($filtros['ignorada']) && $filtros['ignorada'] !== '' && $filtros['ignorada'] !== null) {
            $query->where('ignorada', filter_var($filtros['ignorada'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filtros['revisao']) && $filtros['revisao'] !== '' && $filtros['revisao'] !== null) {
            if (filter_var($filtros['revisao'], FILTER_VALIDATE_BOOLEAN)) {
                $query->whereNotNull('revisao');
            } else {
                $query->whereNull('revisao');
            }
        }

        if (isset($filtros['conferencia']) && $filtros['conferencia'] !== '' && $filtros['conferencia'] !== null) {
            if (filter_var($filtros['conferencia'], FILTER_VALIDATE_BOOLEAN)) {
                $query->whereNotNull('conferencia');
            } else {
                $query->whereNull('conferencia');
            }
        }

        if (!empty($filtros['valortotal_inicio'])) {
            $query->where('valortotal', '>=', $filtros['valortotal_inicio']);
        }

        if (!empty($filtros['valortotal_fim'])) {
            $query->where('valortotal', '<=', $filtros['valortotal_fim']);
        }

        return $query;
    }

    public static function xml(NfeTerceiro $nft): string
    {
        $dfeTipo = DfeTipo::where(['schemaxml' => 'procNFe_v4.00.xsd'])->firstOrFail();
        $dd = $nft->DistribuicaoDfeS()->where('coddfetipo', $dfeTipo->coddfetipo)->first();
        if (!$dd) {
            abort(404, 'XML não disponível para esta NFe.');
        }
        $path = NFePHPPathService::pathDfeGz($dd);
        $gz = file_get_contents($path);

        return gzdecode($gz);
    }

    public static function atualizar(NfeTerceiro $nft, array $data): NfeTerceiro
    {
        if (!empty($nft->codnotafiscal)) {
            abort(409, 'NFe já foi importada e não pode mais ser editada.');
        }

        $nft->fill($data);
        $nft->save();

        return $nft->fresh();
    }

    public static function alternarRevisao(NfeTerceiro $nft): NfeTerceiro
    {
        if ($nft->revisao) {
            $nft->revisao = null;
            $nft->codusuariorevisao = null;
        } else {
            $nft->revisao = now();
            $nft->codusuariorevisao = Auth::id();
        }
        $nft->save();

        return $nft->fresh();
    }

    public static function alternarConferencia(NfeTerceiro $nft): NfeTerceiro
    {
        if ($nft->conferencia) {
            $nft->conferencia = null;
            $nft->codusuarioconferencia = null;
        } else {
            $nft->conferencia = now();
            $nft->codusuarioconferencia = Auth::id();
        }
        $nft->save();

        return $nft->fresh();
    }

    public static function uploadXml(string $xmlContent): ?NfeTerceiro
    {
        $gz = gzencode($xmlContent);
        NFePHPDistDfeService::processarProcNFe(null, $gz);

        // LIBXML_NONET impede acesso a recursos externos (proteção XXE)
        $dom = new \DOMDocument();
        $dom->loadXML($xmlContent, LIBXML_NONET);

        $infNFe = $dom->getElementsByTagName('infNFe')->item(0);
        if (!$infNFe) {
            return null;
        }

        $chave = preg_replace('/[^0-9]/', '', $infNFe->getAttribute('Id'));
        if (!$chave) {
            return null;
        }

        return NfeTerceiro::where('nfechave', $chave)->first();
    }

    public static function manifestacao(NfeTerceiro $nfeTerceiro, $indmanifestacao, string $justificativa = '')
    {
        $resp = NFePHPManifestacaoService::manifestacao(
            $nfeTerceiro->Filial,
            $nfeTerceiro->nfechave,
            $indmanifestacao,
            $justificativa,
            1
        );
        $ret = [
            'cStat' => $resp->cStat,
            'xMotivo' => $resp->xMotivo,
        ];
        if ($infEvento = $resp->retEvento->infEvento) {
            $ret['cStat'] = $infEvento->cStat;
            $ret['xMotivo'] = $infEvento->xMotivo;
            switch ($infEvento->cStat) {
                case '135': // Evento registrado e vinculado a NF-e
                case '136': // Evento registrado, mas não vinculado a NF-e
                case '573': // Rejeicao: Duplicidade de evento
                    $nfeTerceiro->update([
                        'indmanifestacao' => $indmanifestacao
                    ]);
                    break;
            }
        }
        return $ret;
    }

    public static function vincularNotaFiscal(NfeTerceiro $nfeTerceiro)
    {
        if (!empty($nfeTerceiro->codnotafiscal)) {
            return $nfeTerceiro;
        }
        $notaFiscal = NotaFiscal::where('emitida', false)->where('nfechave', $nfeTerceiro->nfechave)->first();
        if ($notaFiscal) {
            $nfeTerceiro->codnotafiscal = $notaFiscal->codnotafiscal;
        }
        return $nfeTerceiro;
    }

    public static function vincularNegocio(NfeTerceiro $nfeTerceiro)
    {
        if (!empty($nfeTerceiro->codnegocio)) {
            return $nfeTerceiro;
        }
        if (empty($nfeTerceiro->codnotafiscal)) {
            return $nfeTerceiro;
        }
        $notaFiscalProdutoBarra = NotaFiscalProdutoBarra::where('codnotafiscal', $nfeTerceiro->codnotafiscal)
            ->whereNotNull('codnegocioprodutobarra')
            ->orderBy('codnotafiscalprodutobarra')
            ->first();
        if ($notaFiscalProdutoBarra) {
            $nfeTerceiro->codnegocio = $notaFiscalProdutoBarra->NegocioProdutoBarra->codnegocio;
        }
        return $nfeTerceiro;
    }

    public static function download($nfeTerceiro)
    {
        // consulta nfe na sefaz
        $tools = NFePHPConfigService::instanciaTools($nfeTerceiro->Filial);
        // $tools->setEnvironment(1);
        $resp = $tools->sefazDownload($nfeTerceiro->nfechave);

        // converte resposta em objeto
        $stz = new Standardize($resp);
        $std = $stz->toStd();

        // Se != 138 - "Documento localizado"
        if ($std->cStat != 138) {
            throw new Exception("{$std->cStat} - {$std->xMotivo}", 1);
        }

        // Impota o XML
        if (!NFePHPDistDfeService::processarProcNFe(null, base64_decode($std->loteDistDFeInt->docZip))) {
            throw new Exception('Falha ao importar o XML!');
        }

        // retorna
        return true;

    }
}
