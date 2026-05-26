<?php

namespace Mg\Pessoa;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class Dependente extends MgModel
{
    protected $table = 'tbldependente';
    protected $primaryKey = 'coddependente';

    protected $fillable = [
        'codpessoa',
        'codpessoaresponsavel',
        'datafim',
        'datainicio',
        'depirrf',
        'depplano',
        'depsfam',
        'guardajudicial',
        'inativo',
        'incsocfam',
        'motivofim',
        'observacao',
        'pensaoagencia',
        'pensaoalimenticia',
        'pensaobanco',
        'pensaobeneficiario',
        'pensaoconta',
        'pensaocpfbeneficiario',
        'pensaopercentual',
        'pensaovalor',
        'tipdep',
    ];

    protected $casts = [
        'coddependente' => 'integer',
        'codpessoa' => 'integer',
        'codpessoaresponsavel' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'depirrf' => 'boolean',
        'depplano' => 'boolean',
        'depsfam' => 'boolean',
        'guardajudicial' => 'boolean',
        'incsocfam' => 'boolean',
        'pensaoalimenticia' => 'boolean',
        'pensaopercentual' => 'float',
        'pensaovalor' => 'float',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'datafim' => 'datetime',
        'datainicio' => 'datetime',
        'inativo' => 'datetime',
    ];

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaResponsavel()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoaresponsavel', 'codpessoa');
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
