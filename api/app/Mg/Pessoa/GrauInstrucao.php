<?php

namespace Mg\Pessoa;

use App\Models\Usuario;
use Mg\MgModel;

class GrauInstrucao extends MgModel
{
    protected $table = 'tblgrauinstrucao';
    protected $primaryKey = 'codgrauinstrucao';

    protected $fillable = [
        'grauinstrucao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codgrauinstrucao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codgrauinstrucao', 'codgrauinstrucao');
    }
}
