<?php

namespace Mg\NotaFiscalServico;

use Carbon\Carbon;
use DOMDocument;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mg\Filial\Filial;
use Mg\NFePHP\NFePHPPathService;

/**
 * Caixa de DF-e da NFS-e (TASK-279): le a distribuicao do ADN por NSU, como o DistDFe
 * da NF-e. Chegam as NFS-e em que a filial e' emitente, tomadora ou intermediaria, e os
 * eventos delas. Cada documento vira uma linha da tbldistribuicaonfse e um .xml.gz.
 *
 * O ADN so responde com ?cnpjConsulta= (sem ele: REJEICAO sem erro). Ele devolve os
 * documentos com NSU MAIOR que o informado, em lotes, e nao diz qual e' o ultimo: le-se
 * ate vir um lote vazio.
 */
class NfseDistribuicaoService
{
    const LOTE_LOCALIZADO = 'DOCUMENTOS_LOCALIZADOS';

    public static function ultimoNsu(Filial $filial): int
    {
        return (int) DistribuicaoNfse::where('codfilial', $filial->codfilial)->max('nsu');
    }

    /**
     * Le UM lote a partir do NSU (padrao: o ultimo gravado). Devolve
     * ['ultNSU' => int, 'documentos' => int, 'fim' => bool, 'mensagem' => string].
     */
    public static function consultar(Filial $filial, ?int $nsu = null): array
    {
        return static::comTrava($filial, fn () => static::consultarLote($filial, $nsu));
    }

    /**
     * Uma leitura do ADN por filial de cada vez (robo agendado x botao da tela): quem
     * chega depois espera a vez, em vez de gravar o mesmo NSU duas vezes.
     */
    protected static function comTrava(Filial $filial, callable $fn)
    {
        return Cache::lock("nfse-dfe:{$filial->codfilial}", 300)->block(120, $fn);
    }

    protected static function consultarLote(Filial $filial, ?int $nsu = null): array
    {
        $nsu = $nsu ?? static::ultimoNsu($filial);
        $cnpj = NfseXmlService::documentoFilial($filial);
        $resp = NfseSefinService::getAdn(
            $filial,
            "contribuintes/DFe/{$nsu}?cnpjConsulta={$cnpj}&lote=true",
            "adn-dfe-{$nsu}"
        );
        $json = $resp['json'] ?? [];
        $status = $json['StatusProcessamento'] ?? null;
        $lote = $json['LoteDFe'] ?? [];

        if ($status != static::LOTE_LOCALIZADO || empty($lote)) {
            $mensagem = NfseSefinService::mensagens($json, 'Erros') ?: NfseSefinService::mensagens($json);
            // E2220: nao ha documento depois do NSU informado (a caixa esta em dia)
            if (str_starts_with($mensagem, 'E2220')) {
                $mensagem = 'Nenhum documento novo.';
            }
            return [
                'ultNSU' => $nsu,
                'documentos' => 0,
                'fim' => true,
                'mensagem' => $mensagem ?: ($status == 'NENHUM_DOCUMENTO_LOCALIZADO' || empty($status) ? 'Nenhum documento novo.' : $status),
            ];
        }

        $ultimo = $nsu;
        foreach ($lote as $item) {
            DB::transaction(fn () => static::salvar($filial, $item));
            $ultimo = max($ultimo, (int) ($item['NSU'] ?? 0));
        }
        return [
            'ultNSU' => $ultimo,
            'documentos' => count($lote),
            'fim' => false,
            'mensagem' => count($lote) . ' documento(s) recebido(s).',
        ];
    }

    /**
     * Busca de novo UM documento pelo NSU. O ADN, com lote=false, devolve o documento
     * SEGUINTE ao NSU informado: pede-se o NSU - 1.
     */
    public static function consultarNsu(Filial $filial, int $nsu): array
    {
        $cnpj = NfseXmlService::documentoFilial($filial);
        $anterior = max(0, $nsu - 1);
        $resp = NfseSefinService::getAdn(
            $filial,
            "contribuintes/DFe/{$anterior}?cnpjConsulta={$cnpj}&lote=false",
            "adn-dfe-nsu-{$nsu}"
        );
        return static::comTrava($filial, fn () => static::salvarLote($filial, $resp['json'] ?? [], "NSU {$nsu}"));
    }

    /**
     * Eventos de uma NFS-e pela chave (GET /contribuintes/NFSe/{chave}/Eventos). O ADN
     * devolve no mesmo formato do lote, com NSU (inclusive a propria NFS-e quando ela nao
     * tem evento): grava como os demais, sem duplicar.
     */
    public static function consultarEventos(Filial $filial, string $chave): array
    {
        $chave = chaveDfeLimpa($chave);
        if (strlen($chave) != 50) {
            abort(422, 'A chave da NFS-e tem 50 caracteres.');
        }
        $resp = NfseSefinService::getAdn(
            $filial,
            "contribuintes/NFSe/{$chave}/Eventos",
            "adn-eventos-{$chave}"
        );
        return static::comTrava($filial, fn () => static::salvarLote($filial, $resp['json'] ?? [], "chave {$chave}"));
    }

