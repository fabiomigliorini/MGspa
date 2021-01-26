<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Jan/2021 08:55:43
 */

namespace Mg\Mdfe;

use Mg\MgModel;
use Mg\Cidade\Cidade;
use Mg\Mdfe\Mdfe;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Usuario\Usuario;

class MdfeNfe extends MgModel
{
    protected $table = 'tblmdfenfe';
    protected $primaryKey = 'codmdfenfe';


    protected $fillable = [
        'codcidadedescarga',
        'codmdfe',
        'codnotafiscal',
        'nfechave',
        'peso',
        'valor'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcidadedescarga' => 'integer',
        'codmdfe' => 'integer',
        'codmdfenfe' => 'integer',
        'codnotafiscal' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'peso' => 'float',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function CidadeDescarga()
    {
        return $this->belongsTo(Cidade::class, 'codcidadedescarga', 'codcidade');
    }

    public function Mdfe()
    {
        return $this->belongsTo(Mdfe::class, 'codmdfe', 'codmdfe');
    }

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