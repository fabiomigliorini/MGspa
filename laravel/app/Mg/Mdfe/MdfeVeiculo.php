<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Jan/2021 08:31:33
 */

namespace Mg\Mdfe;

use Mg\MgModel;
use Mg\Mdfe\Mdfe;
use Mg\Usuario\Usuario;
use Mg\Veiculo\Veiculo;

class MdfeVeiculo extends MgModel
{
    protected $table = 'tblmdfeveiculo';
    protected $primaryKey = 'codmdfeveiculo';


    protected $fillable = [
        'codmdfe',
        'codpessoacondutor',
        'codveiculo'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codmdfe' => 'integer',
        'codmdfeveiculo' => 'integer',
        'codpessoacondutor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculo' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Mdfe()
    {
        return $this->belongsTo(Mdfe::class, 'codmdfe', 'codmdfe');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function Veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'codveiculo', 'codveiculo');
    }

}