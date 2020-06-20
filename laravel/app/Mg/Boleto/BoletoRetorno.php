<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:55:48
 */

namespace Mg\Boleto;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;
use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

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

    protected $dates = [
        'alteracao',
        'criacao',
        'dataretorno'
    ];

    protected $casts = [
        'abatimento' => 'float',
        'codbancocobrador' => 'integer',
        'codboletomotivoocorrencia' => 'integer',
        'codboletoretorno' => 'integer',
        'codportador' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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