    /**
     * Grava os itens de uma resposta do ADN. Devolve ['documentos', 'novos', 'mensagem'].
     */
    protected static function salvarLote(Filial $filial, array $json, string $oque): array
    {
        $lote = $json['LoteDFe'] ?? [];
        if (($json['StatusProcessamento'] ?? null) != static::LOTE_LOCALIZADO || empty($lote)) {
            $mensagem = NfseSefinService::mensagens($json, 'Erros') ?: NfseSefinService::mensagens($json);
            return [
                'documentos' => 0,
                'novos' => 0,
                'mensagem' => "Nada encontrado no ADN para {$oque}." . ($mensagem ? " {$mensagem}" : ''),
            ];
        }
        $novos = 0;
        foreach ($lote as $item) {
            $existia = DistribuicaoNfse::where('codfilial', $filial->codfilial)->where('nsu', (int) $item['NSU'])->exists();
            DB::transaction(fn () => static::salvar($filial, $item));
            $novos += $existia ? 0 : 1;
        }
        $total = count($lote);
        return [
            'documentos' => $total,
            'novos' => $novos,
            'mensagem' => "{$total} documento(s) do ADN para {$oque}" . ($novos ? ", {$novos} novo(s)." : ', nenhum novo.'),
        ];
    }

    /**
     * Le lotes ate acabar (robo e comando). Devolve o total de documentos.
     */
    public static function consultarTudo(Filial $filial, int $maxLotes = 100): int
    {
        $total = 0;
        $nsu = null;
        for ($i = 0; $i < $maxLotes; $i++) {
            $ret = static::consultar($filial, $nsu);
            $total += $ret['documentos'];
            if ($ret['fim']) {
                break;
            }
            $nsu = $ret['ultNSU'];
        }
        return $total;
    }

    protected static function salvar(Filial $filial, array $item): DistribuicaoNfse
    {
        $dist = DistribuicaoNfse::firstOrNew([
            'codfilial' => $filial->codfilial,
            'nsu' => (int) $item['NSU'],
        ]);
        $dist->tipodocumento = mb_substr((string) ($item['TipoDocumento'] ?? '?'), 0, 20);
        $dist->tipoevento = empty($item['TipoEvento']) ? null : mb_substr((string) $item['TipoEvento'], 0, 50);
        $dist->nfsechave = empty($item['ChaveAcesso']) ? null : chaveDfeLimpa($item['ChaveAcesso']);
        // O ADN manda a hora de Brasilia sem fuso: converte para a do app (Cuiaba)
        $dist->data = empty($item['DataHoraGeracao'])
            ? null
            : Carbon::parse($item['DataHoraGeracao'], 'America/Sao_Paulo')->setTimezone(config('app.timezone'));
        $dist->save();

        $xml = NfseSefinService::ungzipBase64($item['ArquivoXml'] ?? null);
        if (!empty($xml)) {
            file_put_contents(NFePHPPathService::pathNfseDfe($dist, true), gzencode($xml));
        }
        return static::processar($dist, $xml);
    }

    /**
     * Le o XML do documento e preenche a linha: dados da NFS-e para a lista e o vinculo
     * com a nossa nota. Cancelamento de uma nota nossa feito fora (pelo site ou pela
     * prefeitura) leva a nota a CAN.
     */
    public static function processar(DistribuicaoNfse $dist, ?string $xml = null): DistribuicaoNfse
    {
        $xml = $xml ?? static::xml($dist);
        if (!empty($dist->nfsechave)) {
            $dist->codnotafiscalservico = NotaFiscalServico::where('nfsechave', $dist->nfsechave)->value('codnotafiscalservico');
            // A chave leva o CNPJ (pode ter letras, TASK-283) ou o CPF com zeros a esquerda do
            // emitente nas posicoes 10 a 23 (inscricao com 14 posicoes): vale para a NFS-e e
            // para os eventos, que so tem a chave
            $dist->emitida = substr($dist->nfsechave, 9, 14)
                === str_pad(NfseXmlService::documentoFilial($dist->Filial), 14, '0', STR_PAD_LEFT);
        }

        if ($dist->tipodocumento == DistribuicaoNfse::TIPO_NFSE && !empty($xml)) {
            $dom = new DOMDocument();
            if (@$dom->loadXML($xml)) {
                $emit = $dom->getElementsByTagName('emit')->item(0);
                $toma = $dom->getElementsByTagName('toma')->item(0);
                $dist->nfsenumero = (int) $dom->getElementsByTagName('nNFSe')->item(0)?->nodeValue ?: null;
                $dist->emitentecnpj = static::documento($emit);
                $dist->emitente = mb_substr((string) $emit?->getElementsByTagName('xNome')->item(0)?->nodeValue, 0, 300) ?: null;
                $dist->tomadorcnpj = static::documento($toma);
                $dist->tomador = mb_substr((string) $toma?->getElementsByTagName('xNome')->item(0)?->nodeValue, 0, 300) ?: null;
                $vServ = $dom->getElementsByTagName('vServ')->item(0)?->nodeValue;
                $dist->valorservico = $vServ !== null ? (float) $vServ : null;
            }
        }
        $dist->save();

        if (
            $dist->tipodocumento == DistribuicaoNfse::TIPO_EVENTO
            && in_array($dist->tipoevento, ['CANCELAMENTO', 'SUBSTITUICAO'])
            && !empty($xml)
            && $dist->NotaFiscalServico?->status == NotaFiscalServico::STATUS_AUTORIZADA
        ) {
            try {
                NfseService::registrarCancelamento($dist->NotaFiscalServico, $xml, null);
            } catch (\Throwable $e) {
                Log::error("NFS-e {$dist->nfsechave}: cancelamento distribuido pelo ADN nao aplicado: " . $e->getMessage());
            }
        }
        return $dist;
    }

