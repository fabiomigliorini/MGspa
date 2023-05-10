<?php
/**
 * Created by php artisan gerador:model.
 * Date: 10/May/2023 14:51:37
 */

namespace Mg\Certidao;

use Mg\MgModel;
use Mg\Pessoa\PessoaCertidao;
use Mg\Usuario\Usuario;

class CertidaoTipo extends MgModel
{
    protected $table = 'tblcertidaotipo';
    protected $primaryKey = 'codcertidaotipo';


    protected $fillable = [
        'certidaotipo',
        'inativo',
        'sigla'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codcertidaotipo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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
    public function PessoaCertidaoS()
    {
        return $this->hasMany(PessoaCertidao::class, 'codcertidaotipo', 'codcertidaotipo');
    }

}