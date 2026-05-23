<?php

namespace Mg\GrupoEconomico;

use App\Models\Usuario;
use Mg\Marca\Marca;
use Mg\MgModel;
use Mg\Pessoa\Pessoa;

class GrupoEconomico extends MgModel
{
    protected $table = 'tblgrupoeconomico';
    protected $primaryKey = 'codgrupoeconomico';

    protected $fillable = [
        'grupoeconomico',
        'inativo',
        'observacoes',
    ];

    protected $casts = [
        'codgrupoeconomico' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function MarcaS()
    {
        return $this->hasMany(Marca::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }

    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }
}
