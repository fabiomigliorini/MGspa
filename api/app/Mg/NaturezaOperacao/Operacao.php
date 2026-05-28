<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:27:08
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Usuario\Usuario;

class Operacao extends MgModel
{
    const ENTRADA = 1;
    const SAIDA = 2;

    protected $table = 'tbloperacao';
    protected $primaryKey = 'codoperacao';


    protected $fillable = [
        'operacao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codoperacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codoperacao', 'codoperacao');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codoperacao', 'codoperacao');
    }

    public function NotaFiscalS()
    {
        return $this->hasMany(NotaFiscal::class, 'codoperacao', 'codoperacao');
    }

    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codoperacao', 'codoperacao');
    }

}
