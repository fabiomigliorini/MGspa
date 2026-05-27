<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:24:45
 */

namespace Mg\Cidade;

use Mg\MgModel;
use Mg\Mdfe\Mdfe;
use Mg\Mdfe\MdfeNfe;
use Mg\Pessoa\Pessoa;
use Mg\Pessoa\PessoaEndereco;
use Mg\Tributacao\TributacaoRegra;
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codcidade' => 'integer',
        'codestado' => 'integer',
        'codigooficial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
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

    public function PessoaNascimentoS()
    {
        return $this->hasMany(Pessoa::class, 'codcidadenascimento', 'codcidade');
    }

    public function PessoaEnderecoS()
    {
        return $this->hasMany(PessoaEndereco::class, 'codcidade', 'codcidade');
    }

    public function TributacaoRegraDestinoS()
    {
        return $this->hasMany(TributacaoRegra::class, 'codcidadedestino', 'codcidade');
    }

}
