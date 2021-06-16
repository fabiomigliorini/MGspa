<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jun/2021 08:45:58
 */

namespace Mg\Cidade;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Mdfe\Mdfe;
use Mg\Mdfe\MdfeNfe;
use Mg\Cidade\Estado;
use Mg\Usuario\Usuario;

class Cidade extends MgModel
{
    protected $table = 'tblcidade';
    protected $primaryKey = 'codcidade';


    protected $fillable = [
        'cidade',
        'codestado',
        'codigooficial',
        'sigla'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcidade' => 'integer',
        'codestado' => 'integer',
        'codigooficial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function MdfeCarregamentoS()
    {
        return $this->hasMany(Mdfe::class, 'codcidadecarregamento', 'codcidade');
    }

    public function MdfeNfeDescargaS()
    {
        return $this->hasMany(MdfeNfe::class, 'codcidadedescarga', 'codcidade');
    }

    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codcidade', 'codcidade');
    }

    public function PessoaCobrancaS()
    {
        return $this->hasMany(Pessoa::class, 'codcidadecobranca', 'codcidade');
    }

}