<?php

namespace Mg\NfeTerceiro;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloNfeTerceiro;

class NfeTerceiroIcmsStService
{
    // Mapeamento de CNAE por filial
    const CNAE_FILIAL = [
        101 => '4761003',
        103 => '4761003',
        105 => '4751201',
    ];

    // Mapeamento de número pessoa destinatário por filial
    const PESSOA_DESTINATARIO_FILIAL = [
        101 => '611107',
        103 => '126206917',
        105 => '128413022',
    ];

    /**
     * Gera Guia ST (DAR) via SEFAZ MT e cria Título vinculado
     */
    public static function gerarGuiaSt(NfeTerceiro $nft, float $valor, string $vencimento): array
    {
        if ($valor <= 0) {
            throw new Exception('Valor deve ser maior que zero.');
        }

        $filial = $nft->Filial;
        $codfilial = $filial->codfilial;

        if (!isset(self::CNAE_FILIAL[$codfilial])) {
            throw new Exception("Filial {$codfilial} não configurada para geração de Guia ST.");
        }

        $cnae = self::CNAE_FILIAL[$codfilial];
        $numrPessoaDestinatario = self::PESSOA_DESTINATARIO_FILIAL[$codfilial];
        $numrContribuinte = self::PESSOA_DESTINATARIO_FILIAL[$codfilial];

        // Dados da filial
        $ie = $filial->Pessoa->ie;
        $cnpj = $filial->Pessoa->cnpj;

        $periodoReferencia = date('m/Y');
        $valorFormatado = number_format($valor, 2, ',', '.');

        // POST para SEFAZ MT
        $url = 'https://www.sefaz.mt.gov.br/arrecadacao/darlivre/pj/gerardar';
        $params = [
            'periodoReferencia' => $periodoReferencia,
            'tipoVenda' => '2',
            'tributo' => '2817',
            'cnpjBeneficiario' => '',
            'numrDocumentoDestinatario' => $ie,
            'numrNota1' => $nft->nfechave,
            'numrPessoaDestinatario' => $numrPessoaDestinatario,
            'statInscricaoEstadual' => 'Ativo',
            'dataVencimento' => $vencimento,
            'valor' => $valorFormatado,
            'numrContribuinte' => $numrContribuinte,
            'numrDocumento' => $cnpj,
            'numrInscEstadual' => $ie,
            'tipoContribuinte' => '1',
            'codgCnae' => $cnae,
            'tributoTad' => '2817',
            'tipoDocumento' => '2',
            'municipio' => '255009',
        ];

        $cookieFile = tempnam(sys_get_temp_dir(), 'sefaz_');

        // Primeira requisição
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            @unlink($cookieFile);
            throw new Exception("Erro HTTP {$httpCode} ao acessar SEFAZ MT.");
        }

        $pdf = null;

        if (strpos($contentType, 'application/pdf') !== false) {
            $pdf = $response;
        } else {
            // Segunda requisição para obter PDF
            $urlPdf = 'https://www.sefaz.mt.gov.br/arrecadacao/darlivre/impirmirdar?chavePix=true';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $urlPdf,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_COOKIEFILE => $cookieFile,
                CURLOPT_TIMEOUT => 30,
            ]);
            $pdfResponse = curl_exec($ch);
            $pdfContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if (strpos($pdfContentType, 'application/pdf') !== false) {
                $pdf = $pdfResponse;
            }
        }

        @unlink($cookieFile);

        if (!$pdf) {
            // Tenta extrair mensagem de erro do HTML
            $dom = new \DOMDocument();
            @$dom->loadHTML($response);
            $xpath = new \DOMXPath($dom);
            $erroNodes = $xpath->query("//font[@class='SEFAZ-FONT-MensagemErro']");
            $mensagemErro = $erroNodes->length > 0 ? $erroNodes->item(0)->textContent : 'Erro desconhecido ao gerar DAR.';
            throw new Exception("SEFAZ MT: {$mensagemErro}");
        }

        // Cria Título
        $codtitulo = DB::selectOne("SELECT NEXTVAL('tbltitulo_codtitulo_seq') as codtitulo")->codtitulo;

        $titulo = new Titulo();
        $titulo->codtitulo = $codtitulo;
        $titulo->codfilial = $nft->codfilial;
        $titulo->codtipotitulo = 928; // Boleto a Pagar
        $titulo->codpessoa = 3899; // SEFAZ
        $titulo->codcontacontabil = 147; // ICMS ST
        $titulo->codportador = Portador::CARTEIRA;
        $titulo->credito = $valor;
        $titulo->numero = "ICMS ST {$codtitulo}";
        $titulo->emissao = Carbon::now();
        $titulo->transacao = $titulo->emissao;
        $titulo->sistema = $titulo->emissao;
        $titulo->vencimento = Carbon::parse($vencimento);
        $titulo->vencimentooriginal = $titulo->vencimento;
        $titulo->gerencial = true;
        $titulo->save();

        // Vincula ao NfeTerceiro
        $tnft = new TituloNfeTerceiro();
        $tnft->codtitulo = $titulo->codtitulo;
        $tnft->codnfeterceiro = $nft->codnfeterceiro;
        $tnft->save();

        // Salva PDF
        $ano = date('Y');
        $mes = date('m');
        $path = "/opt/www/Arquivos/GuiaST/{$ano}/{$mes}";
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $arquivo = "{$path}/{$nft->nfechave}-{$codtitulo}.pdf";
        file_put_contents($arquivo, $pdf);

        return [
            'codtitulo' => $titulo->codtitulo,
            'codtitulonfeterceiro' => $tnft->codtitulonfeterceiro,
            'arquivo' => $arquivo,
        ];
    }

    /**
     * Retorna PDF da Guia ST já gerada
     */
    public static function guiaStPdf(TituloNfeTerceiro $tnft): ?string
    {
        $titulo = $tnft->Titulo;
        $nft = $tnft->NfeTerceiro;
        $ano = Carbon::parse($titulo->emissao)->format('Y');
        $mes = Carbon::parse($titulo->emissao)->format('m');
        $arquivo = "/opt/www/Arquivos/GuiaST/{$ano}/{$mes}/{$nft->nfechave}-{$titulo->codtitulo}.pdf";

        if (file_exists($arquivo)) {
            return $arquivo;
        }
        return null;
    }
}
