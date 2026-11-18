<?php

namespace Mg\NotaFiscalServico;

use Mg\MgModel;

/**
 * Nomenclatura Brasileira de Servicos (TASK-279): o "NCM dos servicos", 9 digitos.
 * Tabela oficial (Anexo B), importada pelo api/database/nfse_tabelas.sql. Os NBS
 * validos para cada item da LC 116 estao na tblnbsitem (Anexo VIII).
 */
class Nbs extends MgModel
{
    protected $table = 'tblnbs';
    protected $primaryKey = 'codnbs';

    protected $fillable = [
        'codigo',
        'descricao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnbs' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    // 101011100 -> 1.0101.11.00
    public static function formataCodigo(?string $codigo): ?string
    {
        if (empty($codigo) || strlen($codigo) != 9) {
            return $codigo;
        }
        return substr($codigo, 0, 1) . '.' . substr($codigo, 1, 4) . '.' . substr($codigo, 5, 2) . '.' . substr($codigo, 7, 2);
    }
}
