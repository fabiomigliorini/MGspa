<?php

namespace Mg\Filial;

use Mg\Portador\Portador;

class CertificadoService
{
    public static function pfxPath(Filial $filial): string
    {
        $path = env('NFE_PHP_PATH') . "/Certs/{$filial->codfilial}.pfx";
        if (!file_exists($path)) {
            throw new \Exception("Filial não possui certificado válido!");
        }
        return $path;
    }

    public static function pfxConteudo(Filial $filial): string
    {
        return file_get_contents(static::pfxPath($filial));
    }

    public static function pfxSenha(Filial $filial): string
    {
        if (empty($filial->senhacertificado)) {
            throw new \Exception("Senha do Certificado da filial Incorreta!");
        }
        return $filial->senhacertificado;
    }

    public static function validarValidade(Filial $filial): void
    {
        $pfxPath = static::pfxPath($filial);
        $pfxConteudo = file_get_contents($pfxPath);
        $senha = static::pfxSenha($filial);
        $certs = [];
        if (!openssl_pkcs12_read($pfxConteudo, $certs, $senha)) {
            throw new \Exception("Senha do Certificado da filial Incorreta!");
        }
        $certData = openssl_x509_parse($certs['cert']);
        if (!empty($certData['validTo_time_t']) && $certData['validTo_time_t'] < time()) {
            throw new \Exception("Certificado da filial expirado!");
        }
    }

    public static function opcoesCurlMTls(Filial $filial): array
    {
        static::validarValidade($filial);
        return [
            CURLOPT_SSLCERT => static::pfxPath($filial),
            CURLOPT_SSLCERTTYPE => 'P12',
            CURLOPT_SSLCERTPASSWD => static::pfxSenha($filial),
        ];
    }

    public static function filialDoPortador(Portador $portador): Filial
    {
        if (empty($portador->codfilial)) {
            throw new \Exception("Portador não está ligado à nenhuma Filial, impossível determinar certificado");
        }
        return $portador->Filial;
    }
}
