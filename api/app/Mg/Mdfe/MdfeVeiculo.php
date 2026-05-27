<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:31:06
 */

namespace Mg\Mdfe;

use Mg\MgModel;
use Mg\Mdfe\Mdfe;
use Mg\Usuario\Usuario;
use Mg\Veiculo\Veiculo;
use Mg\Pessoa\Pessoa;

class MdfeVeiculo extends MgModel
{
    protected $table = 'tblmdfeveiculo';
    protected $primaryKey = 'codmdfeveiculo';


    protected $fillable = [
        'codmdfe',
        'codpessoacondutor',
        'codveiculo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codmdfe' => 'integer',
        'codmdfeveiculo' => 'integer',
        'codpessoacondutor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculo' => 'integer',
        'criacao' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Mdfe()
    {
        return $this->belongsTo(Mdfe::class, 'codmdfe', 'codmdfe');
    }

    public function PessoaCondutor()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoacondutor', 'codpessoa');
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
