<?php

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\Contrato;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Pessoa\Pessoa;

/**
 * Plano de emissao de NF de um contrato. Operacao triangular (venda a ordem)
 * de graos gera MAIS DE UMA nota pra mesma carga, numa sequencia (ordem) em
 * que uma nota referencia a chave de outra (refNFe). Cada linha aqui descreve
 * UMA nota a emitir: natureza de operacao, pessoa da NF (destinatario/emitente
 * conforme a natureza), observacao e qual nota-pai desta sequencia ela
 * referencia (codcontratonotapai -> NULL = raiz da cadeia).
 *
 * A emissao real percorre este plano por carga (ver NotaFiscalContratoService).
 */
class ContratoNota extends MgModel
{
    protected $table = 'tblcontratonota';
    protected $primaryKey = 'codcontratonota';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'codcontrato',
        'ordem',
        'codnaturezaoperacao',
        'codpessoanf',
        'codcontratonotapai',
        'observacaonf',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcontratonota' => 'integer',
        'codcontrato' => 'integer',
        'ordem' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codpessoanf' => 'integer',
        'codcontratonotapai' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    // Chaves Estrangeiras
    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function PessoaNf()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoanf', 'codpessoa');
    }

    // Nota desta cadeia cuja chave esta nota referencia (refNFe).
    public function Pai()
    {
        return $this->belongsTo(ContratoNota::class, 'codcontratonotapai', 'codcontratonota');
    }
}
