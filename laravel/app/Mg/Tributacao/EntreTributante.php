<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Dec/2025 18:55:02
 */

namespace Mg\Tributacao;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscalItemTributo;
use Mg\Tributacao\TributacaoRegra;
use Mg\Usuario\Usuario;

class EntreTributante extends MgModel
{
    protected $table = 'tblentetributante';
    protected $primaryKey = 'codentetributante';


    protected $fillable = [
        'codigo',
        'descricao',
        'tipo'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codentetributante' => 'integer',
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
    public function NotaFiscalItemTributoS()
    {
        return $this->hasMany(NotaFiscalItemTributo::class, 'codentetributante', 'codentetributante');
    }

    public function TributacaoRegraS()
    {
        return $this->hasMany(TributacaoRegra::class, 'codentetributante', 'codentetributante');
    }

}