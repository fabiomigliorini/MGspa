<?php

namespace Mg\NfeTerceiro;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mg\Dfe\DfeTipo;
use Mg\NFePHP\NFePHPManifestacaoService;
use Mg\NFePHP\NFePHPService;
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

        if (!empty($filtros['importacao'])) {
            switch ($filtros['importacao']) {
                case 'pendentes':
                    $query->whereNull('codnotafiscal')
                        ->where('indsituacao', NfeTerceiro::INDSITUACAO_AUTORIZADA)
                        ->where('ignorada', false)
                        ->where(function ($q) {
                            $q->whereNull('indmanifestacao')
                                ->orWhereNotIn('indmanifestacao', [
                                    NfeTerceiro::INDMANIFESTACAO_DESCONHECIDA,
                                    NfeTerceiro::INDMANIFESTACAO_NAOREALIZADA,
                                ]);
                        });
                    break;
                case 'importadas':
                    $query->whereNotNull('codnotafiscal');
                    break;
                case 'ignoradas':
                    $query->where(function ($q) {
                        $q->whereIn('indmanifestacao', [
                            NfeTerceiro::INDMANIFESTACAO_DESCONHECIDA,
                            NfeTerceiro::INDMANIFESTACAO_NAOREALIZADA,
                        ])
                            ->orWhere('indsituacao', '<>', NfeTerceiro::INDSITUACAO_AUTORIZADA)
                            ->orWhere('ignorada', true);
                    });
                    break;
            }
        }

        return $query;
    }

    /**
     * XML completo (procNFe, com protocolo) da NFe de terceiro. Baixa da SEFAZ na primeira
     * vez e serve do disco nas seguintes.
     *
     * @throws NfeTerceiroXmlIndisponivelException
     */
    public static function xml(NfeTerceiro $nft): string
    {
        // Instancia FRESCA: processarProcNFe reescreve emissao e pode reescrever codfilial,
        // e o caminho depende dos dois.
        $nft = static::garantirProcNFe($nft);

        $path = static::pathProcNFeExistente($nft);
        if ($path === null) {
            throw new NfeTerceiroXmlIndisponivelException(
                'O XML desta NFe foi baixado mas não pôde ser gravado no servidor.',
                sugestao: 'Acione o suporte.'
            );
        }

        return static::lerProcNFe($path);
    }

    /**
     * Garante que o procNFe esta em disco. Se nao estiver, baixa da SEFAZ.
     *
     * Devolve a NfeTerceiro — possivelmente uma instancia NOVA, relida depois do
     * processamento. Quem chama tem de usar o retorno, nao o argumento.
     *
     * @throws NfeTerceiroXmlIndisponivelException
     */
    public static function garantirProcNFe(NfeTerceiro $nft): NfeTerceiro
    {
        if (static::pathProcNFeExistente($nft) !== null) {
            return $nft;
        }

        return static::download($nft);
    }

    /**
     * Le e descompacta um procNFe do disco.
     *
     * @throws NfeTerceiroXmlIndisponivelException
     */
    protected static function lerProcNFe(string $path): string
    {
        $gz = @file_get_contents($path);
        $xml = ($gz === false) ? false : @gzdecode($gz);

        if ($xml === false) {
            Log::error('NfeTerceiroService - procNFe ilegivel em disco', ['path' => $path]);
            throw new NfeTerceiroXmlIndisponivelException(
                'O arquivo XML desta NFe não pôde ser lido no servidor.',
                sugestao: 'Acione o suporte para baixar a NFe novamente da SEFAZ.'
            );
        }

        return $xml;
    }

    /**
     * Caminho do procNFe em disco, por ordem de prioridade:
     *   1. arquivo do distDFe (tbldistribuicaodfe), quando o robo ja trouxe;
     *   2. arquivo gravado por download()/uploadXml(), fora do fluxo distDFe.
     *
     * Fonte unica do lookup: xml() e garantirProcNFe() dependem das duas alternativas
     * darem a MESMA resposta. null quando nao existe nenhum dos dois.
     */
    protected static function pathProcNFeExistente(NfeTerceiro $nft): ?string
    {
        // first() e nao firstOrFail(): se a linha de tipo sumir, queremos a mensagem de
        // "XML indisponivel", nao um 404 generico de model nao encontrado.
        $dfeTipo = DfeTipo::where(['schemaxml' => 'procNFe_v4.00.xsd'])->first();
        if ($dfeTipo) {
            $dd = $nft->DistribuicaoDfeS()->where('coddfetipo', $dfeTipo->coddfetipo)->first();
            if ($dd && file_exists($path = NFePHPPathService::pathDfe($dd))) {
                return $path;
            }
        }

        // Guard: emissao e codfilial sao nullable em tblnfeterceiro (notas que so tem o
        // resumo). Sem eles pathDiretorio() estoura em $filial->codfilial.
        if (empty($nft->nfechave) || empty($nft->emissao) || empty($nft->codfilial)) {
            return null;
        }

        $path = NFePHPPathService::pathProcNfeTerceiro($nft);

        return file_exists($path) ? $path : null;
    }

    /**
     * Chave de acesso a partir do XML do nfeProc. Prefere infNFe/@Id, que sempre existe;
     * chNFe so aparece dentro do protNFe.
     */
    protected static function chaveDoProcNFe(string $xml): string
    {
        $dom = new \DOMDocument();
        if (!@$dom->loadXML($xml, LIBXML_NONET)) {
            throw new NfeTerceiroXmlIndisponivelException('XML da NFe é inválido.');
        }

        $infNFe = $dom->getElementsByTagName('infNFe')->item(0);
        $chave = $infNFe ? preg_replace('/[^0-9]/', '', $infNFe->getAttribute('Id')) : null;

        if (empty($chave)) {
            $chave = @$dom->getElementsByTagName('chNFe')->item(0)?->nodeValue;
            $chave = preg_replace('/[^0-9]/', '', (string) $chave);
        }

        if (strlen((string) $chave) != 44) {
            throw new NfeTerceiroXmlIndisponivelException(
                'Não foi possível ler a chave de acesso no XML da NFe.'
            );
        }

        return $chave;
    }

    /**
     * Extrai do retDistDFeInt CRU o docZip cujo schema e procNFe, ja gunzipavel.
     *
     * DOM em vez de Standardize porque o simplexml+json_encode do Standardize DESCARTA os
     * atributos NSU e schema do no docZip — sem eles nao da para distinguir um procNFe de
     * um resNFe (resumo), e passar um resumo adiante faz processarProcNFe gravar lixo. Alem
     * disso, com mais de um docZip o Standardize devolve array e o base64_decode e TypeError.
     *
     * @return string|null conteudo gzipado, ou null se a resposta nao trouxe procNFe
     */
    protected static function extrairProcNFeDaResposta(string $resp): ?string
    {
        $dom = new \DOMDocument();
        if (!@$dom->loadXML($resp, LIBXML_NONET)) {
            return null;
        }

        foreach ($dom->getElementsByTagName('docZip') as $doc) {
            if ($doc->getAttribute('schema') != 'procNFe_v4.00.xsd') {
                continue;
            }
            $gz = base64_decode($doc->nodeValue, true);
            if ($gz !== false && @gzdecode($gz) !== false) {
                return $gz;
            }
        }

        return null;
    }

    /**
     * Processa o procNFe (cria/atualiza a NfeTerceiro) e grava o XML em disco.
     *
     * Unico ponto que sabe onde o arquivo mora. Ordem obrigatoria: processa PRIMEIRO,
     * rele do banco, e SO ENTAO calcula o caminho — porque processarProcNFe sobrescreve
     * emissao incondicionalmente e resolve codfilial pelo dest, e o caminho depende dos
     * dois. Calcular antes gravaria num diretorio e leria de outro.
     *
     * A GRAVACAO E BEST-EFFORT: falha de disco vira log e a nota volta assim mesmo. O XML
     * ja esta em memoria e quem chama consegue servi-lo; o cache em disco e otimizacao,
     * nao requisito. Lancar aqui abortaria NfeTerceiroImportarService::importar(), um
     * fluxo de negocio que nao depende do arquivo para nada.
     *
     * Sem transacao de proposito: processarProcNFe ja comitou (cria Pessoa/Endereco/
     * Telefone) e file_put_contents nao faz rollback, entao envolver nao tornaria atomico
     * — so seguraria locks durante o parse.
     *
     * @param NfeTerceiro|null $conhecida nota que o chamador ja tem, preferida na hora de
     *                                    resolver a linha (tblnfeterceiro.nfechave NAO tem
     *                                    UNIQUE e ha chaves duplicadas em producao)
     * @throws NfeTerceiroXmlIndisponivelException
     */
    protected static function persistirProcNFe(string $gz, ?NfeTerceiro $conhecida = null): NfeTerceiro
    {
        if (!NFePHPDistDfeService::processarProcNFe(null, $gz)) {
            throw new NfeTerceiroXmlIndisponivelException('Falha ao importar o XML da NFe.');
        }

        $chave = static::chaveDoProcNFe(gzdecode($gz));

        $nft = $conhecida
            ? $conhecida->fresh()
            : NfeTerceiro::where('nfechave', $chave)->orderBy('codnfeterceiro')->first();

        if (!$nft) {
            throw new NfeTerceiroXmlIndisponivelException(
                "XML processado mas a NFe {$chave} não foi localizada no banco."
            );
        }

        static::gravarProcNFe($nft, $gz);

        return $nft;
    }

    /**
     * Grava o procNFe em disco. Best-effort: nunca lanca, so loga.
     */
    protected static function gravarProcNFe(NfeTerceiro $nft, string $gz): void
    {
        // processarProcNFe so preenche codfilial se FilialService::buscarPorCnpjIe achar a
        // filial pelo dest — XML destinado a terceiro, ou com IE "ISENTO", nao acha. Sem
        // filial nao ha caminho: path() exige Filial nao-nulavel e daria TypeError.
        if (empty($nft->nfechave) || empty($nft->emissao) || empty($nft->codfilial)) {
            Log::warning('NfeTerceiroService - sem dados para montar o caminho do procNFe', [
                'codnfeterceiro' => $nft->codnfeterceiro,
                'codfilial' => $nft->codfilial,
            ]);
            return;
        }

        $path = NFePHPPathService::pathProcNfeTerceiro($nft, true);

        // .tmp + rename (atomico no mesmo filesystem): um .gz truncado faria gzdecode
        // devolver false para sempre, sem se auto-curar. Sufixo unico porque dois requests
        // simultaneos na mesma nota (abrir /xml e /danfe juntos) usariam o mesmo .tmp.
        $tmp = $path . '.' . getmypid() . '.' . uniqid() . '.tmp';

        // file_put_contents devolve BYTES escritos: disco cheio devolve um int menor, nao
        // false. Comparar com strlen, senao renomeamos justamente o arquivo truncado.
        if (@file_put_contents($tmp, $gz) !== strlen($gz) || !@rename($tmp, $path)) {
            @unlink($tmp);
            // Path so no log — a mensagem do usuario nao deve expor a arvore do servidor.
            Log::error('NfeTerceiroService - falha ao gravar procNFe em disco', [
                'codnfeterceiro' => $nft->codnfeterceiro,
                'path' => $path,
            ]);
        }
    }

    /**
     * Acao concreta para os cStat de rejeicao que o usuario consegue resolver sozinho.
     */
    protected static function sugestaoParaCstat(?string $cstat): ?string
    {
        return match ((string) $cstat) {
            '632' => 'A SEFAZ só libera o XML completo depois da manifestação do '
                . 'destinatário. Use "Manifestação → Ciência da Operação" nesta NFe e '
                . 'tente novamente.',
            '217' => 'A NFe não consta na base da SEFAZ. Confira a chave de acesso.',
            '656' => 'Consumo indevido: a SEFAZ bloqueou temporariamente as consultas. '
                . 'Aguarde alguns minutos antes de tentar de novo.',
            default => null,
        };
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

    /**
     * Importa um nfeProc enviado manualmente (fora do fluxo distDFe).
     *
     * @throws NfeTerceiroXmlIndisponivelException
     */
    public static function uploadXml(string $xmlContent): NfeTerceiro
    {
        // LIBXML_NONET impede acesso a recursos externos (proteção XXE)
        $dom = new \DOMDocument();
        if (!@$dom->loadXML($xmlContent, LIBXML_NONET)) {
            throw new NfeTerceiroXmlIndisponivelException(
                'O arquivo enviado não é um XML válido.'
            );
        }

        // Sem protNFe nao ha protocolo de autorizacao: processarProcNFe procura chNFe
        // (que so existe dentro do protNFe) e estoura em null.
        if ($dom->getElementsByTagName('protNFe')->length === 0) {
            throw new NfeTerceiroXmlIndisponivelException(
                'O XML enviado não tem protocolo de autorização.',
                sugestao: 'Envie o nfeProc completo (NFe + protNFe), não apenas a NFe.'
            );
        }

        return static::persistirProcNFe(gzencode($xmlContent));
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

    /**
     * Baixa o procNFe da SEFAZ por chave (consChNFe) e persiste em banco e disco.
     *
     * Devolve a NfeTerceiro relida — processarProcNFe reescreve emissao e codfilial.
     *
     * @throws NfeTerceiroXmlIndisponivelException
     */
    public static function download(NfeTerceiro $nfeTerceiro): NfeTerceiro
    {
        // consulta nfe na sefaz
        $tools = NFePHPConfigService::instanciaTools($nfeTerceiro->Filial);
        // $tools->setEnvironment(1);
        $resp = NFePHPService::chamarSefazComRetry(
            fn() => $tools->sefazDownload($nfeTerceiro->nfechave),
            'download',
            $tools,
            $nfeTerceiro->Filial
        );

        // converte resposta em objeto
        $stz = new Standardize($resp);
        $std = $stz->toStd();

        // Se != 138 - "Documento localizado"
        if ($std->cStat != 138) {
            Log::warning('NfeTerceiroService::download - rejeicao da SEFAZ', [
                'codnfeterceiro' => $nfeTerceiro->codnfeterceiro,
                'nfechave' => $nfeTerceiro->nfechave,
                'cStat' => $std->cStat,
                'xMotivo' => $std->xMotivo,
            ]);
            throw new NfeTerceiroXmlIndisponivelException(
                "{$std->cStat} - {$std->xMotivo}",
                sugestao: static::sugestaoParaCstat($std->cStat),
                cstat: (string) $std->cStat
            );
        }

        // Le do XML CRU, nao do Standardize: ele descarta o atributo schema do docZip e
        // sem ele nao da para distinguir procNFe (completo) de resNFe (resumo).
        $gz = static::extrairProcNFeDaResposta($resp);
        if ($gz === null) {
            throw new NfeTerceiroXmlIndisponivelException(
                'A SEFAZ localizou a NFe mas não devolveu o XML completo (procNFe).',
                sugestao: static::sugestaoParaCstat('632')
            );
        }

        // Passa a nota conhecida: tblnfeterceiro.nfechave nao tem UNIQUE e ha chaves
        // duplicadas em producao, entao buscar so pela chave poderia devolver outra linha.
        return static::persistirProcNFe($gz, $nfeTerceiro);
    }
}
