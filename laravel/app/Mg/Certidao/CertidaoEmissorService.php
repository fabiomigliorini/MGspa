<?php

namespace Mg\Certidao;

use Mg\MgService;

class CertidaoEmissorService extends MgService
{
    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = CertidaoEmissor::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['certidaoemissor'])) {
            $qry->palavras('certidaoemissor', $filter['certidaoemissor']);
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function create(array $data): CertidaoEmissor
    {
        $reg = new CertidaoEmissor($data);
        $reg->save();
        return $reg->refresh();
    }

    public static function update(CertidaoEmissor $reg, array $data): CertidaoEmissor
    {
        $reg->fill($data);
        $reg->save();
        return $reg;
    }
}
