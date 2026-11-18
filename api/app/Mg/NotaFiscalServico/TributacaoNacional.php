<?php

namespace Mg\NotaFiscalServico;

use Mg\MgModel;

/**
 * Codigo de tributacao nacional da NFS-e (TASK-279): 6 digitos = item, subitem e
 * desdobro nacional da LC 116. Tabela oficial (Anexo B), importada pelo
 * api/database/nfse_tabelas.sql.
 */
class TributacaoNacional extends MgModel
{
    protected $table = 'tbltributacaonacional';
    protected $primaryKey = 'codtributacaonacional';

    protected $fillable = [
        'codigo',
        'item',
        'descricao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codtributacaonacional' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    // 170202 -> 17.02.02
    public static function formataCodigo(?string $codigo): ?string
    {
        if (empty($codigo) || strlen($codigo) != 6) {
            return $codigo;
        }
        return substr($codigo, 0, 2) . '.' . substr($codigo, 2, 2) . '.' . substr($codigo, 4, 2);
    }
}