    protected static function documento($no): ?string
    {
        if (!$no) {
            return null;
        }
        return $no->getElementsByTagName('CNPJ')->item(0)?->nodeValue
            ?? $no->getElementsByTagName('CPF')->item(0)?->nodeValue;
    }

    public static function xml(DistribuicaoNfse $dist): ?string
    {
        $path = NFePHPPathService::pathNfseDfe($dist);
        if (!file_exists($path)) {
            return null;
        }
        return gzdecode(file_get_contents($path)) ?: null;
    }

    /**
     * DANFSe da NFS-e da chave desta linha (NFS-e ou evento), nossa ou de terceiro. O XML da
     * nota nao muda: o cancelamento chega como outro documento da caixa, e com ele (ou com a
     * substituicao) o PDF sai com a marca de CANCELADA, como no processar.
     */
    public static function danfse(DistribuicaoNfse $dist): string
    {
        if (empty($dist->nfsechave)) {
            abort(422, 'Este documento não tem chave de NFS-e.');
        }
        $nfse = DistribuicaoNfse::where('nfsechave', $dist->nfsechave)
            ->where('tipodocumento', DistribuicaoNfse::TIPO_NFSE)
            ->orderByDesc('nsu')
            ->first();
        $xml = $nfse ? static::xml($nfse) : null;
        if (empty($xml)) {
            abort(404, 'A NFS-e desta chave não está na caixa. Busque pelo botão "Eventos desta NFS-e no ADN".');
        }
        $cancelada = DistribuicaoNfse::where('nfsechave', $dist->nfsechave)
            ->where('tipodocumento', DistribuicaoNfse::TIPO_EVENTO)
            ->whereIn('tipoevento', ['CANCELAMENTO', 'SUBSTITUICAO'])
            ->exists();
        try {
            return NfseService::renderDanfse($xml, $cancelada);
        } catch (\Throwable $e) {
            // XML de outro municipio ou de outro leiaute que o sped-da nao desenha
            Log::error("DANFSe da NFS-e {$dist->nfsechave} (caixa do ADN): " . $e->getMessage());
            abort(422, 'Não foi possível gerar o DANFSe desta NFS-e: ' . $e->getMessage());
        }
    }

    /**
     * Filiais que emitem NFS-e, com o ultimo NSU lido (para a janela de consulta).
     */
    public static function filiaisHabilitadas(): array
    {
        return Filial::where('emitenfse', true)
            ->whereNull('inativo')
            ->orderBy('codfilial')
            ->get(['codfilial', 'filial'])
            ->map(fn ($f) => [
                'codfilial' => $f->codfilial,
                'filial' => $f->filial,
                'nsu' => static::ultimoNsu($f),
            ])
            ->all();
    }

    public static function pesquisar(array $filtros)
    {
        $qry = DistribuicaoNfse::with(['Filial', 'NotaFiscalServico']);
        if (!empty($filtros['codfilial'])) {
            $qry->where('codfilial', $filtros['codfilial']);
        }
        if (!empty($filtros['datade'])) {
            $qry->where('data', '>=', $filtros['datade'] . ' 00:00:00');
        }
        if (!empty($filtros['dataate'])) {
            $qry->where('data', '<=', $filtros['dataate'] . ' 23:59:59');
        }
        if (!empty($filtros['nfsechave'])) {
            $chave = chaveDfeLimpa($filtros['nfsechave']);
            $qry->where('nfsechave', 'ilike', "%{$chave}%");
        }
        if (!empty($filtros['tipodocumento'])) {
            $qry->where('tipodocumento', $filtros['tipodocumento']);
        }
        // "true"/"false" em texto na query string: false e' filtro valido (emitida por terceiro).
        // Sem o parametro (ou vazio) nao filtra: o filter_var leria null e '' como false.
        if (isset($filtros['emitida']) && $filtros['emitida'] !== '') {
            $emitida = filter_var($filtros['emitida'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($emitida !== null) {
                $qry->where('emitida', $emitida);
            }
        }
        if (!empty($filtros['nsude'])) {
            $qry->where('nsu', '>=', (int) $filtros['nsude']);
        }
        if (!empty($filtros['nsuate'])) {
            $qry->where('nsu', '<=', (int) $filtros['nsuate']);
        }
        return $qry->orderByDesc('data')->orderByDesc('nsu');
    }
}
