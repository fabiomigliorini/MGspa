<?php
/**
 * Created by php artisan gerador:model.
 * Date: 10/May/2023 14:52:20
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Certidao\CertidaoEmissor;
use Mg\Certidao\CertidaoTipo;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class PessoaCertidao extends MgModel
{
    protected $table = 'tblpessoacertidao';
    protected $primaryKey = 'codpessoacertidao';


    protected $fillable = [
        'autenticacao',
        'codcertidaoemissor',
        'codcertidaotipo',
        'codpessoa',
        'inativo',
        'numero',
        'validade'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'validade'
    ];

    protected $casts = [
        'codcertidaoemissor' => 'integer',
        'codcertidaotipo' => 'integer',
        'codpessoa' => 'integer',
        'codpessoacertidao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function CertidaoEmissor()
    {
        return $this->belongsTo(CertidaoEmissor::class, 'codcertidaoemissor', 'codcertidaoemissor');
    }

    public function CertidaoTipo()
    {
        return $this->belongsTo(CertidaoTipo::class, 'codcertidaotipo', 'codcertidaotipo');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
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