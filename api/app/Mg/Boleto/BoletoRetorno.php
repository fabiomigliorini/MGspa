<?php

/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:20:41
 */

namespace Mg\Boleto;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;
use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;
use Mg\Boleto\BoletoMotivoOcorrencia;

class BoletoRetorno extends MgModel
{
    protected $table = 'tblboletoretorno';
    protected $primaryKey = 'codboletoretorno';


    protected $fillable = [
        'abatimento',
        'agenciacobradora',
        'arquivo',
        'codbancocobrador',
        'codboletomotivoocorrencia',
        'codportador',
        'codtitulo',
        'dataretorno',
        'desconto',
        'despesas',
        'jurosatraso',
        'jurosmora',
        'linha',
        'nossonumero',
        'numero',
        'outrasdespesas',
        'pagamento',
        'protesto',
        'valor'
    ];

    protected $casts = [
        'abatimento' => 'float',
        'alteracao' => 'datetime',
        'codbancocobrador' => 'integer',
        'codboletomotivoocorrencia' => 'integer',
        'codboletoretorno' => 'integer',
        'codportador' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'dataretorno' => 'date',
        'desconto' => 'float',
        'despesas' => 'float',
        'jurosatraso' => 'float',
        'jurosmora' => 'float',
        'linha' => 'integer',
        'outrasdespesas' => 'float',
        'pagamento' => 'float',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function BoletoMotivoOcorrencia()
    {
        return $this->belongsTo(BoletoMotivoOcorrencia::class, 'codboletomotivoocorrencia', 'codboletomotivoocorrencia');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codboletoretorno', 'codboletoretorno');
    }
}
