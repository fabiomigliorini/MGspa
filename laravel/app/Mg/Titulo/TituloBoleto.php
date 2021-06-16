<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jun/2021 15:31:12
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;
use Mg\Portador\Portador;

class TituloBoleto extends MgModel
{
    protected $table = 'tbltituloboleto';
    protected $primaryKey = 'codtituloboleto';


    protected $fillable = [
        'barras',
        'canalpagamento',
        'codportador',
        'codtitulo',
        'databaixaautomatica',
        'datacredito',
        'datarecebimento',
        'dataregistro',
        'estadotitulocobranca',
        'inativo',
        'linhadigitavel',
        'nossonumero',
        'qrcodeemv',
        'qrcodetxid',
        'qrcodeurl',
        'tipobaixatitulo',
        'valorabatimento',
        'valoratual',
        'valordesconto',
        'valorjuromora',
        'valorliquido',
        'valormulta',
        'valororiginal',
        'valoroutro',
        'valorpagamentoparcial',
        'valorpago',
        'valorreajuste'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'databaixaautomatica',
        'datacredito',
        'datarecebimento',
        'dataregistro',
        'inativo'
    ];

    protected $casts = [
        'canalpagamento' => 'integer',
        'codportador' => 'integer',
        'codtitulo' => 'integer',
        'codtituloboleto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'estadotitulocobranca' => 'integer',
        'tipobaixatitulo' => 'integer',
        'valorabatimento' => 'float',
        'valoratual' => 'float',
        'valordesconto' => 'float',
        'valorjuromora' => 'float',
        'valorliquido' => 'float',
        'valormulta' => 'float',
        'valororiginal' => 'float',
        'valoroutro' => 'float',
        'valorpagamentoparcial' => 'float',
        'valorpago' => 'float',
        'valorreajuste' => 'float'
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

}