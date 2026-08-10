<?php

namespace Mg\NFePHP;

use Illuminate\Database\Eloquent\Model;

use Mg\Filial\Filial;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Usuario\Usuario;

/**
 * Registro de uma conversa com a SEFAZ (uma tentativa, nao uma operacao).
 *
 * Tabela append-only: nunca se altera uma linha. Por isso o model nao tem os campos de
 * alteracao do padrao de auditoria — ver comentario em database/sefaz_comunicacao.sql.
 *
 * Estende Model e nao MgModel de proposito: o boot() do MgModel carimba
 * codusuarioalteracao em todo creating, e essa coluna nao existe aqui.
 * O codusuariocriacao e preenchido pelo SefazLogService.
 */
class SefazComunicacao extends Model
{
    protected $table = 'tblsefazcomunicacao';
    protected $primaryKey = 'codsefazcomunicacao';

    public $timestamps = false;

    protected $fillable = [
        'codfilial',
        'codnotafiscal',
        'operacao',
        'ambiente',
        'tentativa',
        'httpcode',
        'cstat',
        'xmotivo',
        'duracaoms',
        'sucesso',
        'erro',
        'arquivo',
        'codusuariocriacao',
    ];

    protected $casts = [
        'codsefazcomunicacao' => 'integer',
        'codfilial' => 'integer',
        'codnotafiscal' => 'integer',
        'ambiente' => 'integer',
        'tentativa' => 'integer',
        'httpcode' => 'integer',
        'duracaoms' => 'integer',
        'sucesso' => 'boolean',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
    ];

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }
}
