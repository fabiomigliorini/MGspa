<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:41:55
 */

namespace Mg\Tributacao;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscalItemTributo;
use Mg\Tributacao\TributacaoRegra;
use Mg\Usuario\Usuario;

class Tributo extends MgModel
{
    protected $table = 'tbltributo';
    protected $primaryKey = 'codtributo';


    protected $fillable = [
        'codigo',
        'descricao',
        'ente'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codtributo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
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
    public function NotaFiscalItemTributoS()
    {
        return $this->hasMany(NotaFiscalItemTributo::class, 'codtributo', 'codtributo');
    }

    public function TributacaoRegraS()
    {
        return $this->hasMany(TributacaoRegra::class, 'codtributo', 'codtributo');
    }

}
