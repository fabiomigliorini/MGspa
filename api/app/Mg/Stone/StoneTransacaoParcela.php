<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:32:22
 */

namespace Mg\Stone;

use Mg\MgModel;
use Mg\Stone\StoneTransacao;
use Mg\Usuario\Usuario;

class StoneTransacaoParcela extends MgModel
{
    protected $table = 'tblstonetransacaoparcela';
    protected $primaryKey = 'codstonetransacaoparcela';


    protected $fillable = [
        'codstonetransacao',
        'inativo',
        'numero',
        'valor',
        'valorliquido',
        'vencimento'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codstonetransacao' => 'integer',
        'codstonetransacaoparcela' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'numero' => 'integer',
        'valor' => 'float',
        'valorliquido' => 'float',
        'vencimento' => 'date'
    ];


    // Chaves Estrangeiras
    public function StoneTransacao()
    {
        return $this->belongsTo(StoneTransacao::class, 'codstonetransacao', 'codstonetransacao');
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
