<?php

namespace Mg\Caixa;

use Mg\MgModel;
use Mg\Pagamento\Pagamento;
use Mg\Portador\PortadorPeriodo;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

/**
 * O item do caixa numa sessao da gaveta (M13 doc-3). Entrada - saida e' o
 * pagamento na gaveta (codpagamento, sempre o mesmo registro); no
 * fechamento o liquido vira o titulo de repasse (codtitulo).
 */
class CaixaItemLancamento extends MgModel
{
    protected $table = 'tblcaixaitemlancamento';
    protected $primaryKey = 'codcaixaitemlancamento';

    protected $fillable = [
        'codportadorperiodo',
        'codcaixaitem',
        'valorabertura',
        'valorfechamento',
        'valorvendido',
        'valorentrada',
        'valorsaida',
        'observacoes',
        'codpagamento',
        'codtitulo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcaixaitem' => 'integer',
        'codcaixaitemlancamento' => 'integer',
        'codpagamento' => 'integer',
        'codportadorperiodo' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'valorabertura' => 'float',
        'valorentrada' => 'float',
        'valorfechamento' => 'float',
        'valorsaida' => 'float',
        'valorvendido' => 'float',
    ];

    // o que fica para o parceiro: na contagem o estoque que saiu da gaveta
    // (abertura + entrada - saida - fechamento); na maquineta o dinheiro
    // que entrou (entrada - saida). Positivo = devemos ao parceiro.
    public function liquido(): float
    {
        $mov = (float) $this->valorentrada - (float) $this->valorsaida;
        if ($this->CaixaItem->ehContagem()) {
            return round((float) $this->valorabertura + $mov - (float) $this->valorfechamento, 2);
        }
        return round($mov, 2);
    }

    // Chaves Estrangeiras
    public function CaixaItem()
    {
        return $this->belongsTo(CaixaItem::class, 'codcaixaitem', 'codcaixaitem');
    }

    public function Pagamento()
    {
        return $this->belongsTo(Pagamento::class, 'codpagamento', 'codpagamento');
    }

    public function PortadorPeriodo()
    {
        return $this->belongsTo(PortadorPeriodo::class, 'codportadorperiodo', 'codportadorperiodo');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
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
