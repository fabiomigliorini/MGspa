<?php

namespace Mg\NotaFiscalServico;

use Carbon\Carbon;
use DOMDocument;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Mg\NFePHP\NFePHPPathService;
use Mg\NotaFiscalServico\Mail\NfseAutorizadaMail;
use NFePHP\DA\NFSe\Danfse;

/**
 * Emissao, consulta, cancelamento, DANFSe e e-mail da NFS-e Nacional (TASK-279).
 *
 * Cada acao devolve ['sucesso' => bool, 'mensagem' => string] e deixa a nota com o
 * status que o Sistema Nacional confirmou. Rejeicao nao e' excecao: vira ERR com a
 * mensagem gravada na nota. Uma acao por vez por nota (lock).
 */
class NfseService
{
    const LOCK_SEGUNDOS = 120;

    // Caixa "Descricao do Servico" do DANFSe do sped-da: 3 linhas inteiras de ~130 caracteres
    const DANFSE_LINHAS_DESCRICAO = 3;
    const DANFSE_CARACTERES_LINHA = 130;
    // Texto corrido: cabem ~4 linhas (~130 caracteres cada); acima disso invade o bloco de baixo
    const DANFSE_CARACTERES_DESCRICAO = 500;

    protected static function lock(NotaFiscalServico $nfs, callable $fn): array
    {
        $lock = Cache::lock("nfse:{$nfs->codnotafiscalservico}", static::LOCK_SEGUNDOS);
        if (!$lock->get()) {
            return [
                'sucesso' => false,
                'mensagem' => 'Outra operação já está em andamento nesta nota. Aguarde e tente de novo.',
            ];
        }
        try {
            return $fn();
        } finally {
            $lock->release();
        }
    }

