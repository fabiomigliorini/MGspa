<?php

namespace Mg\NotaFiscalServico\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use Mg\NotaFiscalServico\NotaFiscalServico;
use Mg\NotaFiscalServico\TributacaoNacional;

/**
 * DANFSe + XML da NFS-e ao tomador (TASK-279). O NfseService gera os arquivos antes
 * de enfileirar; aqui so anexa.
 */
class NfseAutorizadaMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $nfs;
    protected $pathDanfse;
    protected $pathXml;

    public function __construct(NotaFiscalServico $nfs, string $pathDanfse, string $pathXml)
    {
        $this->nfs = $nfs;
        $this->pathDanfse = $pathDanfse;
        $this->pathXml = $pathXml;
    }

    public function build()
    {
        foreach ([$this->pathDanfse, $this->pathXml] as $path) {
            if (!file_exists($path)) {
                throw new \Exception("Arquivo não localizado ({$path})!");
            }
        }

        return $this
            ->subject("Nota Fiscal de Serviço {$this->nfs->nfsenumero} {$this->nfs->Filial->Pessoa->fantasia}")
            ->attach($this->pathDanfse)
            ->attach($this->pathXml)
            ->view('nfse-mail.autorizada')
            ->with([
                'nfs' => $this->nfs,
                'codigoServico' => TributacaoNacional::formataCodigo($this->nfs->TributacaoNacional?->codigo),
            ]);
    }
}
