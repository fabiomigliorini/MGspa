<?php
/**
 * Created by php artisan gerador:model.
 * Date: 10/May/2023 14:49:51
 */

namespace Mg\Certidao;

use Mg\MgModel;
use Mg\Pessoa\PessoaCertidao;
use Mg\Usuario\Usuario;

class CertidaoEmissor extends MgModel
{
    const SEFAZ_MT = 1;

    protected $table = 'tblcertidaoemissor';
    protected $primaryKey = 'codcertidaoemissor';


    protected $fillable = [
        'certidaoemissor',
        'inativo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codcertidaoemissor' => 'integer',
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
        return $this->hasMany(PessoaCertidao::class, 'codcertidaoemissor', 'codcertidaoemissor');
    }

}