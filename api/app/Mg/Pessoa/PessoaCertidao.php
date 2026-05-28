<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:28:59
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codcertidaoemissor' => 'integer',
        'codcertidaotipo' => 'integer',
        'codpessoa' => 'integer',
        'codpessoacertidao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'validade' => 'date'
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
