<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Feb/2026 11:45:46
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

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
        'tipdep'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'datafim',
        'datainicio',
        'inativo'
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
        'pensaovalor' => 'float'
    ];


    // Chaves Estrangeiras
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