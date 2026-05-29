<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:36:48
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codcertidaoemissor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
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
