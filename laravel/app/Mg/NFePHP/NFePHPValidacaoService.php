<?php

namespace Mg\NFePHP;

use DB;

use Mg\NotaFiscal\NotaFiscal;
use Mg\Pessoa\Pessoa;
use Mg\Filial\Empresa;
use Mg\NotaFiscal\NotaFiscalService;

class NFePHPValidacaoService
{
    public static function validar(NotaFiscal $nf)
    {
        static::validarEmitente($nf);
        static::validarPessoaNFCe($nf);
        static::validarPessoaNFe($nf);
        static::validarOffLine($nf);
        static::validarCertidoes($nf);
    }

    public static function validarEmitente(NotaFiscal $nf)
    {
        if (!$nf->emitida) {
            throw new \Exception('Nota Fiscal não é de nossa emissão!');
        }
        return true;
    }

    public static function validarPessoaNFCe(NotaFiscal $nf)
    {
        if ($nf->modelo != NotaFiscalService::MODELO_NFCE) {
            return true;
        }
        if (!empty($nf->Pessoa->ie)) {
            throw new \Exception('Não é permitida emissão de NFCe para Pessoas com Inscrição Estadual!');
        }
        return true;
    }

    public static function validarPessoaNFe(NotaFiscal $nf)
    {
        if ($nf->modelo != NotaFiscalService::MODELO_NFE) {
            return true;
        }
        if ($nf->codpessoa == Pessoa::CONSUMIDOR) {
            throw new \Exception('Não é permitida emissão de NFe para Consumidor!');
        }
        return true;
    }

    public static function validarOffLine(NotaFiscal $nf)
    {
        if ($nf->modelo != NotaFiscalService::MODELO_NFCE) {
            return true;
        }
        if ($nf->Filial->Empresa->modoemissaonfce != Empresa::MODOEMISSAONFCE_OFFLINE) {
            return true;
        }
        if ($nf->NaturezaOperacao->finnfe != 1) {
            throw new \Exception("Finalidade de emissão {$nf->NaturezaOperacao->finnfe} da Natureza de Operação não permite emissão OFFLINE!");
        }
        if (!$nf->Pessoa->consumidor) {
            throw new \Exception('Só é permitida emissão OffLine para Consumidor Final e esta Pessoa não está marcada como Consumidor Final!');
        }
        if ($nf->Pessoa->Cidade->codestado != $nf->Filial->Pessoa->Cidade->codestado) {
            throw new \Exception('Não é permitida emissão OffLine para Pessoas de fora do estado!');
        }
        return true;
    }

    public static function validarCertidoes(NotaFiscal $nf)
    {
        // Se nao precisar de certidao
        if ($nf->NotaFiscalProdutoBarraS()->where('certidaosefazmt', true)->count() <= 0) {
            return true;
        }
        // verifica se destinatario tem certidao
        if (!$nf->Pessoa->certidaoSefazMT()) {
            throw new \Exception("Não existe certidão negativa válida para '{$nf->Pessoa->fantasia}'!");
        }
        // verifica se emitente tem certidao
        if (!$nf->Filial->Pessoa->certidaoSefazMT()) {
            throw new \Exception("Não existe certidão negativa válida para '{$nf->Filial->Pessoa->fantasia}'!");
        }
        return true;
    }
}
