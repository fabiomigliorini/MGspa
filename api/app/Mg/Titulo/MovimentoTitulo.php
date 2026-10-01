<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:21:08
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Portador\PortadorMovimento;
use Mg\Boleto\BoletoRetorno;
use Mg\Cobranca\Cobranca;
use Mg\Portador\Portador;
use Mg\Titulo\TipoMovimentoTitulo;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloAgrupamento;
use Mg\Usuario\Usuario;
use Mg\Titulo\TituloBoleto;
use Mg\Pagamento\Pagamento;

class MovimentoTitulo extends MgModel
{
    protected $table = 'tblmovimentotitulo';
    protected $primaryKey = 'codmovimentotitulo';


    protected $fillable = [
        'codboletoretorno',
        'codcobranca',
        'codmovimentotituloestorno',
        'codpagamento',
        'codperiodocolaboradoracerto',
        'codportador',
        'codtipomovimentotitulo',
        'codtitulo',
        'codtituloagrupamento',
        'codtituloboleto',
        'codtitulorelacionado',
        'desconto',
        'historico',
        'juros',
        'multa',
        'principal',
        'total',
        'transacao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codboletoretorno' => 'integer',
        'codcobranca' => 'integer',
        'codmovimentotitulo' => 'integer',
        'codmovimentotituloestorno' => 'integer',
        'codpagamento' => 'integer',
        'codperiodocolaboradoracerto' => 'integer',
        'codportador' => 'integer',
        'codtipomovimentotitulo' => 'integer',
        'codtitulo' => 'integer',
        'codtituloagrupamento' => 'integer',
        'codtituloboleto' => 'integer',
        'codtitulorelacionado' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'desconto' => 'float',
        'juros' => 'float',
        'multa' => 'float',
        'principal' => 'float',
        'total' => 'float',
        'transacao' => 'date'
    ];


    /**
     * Movimento que desfaz outro. O estorno novo aponta para o original
     * (codmovimentotituloestorno); o antigo só tem o tipo para dizer.
     */
    public function ehEstorno(): bool
    {
        return !empty($this->codmovimentotituloestorno)
            || in_array((int) $this->codtipomovimentotitulo, MovimentoTituloService::TIPOS_ESTORNO);
    }


    // Chaves Estrangeiras
    public function BoletoRetorno()
    {
        return $this->belongsTo(BoletoRetorno::class, 'codboletoretorno', 'codboletoretorno');
    }

    public function Cobranca()
    {
        return $this->belongsTo(Cobranca::class, 'codcobranca', 'codcobranca');
    }

    public function MovimentoTituloEstorno()
    {
        return $this->belongsTo(MovimentoTitulo::class, 'codmovimentotituloestorno', 'codmovimentotitulo');
    }

    public function Pagamento()
    {
        return $this->belongsTo(Pagamento::class, 'codpagamento', 'codpagamento');
    }

    public function PeriodoColaboradorAcerto()
    {
        return $this->belongsTo(\Mg\Rh\PeriodoColaboradorAcerto::class, 'codperiodocolaboradoracerto', 'codperiodocolaboradoracerto');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function TipoMovimentoTitulo()
    {
        return $this->belongsTo(TipoMovimentoTitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function TituloAgrupamento()
    {
        return $this->belongsTo(TituloAgrupamento::class, 'codtituloagrupamento', 'codtituloagrupamento');
    }

    public function TituloBoleto()
    {
        return $this->belongsTo(TituloBoleto::class, 'codtituloboleto', 'codtituloboleto');
    }

    public function TituloRelacionado()
    {
        return $this->belongsTo(Titulo::class, 'codtitulorelacionado', 'codtitulo');
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
    public function MovimentoTituloEstornoS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codmovimentotituloestorno', 'codmovimentotitulo');
    }

    public function PortadorMovimentoS()
    {
        return $this->hasMany(PortadorMovimento::class, 'codmovimentotitulo', 'codmovimentotitulo');
    }

}
