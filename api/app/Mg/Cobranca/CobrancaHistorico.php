<?php

namespace Mg\Cobranca;

use App\Models\Usuario;
use Mg\MgModel;
use Mg\Pessoa\Pessoa;

class CobrancaHistorico extends MgModel
{
    protected $table = 'tblcobrancahistorico';
    protected $primaryKey = 'codcobrancahistorico';

    protected $fillable = ['codpessoa', 'emailautomatico', 'historico'];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'codcobrancahistorico' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'emailautomatico' => 'boolean',
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
