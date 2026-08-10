<?php

namespace Mg\NFePHP;

use Illuminate\Support\Facades\DB;

use Mg\NotaFiscal\NotaFiscal;
use Mg\Pessoa\Pessoa;
use Mg\Filial\Empresa;
use Mg\NotaFiscal\NotaFiscalService;

class NFePHPValidacaoService
{
    public static function validar(NotaFiscal $nf, ?int $tpEmis = null)
    {
        static::validarEmitente($nf);
        static::validarPessoaNFCe($nf);
        static::validarPessoaNFe($nf);
        static::validarOffLine($nf, $tpEmis);
        static::validarCertidoes($nf);
        static::validarPagamento($nf);
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
        if (!$nf->Pessoa->fisica) {
            throw new \Exception('Não é permitida emissão de NFCe para CNPJ!');
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

    /**
     * Regras de quem pode ser emitido off-line.
     *
     * Recebe o tpEmis JA RESOLVIDO em vez de reconsultar Empresa->modoemissaonfce: com o
     * override manual (o usuario avancado forcando offline), a empresa pode estar em modo
     * normal e a nota sair offline mesmo assim — e nesse caso a validacao precisa valer.
     * Antes essa nota passava batido.
     */
    public static function validarOffLine(NotaFiscal $nf, ?int $tpEmis = null)
    {
        if ($nf->modelo != NotaFiscalService::MODELO_NFCE) {
            return true;
        }
        $tpEmis = $tpEmis ?? $nf->Filial->Empresa->modoemissaonfce;
        if ($tpEmis != NotaFiscalService::TPEMIS_OFFLINE) {
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

    public static function validarPagamento(NotaFiscal $nf)
    {
        $pago = floatval($nf->NotaFiscalPagamentoS()->sum(DB::raw('coalesce(valorpagamento, 0) - coalesce(troco, 0)')));
        if (($pago - $nf->valortotal) >= 0.01) {
            $pago = formataNumero($pago);
            $nota = formataNumero($nf->valortotal);
            throw new \Exception("Valor dos pagamentos '{$pago}' maior que valor da nota '{$nota}'!");
        }
    }
}
