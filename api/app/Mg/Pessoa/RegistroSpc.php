<?php

namespace Mg\Pessoa;

use App\Models\Usuario;
use Mg\MgModel;

class RegistroSpc extends MgModel
{
    protected $table = 'tblregistrospc';
    protected $primaryKey = 'codregistrospc';

    protected $fillable = ['baixa', 'codpessoa', 'inclusao', 'observacoes', 'valor'];

    protected $casts = [
        'alteracao' => 'datetime',
        'baixa' => 'datetime',
        'criacao' => 'datetime',
        'inclusao' => 'datetime',
        'codpessoa' => 'integer',
        'codregistrospc' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'valor' => 'float',
    ];

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
