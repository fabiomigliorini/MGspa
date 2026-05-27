<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:42:16
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codgrauinstrucao' => 'integer',
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
    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codgrauinstrucao', 'codgrauinstrucao');
    }

}