    /**
     * Numera a DPS (so na primeira vez) e grava a hora da emissao (a cada envio: o
     * Sistema Nacional recusa dhEmi muito antigo). UPDATE atomico, como na NF-e.
     */
    protected static function numerar(NotaFiscalServico $nfs): void
    {
        $sequence = "tblnotafiscalservico_numero_{$nfs->codfilial}_{$nfs->serie}_seq";
        DB::statement("CREATE SEQUENCE IF NOT EXISTS {$sequence}");
        DB::update("
            UPDATE tblnotafiscalservico
            SET numero = coalesce(numero, nextval('{$sequence}')),
                emissao = ?,
                status = ?
            WHERE codnotafiscalservico = ?
        ", [
            Carbon::now(),
            NotaFiscalServico::STATUS_ERRO,
            $nfs->codnotafiscalservico,
        ]);
        $nfs->refresh();
    }

    public static function enviar(NotaFiscalServico $nfs): array
    {
        if (!in_array($nfs->status, [NotaFiscalServico::STATUS_DIGITACAO, NotaFiscalServico::STATUS_ERRO])) {
            return [
                'sucesso' => false,
                'mensagem' => 'Só dá para enviar nota em digitação ou não autorizada.',
            ];
        }
        $filial = NotaFiscalServicoService::verificarFilial($nfs->codfilial);

        // Percentuais do Simples faltando (o Duplicar deixa vazio quando o mes nao esta
        // no cadastro da filial): recusa antes de numerar, sem virar ERR (TASK-280)
        if (NfseXmlService::simples($filial) && empty($nfs->tributospercentual)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Falta o % de Tributos do Simples: cadastre o mês na filial ou informe na nota.',
            ];
        }
        if ($nfs->issretido && empty($nfs->issaliquota)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Falta a alíquota do ISS retido: cadastre o mês na filial ou informe na nota.',
            ];
        }

        return static::lock($nfs, function () use ($nfs) {
            // Reenvio: a tentativa anterior pode ter sido autorizada sem a resposta chegar.
            if (!empty($nfs->numero)) {
                $consulta = static::buscarNfseDaDps($nfs);
                if ($consulta['sucesso']) {
                    return $consulta;
                }
            }

            static::numerar($nfs);
            $idDps = NfseXmlService::idDps($nfs);

            try {
                $xml = NfseXmlService::dps($nfs, NfseSefinService::ambiente(), NfseSefinService::leiauteRtc());
                $xml = NfseSefinService::assinar($nfs->Filial, $xml, 'infDPS', 'DPS');
                NfseSefinService::validar($xml, 'DPS_v1.01.xsd');
                file_put_contents(NFePHPPathService::pathNfseDps($nfs, $idDps, true), $xml);
            } catch (\Throwable $e) {
                return static::erro($nfs, 'DPS não enviada: ' . $e->getMessage());
            }

            try {
                $resp = NfseSefinService::enviarDps($nfs, $idDps, $xml);
            } catch (\Throwable $e) {
                return static::erro($nfs, $e->getMessage() . ' Use Consultar para saber se a nota foi autorizada.');
            }

            $xmlNfse = NfseSefinService::ungzipBase64($resp['json']['nfseXmlGZipB64'] ?? null);
            if ($resp['status'] >= 200 && $resp['status'] < 300 && !empty($xmlNfse)) {
                $ret = static::autorizar($nfs, $xmlNfse, $resp['json']['chaveAcesso'] ?? null);
                $alertas = NfseSefinService::mensagens($resp['json'], 'alertas');
                if (!empty($alertas)) {
                    $ret['mensagem'] .= "\nAlertas: {$alertas}";
                }
                return $ret;
            }

            $mensagem = NfseSefinService::mensagens($resp['json']);
            if (empty($mensagem)) {
                $mensagem = "O Sistema Nacional respondeu HTTP {$resp['status']}: " . mb_substr(strip_tags($resp['corpo']), 0, 500);
            }
            return static::erro($nfs, $mensagem);
        });
    }

    /**
     * Pergunta ao Sistema Nacional pela DPS e, se ela virou NFS-e, pelo cancelamento.
     */
    public static function consultar(NotaFiscalServico $nfs): array
    {
        if (empty($nfs->numero)) {
            return ['sucesso' => false, 'mensagem' => 'A nota ainda não foi enviada.'];
        }
        return static::lock($nfs, function () use ($nfs) {
            $ret = static::buscarNfseDaDps($nfs);
            if (!$ret['sucesso']) {
                if ($nfs->status == NotaFiscalServico::STATUS_ERRO) {
                    $nfs->mensagem = $ret['mensagem'];
                    $nfs->save();
                }
                return $ret;
            }
            $nfs->refresh();
            static::buscarCancelamento($nfs);
            $nfs->refresh();
            if ($nfs->status == NotaFiscalServico::STATUS_CANCELADA) {
                return [
                    'sucesso' => true,
                    'mensagem' => "NFS-e {$nfs->nfsenumero} cancelada em " . $nfs->nfsecancelamento?->format('d/m/Y H:i') . '.',
                ];
            }
            return $ret;
        });
    }

    /**
     * GET /dps/{id} e GET /nfse/{chave}. Autoriza a nota se achar.
     */
    protected static function buscarNfseDaDps(NotaFiscalServico $nfs): array
    {
        $idDps = NfseXmlService::idDps($nfs);
        try {
            $resp = NfseSefinService::consultarDps($nfs, $idDps);
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
        $chave = $resp['json']['chaveAcesso'] ?? $resp['json']['ChaveAcesso'] ?? null;
        if ($resp['status'] == 404 || empty($chave)) {
            $mensagem = NfseSefinService::mensagens($resp['json']);
            return [
                'sucesso' => false,
                'mensagem' => 'A DPS não consta como NFS-e no Sistema Nacional.' . (empty($mensagem) ? '' : " {$mensagem}"),
            ];
        }
        if ($nfs->nfsechave == $chave && $nfs->status != NotaFiscalServico::STATUS_ERRO && file_exists(NFePHPPathService::pathNfseAutorizada($nfs))) {
            return ['sucesso' => true, 'mensagem' => "NFS-e {$nfs->nfsenumero} autorizada."];
        }
        try {
            $respNfse = NfseSefinService::consultarNfse($nfs, $idDps, $chave);
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
        $xmlNfse = NfseSefinService::ungzipBase64($respNfse['json']['nfseXmlGZipB64'] ?? null);
        if (empty($xmlNfse)) {
            return [
                'sucesso' => false,
                'mensagem' => "A DPS virou a NFS-e {$chave}, mas o XML não veio: " . NfseSefinService::mensagens($respNfse['json']),
            ];
        }
        return static::autorizar($nfs, $xmlNfse, $chave);
    }

    /**
     * GET /nfse/{chave}/eventos/101101/1. Se existir, a nota foi cancelada (por nos,
     * pelo site ou pela prefeitura). Nulo quando nao ha cancelamento.
     */
    protected static function buscarCancelamento(NotaFiscalServico $nfs): ?array
    {
        if ($nfs->status == NotaFiscalServico::STATUS_CANCELADA) {
            return null;
        }
        try {
            $resp = NfseSefinService::consultarEvento($nfs, NfseXmlService::idDps($nfs), NfseXmlService::TIPO_EVENTO_CANCELAMENTO);
        } catch (\Throwable $e) {
            return null;
        }
        // { eventos: [ { tipoEvento: 101101, arquivoXml: ... } ] }
        $evento = collect($resp['json']['eventos'] ?? [])->firstWhere('tipoEvento', (int) NfseXmlService::TIPO_EVENTO_CANCELAMENTO);
        $xml = NfseSefinService::ungzipBase64($evento['arquivoXml'] ?? null);
        if ($resp['status'] != 200 || empty($xml)) {
            return null;
        }
        return static::registrarCancelamento($nfs, $xml, null);
    }

    public static function cancelar(NotaFiscalServico $nfs, int $motivo, string $justificativa): array
    {
        if ($nfs->status != NotaFiscalServico::STATUS_AUTORIZADA) {
            return ['sucesso' => false, 'mensagem' => 'Só dá para cancelar nota autorizada.'];
        }
        return static::lock($nfs, function () use ($nfs, $motivo, $justificativa) {
            $idDps = NfseXmlService::idDps($nfs);
            try {
                $xml = NfseXmlService::cancelamento($nfs, NfseSefinService::ambiente(), $motivo, $justificativa);
                $xml = NfseSefinService::assinar($nfs->Filial, $xml, 'infPedReg', 'pedRegEvento');
                NfseSefinService::validar($xml, 'pedRegEvento_v1.01.xsd');
            } catch (\Throwable $e) {
                return ['sucesso' => false, 'mensagem' => 'Pedido de cancelamento inválido: ' . $e->getMessage()];
            }
            try {
                $resp = NfseSefinService::registrarEvento($nfs, $idDps, $xml);
            } catch (\Throwable $e) {
                return ['sucesso' => false, 'mensagem' => $e->getMessage() . ' Use Consultar para saber se o cancelamento foi registrado.'];
            }
            $xmlEvento = NfseSefinService::ungzipBase64(
                $resp['json']['eventoXmlGZipB64'] ?? $resp['json']['eventoNfseXmlGZipB64'] ?? null
            );
            if ($resp['status'] >= 200 && $resp['status'] < 300 && !empty($xmlEvento)) {
                return static::registrarCancelamento($nfs, $xmlEvento, $justificativa);
            }
            $mensagem = NfseSefinService::mensagens($resp['json']);
            if (empty($mensagem)) {
                $mensagem = "O Sistema Nacional respondeu HTTP {$resp['status']}: " . mb_substr(strip_tags($resp['corpo']), 0, 500);
            }
            return ['sucesso' => false, 'mensagem' => "Cancelamento recusado: {$mensagem}"];
        });
    }

    protected static function autorizar(NotaFiscalServico $nfs, string $xmlNfse, ?string $chave): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($xmlNfse);
        $infNfse = $dom->getElementsByTagName('infNFSe')->item(0);
        if (empty($chave) && $infNfse) {
            // Id = 'NFS' + chave; a chave pode ter letras no CNPJ do emitente (TASK-283)
            $chave = chaveDfeLimpa(preg_replace('/^NFS/', '', (string) $infNfse->getAttribute('Id')));
        }
        $numero = $dom->getElementsByTagName('nNFSe')->item(0)?->nodeValue;
        $dhProc = $dom->getElementsByTagName('dhProc')->item(0)?->nodeValue;

        $nfs->nfsechave = $chave;
        $nfs->nfsenumero = $numero ? (int) $numero : null;
        $nfs->nfseautorizacao = $dhProc ? Carbon::parse($dhProc)->setTimezone(config('app.timezone')) : Carbon::now();
        $nfs->status = NotaFiscalServico::STATUS_AUTORIZADA;
        $nfs->mensagem = null;
        $nfs->save();

        // A autorizacao ja vale: falha ao gravar o arquivo nao volta a nota para ERR.
        // O Consultar busca o XML de novo quando o arquivo nao existe.
        try {
            file_put_contents(NFePHPPathService::pathNfseAutorizada($nfs, true), $xmlNfse);
        } catch (\Throwable $e) {
            Log::error("NFS-e {$nfs->nfsechave} autorizada, mas o XML nao foi gravado: " . $e->getMessage());
            $nfs->mensagem = 'Autorizada, mas o XML não foi gravado (' . $e->getMessage() . '). Use Consultar para buscar de novo.';
            $nfs->save();
            return ['sucesso' => true, 'mensagem' => "NFS-e {$nfs->nfsenumero} autorizada, mas o XML não foi gravado: use Consultar."];
        }

        return ['sucesso' => true, 'mensagem' => "NFS-e {$nfs->nfsenumero} autorizada."];
    }

    public static function registrarCancelamento(NotaFiscalServico $nfs, string $xmlEvento, ?string $justificativa): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($xmlEvento);
        $dh = $dom->getElementsByTagName('dhProc')->item(0)?->nodeValue
            ?? $dom->getElementsByTagName('dhEvento')->item(0)?->nodeValue;
        $motivo = $dom->getElementsByTagName('xMotivo')->item(0)?->nodeValue;

        $nfs->status = NotaFiscalServico::STATUS_CANCELADA;
        $nfs->nfsecancelamento = $dh ? Carbon::parse($dh)->setTimezone(config('app.timezone')) : Carbon::now();
        $nfs->justificativa = mb_substr($justificativa ?? $motivo ?? '', 0, 255) ?: null;
        $nfs->save();
        try {
            file_put_contents(NFePHPPathService::pathNfseCancelada($nfs, true), $xmlEvento);
        } catch (\Throwable $e) {
            Log::error("NFS-e {$nfs->nfsechave} cancelada, mas o XML do evento nao foi gravado: " . $e->getMessage());
        }

        return ['sucesso' => true, 'mensagem' => "NFS-e {$nfs->nfsenumero} cancelada."];
    }

    protected static function erro(NotaFiscalServico $nfs, string $mensagem): array
    {
        $nfs->status = NotaFiscalServico::STATUS_ERRO;
        $nfs->mensagem = $mensagem;
        $nfs->save();
        return ['sucesso' => false, 'mensagem' => $mensagem];
    }

    /**
     * XML pelo tipo: dps (a ultima enviada), nfse ou cancelamento.
     */
    public static function xml(NotaFiscalServico $nfs, string $tipo): string
    {
        switch ($tipo) {
            case 'dps':
                $path = empty($nfs->numero) ? null : NFePHPPathService::pathNfseDps($nfs, NfseXmlService::idDps($nfs));
                break;
            case 'nfse':
                $path = empty($nfs->nfsechave) ? null : NFePHPPathService::pathNfseAutorizada($nfs);
                break;
            case 'cancelamento':
                $path = empty($nfs->nfsechave) ? null : NFePHPPathService::pathNfseCancelada($nfs);
                break;
            default:
                abort(404, "Tipo de XML desconhecido: {$tipo}");
        }
        if (empty($path) || !file_exists($path)) {
            abort(404, 'Arquivo XML não encontrado.');
        }
        return file_get_contents($path);
    }

    /**
     * Gera o DANFSe (sped-da) e devolve o caminho do PDF. Sempre regera: a mesma NFS-e
     * ganha a marca de CANCELADA depois do cancelamento.
     */
    public static function danfse(NotaFiscalServico $nfs): string
    {
        if (!in_array($nfs->status, [NotaFiscalServico::STATUS_AUTORIZADA, NotaFiscalServico::STATUS_CANCELADA])) {
            abort(422, 'O DANFSe só existe para nota autorizada ou cancelada.');
        }
        $pdf = static::renderDanfse(static::xml($nfs, 'nfse'), $nfs->status == NotaFiscalServico::STATUS_CANCELADA);
        $path = NFePHPPathService::pathDanfse($nfs, true);
        file_put_contents($path, $pdf);
        return $path;
    }

    /**
     * PDF do DANFSe a partir do XML da NFS-e: o da nossa nota e o de qualquer NFS-e da caixa
     * do ADN (NfseDistribuicaoService::danfse).
     */
    public static function renderDanfse(string $xml, bool $cancelada): string
    {
        $danfse = new Danfse(static::xmlParaDanfse($xml));
        if ($cancelada) {
            $danfse->setAsCanceled();
        }
        return $danfse->render();
    }

    /**
     * A NFS-e nao aceita quebra de linha: na emissao ela vira " | " (NfseXmlService::texto).
     * Para o DANFSe a separacao volta a ser quebra de linha nas informacoes complementares,
     * e na descricao do servico quando couber. So na copia que vai para o PDF: o XML fiscal fica como
     * o Sistema Nacional autorizou.
     */
    protected static function xmlParaDanfse(string $xml): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->loadXML($xml);
        // Informacoes complementares: a caixa vai ate o fim da pagina, sempre cabe
        foreach ($dom->getElementsByTagName('xInfComp') as $no) {
            $no->textContent = str_replace(' | ', "\n", $no->textContent);
        }
        // Descricao do servico: a caixa do sped-da tem altura fixa (3 linhas inteiras a 7pt) e o
        // que passa invade o bloco de baixo. So quebra a linha se couber; senao fica o
        // texto corrido com " | ", que ocupa menos.
        foreach ($dom->getElementsByTagName('xDescServ') as $no) {
            $partes = explode(' | ', $no->textContent);
            $linhas = array_sum(array_map(
                fn ($p) => max(1, (int) ceil(mb_strlen($p) / static::DANFSE_CARACTERES_LINHA)),
                $partes
            ));
            if ($linhas <= static::DANFSE_LINHAS_DESCRICAO) {
                $no->textContent = implode("\n", $partes);
            } elseif (mb_strlen($no->textContent) > static::DANFSE_CARACTERES_DESCRICAO) {
                // Nem corrido cabe: corta so no PDF (o XML e a tela tem o texto inteiro)
                $no->textContent = rtrim(mb_substr($no->textContent, 0, static::DANFSE_CARACTERES_DESCRICAO - 3)) . '...';
            }
        }
        return $dom->saveXML();
    }

    public static function mail(NotaFiscalServico $nfs, ?string $destinatario = null): array
    {
        if ($nfs->status != NotaFiscalServico::STATUS_AUTORIZADA) {
            return ['sucesso' => false, 'mensagem' => 'Só dá para enviar e-mail de nota autorizada.'];
        }
        if (empty($destinatario) && $nfs->Pessoa) {
            $destinatario = $nfs->Pessoa->PessoaEmailS()->where('nfe', true)->get()->pluck('email')->implode(',');
        }
        $destinatarios = array_values(array_filter(array_map('trim', explode(',', (string) $destinatario))));
        if (empty($destinatarios)) {
            return ['sucesso' => false, 'mensagem' => 'Não foi informado nenhum destinatário.'];
        }
        foreach ($destinatarios as $email) {
            if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
                return ['sucesso' => false, 'mensagem' => "E-mail inválido: {$email}"];
            }
        }
        // PDF e XML prontos antes de enfileirar: o worker so anexa
        $pathDanfse = static::danfse($nfs);
        $pathXml = NFePHPPathService::pathNfseAutorizada($nfs);
        Mail::to($destinatarios)->queue(new NfseAutorizadaMail($nfs, $pathDanfse, $pathXml));

        return ['sucesso' => true, 'mensagem' => 'E-mail enviado para: ' . implode(', ', $destinatarios)];
    }
}
