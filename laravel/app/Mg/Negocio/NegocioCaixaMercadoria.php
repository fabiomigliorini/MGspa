<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 11:59:51
 */

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\CaixaMercadoria\CaixaMercadoria;
use Mg\Negocio\Negocio;
use Mg\Usuario\Usuario;

class NegocioCaixaMercadoria extends MgModel
{
    protected $table = 'tblnegociocaixamercadoria';
    protected $primaryKey = 'codnegociocaixamercadoria';


    protected $fillable = [
        'codcaixamercadoria',
        'codnegocio',
        'codusuariorecebimento',
        'recebimento'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'recebimento'
    ];

    protected $casts = [
        'codcaixamercadoria' => 'integer',
        'codnegocio' => 'integer',
        'codnegociocaixamercadoria' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuariorecebimento' => 'integer'
    ];


    // Chaves Estrangeiras
    public function CaixaMercadoria()
    {
        return $this->belongsTo(CaixaMercadoria::class, 'codcaixamercadoria', 'codcaixamercadoria');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioRecebimento()
    {
        return $this->belongsTo(Usuario::class, 'codusuariorecebimento', 'codusuario');
    }

}