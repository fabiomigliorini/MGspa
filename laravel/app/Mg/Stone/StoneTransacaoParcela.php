<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/Apr/2021 16:34:51
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

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'vencimento'
    ];

    protected $casts = [
        'codstonetransacao' => 'integer',
        'codstonetransacaoparcela' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'numero' => 'integer',
        'valor' => 'float',
        'valorliquido' => 'float'
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