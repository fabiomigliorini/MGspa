<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:31:51
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Usuario\Usuario;

class NotaFiscalDuplicatas extends MgModel
{
    protected $table = 'tblnotafiscalduplicatas';
    protected $primaryKey = 'codnotafiscalduplicatas';


    protected $fillable = [
        'codnotafiscal',
        'fatura',
        'valor',
        'vencimento'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnotafiscal' => 'integer',
        'codnotafiscalduplicatas' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'valor' => 'float',
        'vencimento' => 'date'
    ];


    // Chaves Estrangeiras
    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

}
