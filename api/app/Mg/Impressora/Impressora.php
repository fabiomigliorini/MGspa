<?php

namespace Mg\Impressora;

use Mg\MgModel;
use Mg\Usuario\Usuario;

/**
 * Impressora (TASK-253). impressora = nome da fila no CUPS, que e' tambem o
 * nome do evento no canal "printing" do Ably assinado pelo MGprint.
 */
class Impressora extends MgModel
{
    const TIPO_TERMICA = 'T';
    const TIPO_A5 = 'A';
    const TIPO_ETIQUETA = 'E';

    const TIPOS = [
        self::TIPO_TERMICA => 'Térmica 80 mm',
        self::TIPO_A5 => 'A5',
        self::TIPO_ETIQUETA => 'Etiqueta',
    ];

    protected $table = 'tblimpressora';
    protected $primaryKey = 'codimpressora';

    protected $fillable = [
        'impressora',
        'descricao',
        'tipo',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codimpressora' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
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
}
