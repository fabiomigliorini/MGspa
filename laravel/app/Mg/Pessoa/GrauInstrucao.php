<?php
/**
 * Created by php artisan gerador:model.
 * Date: 31/Jan/2026 11:45:09
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class GrauInstrucao extends MgModel
{
    protected $table = 'tblgrauinstrucao';
    protected $primaryKey = 'codgrauinstrucao';


    protected $fillable = [
        'grauinstrucao',
        'inativo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codgrauinstrucao' => 'integer',
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
    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codgrauinstrucao', 'codgrauinstrucao');
    }

}