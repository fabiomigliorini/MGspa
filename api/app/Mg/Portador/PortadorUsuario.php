<?php

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Usuario\Usuario;

/**
 * Quem pode o que num portador (doc-4, redefinicao do dinheiro): D
 * depositante (so' manda dinheiro para ele, nao ve nada), O operador (ve e
 * movimenta), G gestor (tambem confirma transferencia, reabre, mexe nas
 * datas e cuida da lista). Administrador e' gestor em todos sem estar aqui.
 * Mantido a mao.
 */
class PortadorUsuario extends MgModel
{
    protected $table = 'tblportadorusuario';
    protected $primaryKey = 'codportadorusuario';

    const PAPEL_DEPOSITANTE = 'D';
    const PAPEL_OPERADOR = 'O';
    const PAPEL_GESTOR = 'G';

    const PAPEIS = [
        self::PAPEL_DEPOSITANTE => 'Depositante',
        self::PAPEL_OPERADOR => 'Operador',
        self::PAPEL_GESTOR => 'Gestor',
    ];

    protected $fillable = [
        'codportador',
        'codusuario',
        'papel',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codportador' => 'integer',
        'codportadorusuario' => 'integer',
        'codusuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
    ];

    // Chaves Estrangeiras
    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuario', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }
}
