<?php

namespace Mg\NfeTerceiro;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

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

    // Mapeamento de número de contribuinte por filial
    // Filial 101 alternativa "Sem IE": 4432657 (legado)
    const CONTRIBUINTE_FILIAL = [
        101 => '611107',
        103 => '126206917',
        105 => '128413022',
    ];

    /**
     * Relatório ICMS-ST por item + totais + guias já geradas
     */
    public static function relatorio(int $codnfeterceiro): array
    {
        $itens = DB::select('
            with final as (
                with itens as (
                    select
                        nti.codnfeterceiroitem,
                        nti.nitem,
                        nti.cprod,
                        nti.xprod,
                        nti.ncm as ncmnota,
                        n.ncm as ncmproduto,
                        nti.cest as cestnota,
                        c.cest as cestproduto,
                        round(1 + (c.mva / 100), 4) as mva,
                        coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) + coalesce(ipivipi, 0) - coalesce(vdesc, 0) as valor,
                        case when coalesce(n.bit, false) then 0.4117 else 1.0 end as reducao,
                        case when coalesce(picms, 0) > 7 then
                            (coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.07
                        else
                            case when coalesce(vicms, 0) = 0 then
                                case when p.importado then
                                    (coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.04
                                else
                                    (coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.07
                                end
                            else
                                coalesce(vicms, 0)
                            end
                        end as vicms,
                        vicmsst
                    from tblnfeterceiroitem nti
                    left join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
                    left join tblproduto p on (p.codproduto = pb.codproduto)
                    left join tblncm n on (n.codncm = p.codncm)
                    left join tblcest c on (c.codcest = p.codcest)
                    where nti.codnfeterceiro = ?
                    order by nti.ncm, nti.xprod
                )
                select *, round((valor * reducao * mva * 0.17) - (vicms * reducao), 2) as vicmsstcalculado from itens
            )
            select *, coalesce(vicmsstcalculado, 0) - coalesce(vicmsst, 0) as diferenca from final
        ', [$codnfeterceiro]);

        $totalVicmsst = 0;
        $totalCalculado = 0;
        $totalDiferenca = 0;
        foreach ($itens as $item) {
            $totalVicmsst += $item->vicmsst ?? 0;
            $totalCalculado += $item->vicmsstcalculado ?? 0;
            $totalDiferenca += $item->diferenca ?? 0;
        }

        $guias = DB::select('
            select t.codtitulo, t.numero, t.emissao, t.vencimento, t.credito, t.creditosaldo,
                   tnft.codtitulonfeterceiro
            from tbltitulonfeterceiro tnft
            inner join tbltitulo t on (t.codtitulo = tnft.codtitulo)
            where tnft.codnfeterceiro = ?
            order by t.vencimento
        ', [$codnfeterceiro]);

        return [
            'itens' => $itens,
            'totais' => [
                'vicmsst' => round($totalVicmsst, 2),
                'vicmsstcalculado' => round($totalCalculado, 2),
                'diferenca' => round($totalDiferenca, 2),
            ],
            'guias' => $guias,
        ];
    }

    /**
     * Gera Guia ST (DAR) via SEFAZ MT e cria Título vinculado
     */
    public static function gerarGuiaSt(NfeTerceiro $nft, float $valor, string $vencimento): array
    {
        if ($valor <= 0.01) {
            throw new Exception('Valor Não Informado!');
        }

        if (!isset(self::CNAE_FILIAL[$nft->codfilial])) {
            throw new Exception('Impossível determinar Cnae!');
        }
        if (!isset(self::PESSOA_DESTINATARIO_FILIAL[$nft->codfilial])) {
            throw new Exception('Impossível determinar Número da pessoa Destinatario!');
        }
        if (!isset(self::CONTRIBUINTE_FILIAL[$nft->codfilial])) {
            throw new Exception('Impossível determinar Número do Contribuinte!');
        }

        $valorFormatado = number_format($valor, 2, ',', '.');
        $params = self::buildSefazPayload($nft, $valorFormatado, $vencimento);

        $cookieFile = tempnam(sys_get_temp_dir(), 'sefaz_');

        // Primeira requisição
        $url = 'https://www.sefaz.mt.gov.br/arrecadacao/darlivre/pj/gerardar';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            @unlink($cookieFile);
            throw new Exception("Falha ao gerar PDF da DAR! - {$curlError}");
        }

        if ($httpCode !== 200) {
            @unlink($cookieFile);
            throw new Exception("Erro HTTP {$httpCode} ao acessar SEFAZ MT.");
        }

        $pdf = null;
        $errorHtml = $response;

        if (strpos($contentType, 'application/pdf') !== false) {
            $pdf = $response;
        } else {
            // Segunda requisição para obter PDF
            $urlPdf = 'https://www.sefaz.mt.gov.br/arrecadacao/darlivre/impirmirdar?chavePix=true';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $urlPdf,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_COOKIEFILE => $cookieFile,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:107.0) Gecko/20100101 Firefox/107.0',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp;q=0.8',
                    'Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Accept-Encoding: gzip, deflate, br',
                    'Connection: keep-alive',
                    'Referer: https://www.sefaz.mt.gov.br/arrecadacao/darlivre/pj/gerardar',
                    'Upgrade-Insecure-Requests: 1',
                    'Sec-Fetch-Dest: iframe',
                    'Sec-Fetch-Mode: navigate',
                    'Sec-Fetch-Site: same-origin',
                ],
            ]);
            $pdfResponse = curl_exec($ch);
            $pdfContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $pdfError = curl_error($ch);
            curl_close($ch);

            if ($pdfError) {
                @unlink($cookieFile);
                throw new Exception("Falha ao gerar PDF da DAR! - {$pdfError}");
            }

            if (strpos($pdfContentType, 'application/pdf') !== false) {
                $pdf = $pdfResponse;
            } else {
                $errorHtml = $pdfResponse;
            }
        }

        @unlink($cookieFile);

        if (!$pdf) {
            // Tenta extrair mensagem de erro do HTML (response da chamada que falhou)
            $dom = new \DOMDocument();
            @$dom->loadHTML($errorHtml);
            $xpath = new \DOMXPath($dom);
            $erroNodes = $xpath->query("//font[@class='SEFAZ-FONT-MensagemErro']");
            $mensagemErro = $erroNodes->length > 0 ? $erroNodes->item(0)->textContent : 'Erro desconhecido ao gerar DAR.';
            throw new Exception("SEFAZ MT: {$mensagemErro}");
        }

        // Cria diretório pra salvar o PDF (path baseado na emissão da NFe, igual ao legado)
        $ano = Carbon::parse($nft->emissao)->format('Y');
        $mes = Carbon::parse($nft->emissao)->format('m');
        $path = "/opt/www/Arquivos/GuiaST/{$ano}/{$mes}";
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        // Reserva Codtitulo
        $codtitulo = DB::selectOne("SELECT NEXTVAL('tbltitulo_codtitulo_seq') as codtitulo")->codtitulo;

        // Cria o Título
        $titulo = new Titulo();
        $titulo->codtitulo = $codtitulo;
        $titulo->codfilial = $nft->codfilial;
        $titulo->codtipotitulo = 928; // Boleto a Pagar
        $titulo->codpessoa = 3899; // SEFAZ
        $titulo->codcontacontabil = 147; // ICMS ST
        $titulo->credito = $valor;
        $titulo->numero = "ICMS ST {$codtitulo}";
        $titulo->emissao = Carbon::now();
        $titulo->transacao = $titulo->emissao;
        $titulo->sistema = $titulo->emissao; // NOT NULL no schema; legado preenche via beforeSave (sistema = criacao)
        $titulo->vencimento = Carbon::parse($vencimento);
        $titulo->vencimentooriginal = $titulo->vencimento;
        $titulo->observacao = "ICMS ST NFe {$nft->numero} - {$nft->Pessoa->fantasia}\n{$nft->nfechave}";
        $titulo->save();

        // Salva PDF
        $arquivo = "{$path}/{$nft->nfechave}-{$codtitulo}.pdf";
        file_put_contents($arquivo, $pdf);

        // Amarra título à NfeTerceiro
        $tnft = new TituloNfeTerceiro();
        $tnft->codtitulo = $titulo->codtitulo;
        $tnft->codnfeterceiro = $nft->codnfeterceiro;
        $tnft->save();

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
        $ano = Carbon::parse($nft->emissao)->format('Y');
        $mes = Carbon::parse($nft->emissao)->format('m');
        $arquivo = "/opt/www/Arquivos/GuiaST/{$ano}/{$mes}/{$nft->nfechave}-{$titulo->codtitulo}.pdf";

        if (file_exists($arquivo)) {
            return $arquivo;
        }
        return null;
    }

    /**
     * Monta o payload do POST para SEFAZ-MT (DAR Livre)
     * Espelha o array enviado pelo legado MGsis em NfeTerceiroController::actionGerarGuiaSt
     */
    private static function buildSefazPayload(NfeTerceiro $nft, string $valorFormatado, string $vencimento): array
    {
        $codfilial = $nft->codfilial;
        $ie = $nft->Filial->Pessoa->ie;
        $cnpj = $nft->Filial->Pessoa->cnpj;
        $cnae = self::CNAE_FILIAL[$codfilial];
        $numrPessoaDestinatario = self::PESSOA_DESTINATARIO_FILIAL[$codfilial];
        $numrContribuinte = self::CONTRIBUINTE_FILIAL[$codfilial];

        return [
            'periodoReferencia' => date('m/Y'),
            'tipoVenda' => '2',
            'tributo' => '2817',
            'cnpjBeneficiario' => '',
            'numrDuimp' => '',
            'numrDocumentoDestinatario' => $ie,
            'txtCaminhoArquivo' => '(binary)',
            'isNFE1' => 'on',
            'numrNota1' => $nft->nfechave,
            'isNFE2' => 'on',
            'numrNota2' => '',
            'isNFE3' => 'on',
            'numrNota3' => '',
            'isNFE4' => 'on',
            'numrNota4' => '',
            'isNFE5' => 'on',
            'numrNota5' => '',
            'isNFE6' => 'on',
            'numrNota6' => '',
            'isNFE7' => 'on',
            'numrNota7' => '',
            'isNFE8' => 'on',
            'numrNota8' => '',
            'isNFE9' => 'on',
            'numrNota9' => '',
            'isNFE10' => 'on',
            'numrNota10' => '',
            'numrPessoaDestinatario' => $numrPessoaDestinatario,
            'statInscricaoEstadual' => 'Ativo',
            'notas' => '1',
            'nfeNota1' => '',
            'nfeNota2' => '',
            'nfeNota3' => '',
            'nfeNota4' => '',
            'nfeNota5' => '',
            'nfeNota6' => '',
            'nfeNota7' => '',
            'nfeNota8' => '',
            'nfeNota9' => '',
            'nfeNota10' => '',
            'numrParcela' => '',
            'totalParcela' => '',
            'numrNai' => '',
            'numrTad' => '',
            'multaCovid' => '',
            'numeroNob' => '',
            'codgConvDesc' => '',
            'dataVencimento' => $vencimento,
            'qtd' => '',
            'qtdUnMedida' => '',
            'valorUnitario' => '',
            'valorCampo' => $valorFormatado,
            'valorCorrecao' => '',
            'diasAtraso' => '',
            'juros' => '',
            'tipoDocumento' => '2',
            'nota1' => '',
            'nota2' => '',
            'nota3' => '',
            'nota4' => '',
            'nota5' => '',
            'nota6' => '',
            'nota7' => '',
            'nota8' => '',
            'nota9' => '',
            'nota10' => '',
            'informacaoPrevista' => '',
            'informacaoPrevista2' => '',
            'municipio' => '255009',
            'numrContribuinte' => $numrContribuinte,
            'pagn' => 'emitir',
            'numrDocumento' => $cnpj,
            'numrInscEstadual' => $ie,
            'tipoContribuinte' => '1',
            'codgCnae' => $cnae,
            'tipoTributoH' => '',
            'codgOrgao' => '',
            'valor' => $valorFormatado,
            'valorPadrao' => '0',
            'valorMulta' => '',
            'tributoTad' => '2817',
            'tipoVendaX' => '',
            'tipoUniMedida' => '',
            'valorUnit' => '',
            'upfmtFethab' => '',
        ];
    }
}
