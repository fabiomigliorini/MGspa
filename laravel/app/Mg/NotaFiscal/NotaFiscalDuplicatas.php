<?php
/**
 * Created by php artisan gerador:model.
 * Date: 24/Jan/2024 14:49:40
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

    protected $dates = [
        'alteracao',
        'criacao',
        'vencimento'
    ];

    protected $casts = [
        'codnotafiscal' => 'integer',
        'codnotafiscalduplicatas' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'valor' => 'float'
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