<?php
/**
 * Created by php artisan gerador:model.
 * Date: 24/Sep/2024 18:01:33
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\Mdfe\MdfeNfe;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\NotaFiscal\NotaFiscalCartaCorrecao;
use Mg\NotaFiscal\NotaFiscalCreditoIcmsSimples;
use Mg\NotaFiscal\NotaFiscalDuplicatas;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Mg\NotaFiscal\NotaFiscalReferenciada;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\NotaFiscal\NotaFiscalPagamento;
use Mg\Cidade\Estado;
use Mg\Estoque\EstoqueLocal;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\NaturezaOperacao\Operacao;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class NotaFiscal extends MgModel
{
    protected $table = 'tblnotafiscal';
    protected $primaryKey = 'codnotafiscal';


    protected $fillable = [
        'codestadoplaca',
        'codestoquelocal',
        'codfilial',
        'codnaturezaoperacao',
        'codoperacao',
        'codpessoa',
        'codpessoatransportador',
        'cpf',
        'emissao',
        'emitida',
        'frete',
        'icmsbase',
        'icmsstbase',
        'icmsstvalor',
        'icmsvalor',
        'ipibase',
        'ipidevolucaovalor',
        'ipivalor',
        'justificativa',
        'modelo',
        'nfeautorizacao',
        'nfecancelamento',
        'nfechave',
        'nfedataautorizacao',
        'nfedatacancelamento',
        'nfedataenvio',
        'nfedatainutilizacao',
        'nfeimpressa',
        'nfeinutilizacao',
        'nfereciboenvio',
        'numero',
        'observacoes',
        'pesobruto',
        'pesoliquido',
        'placa',
        'saida',
        'serie',
        'tpemis',
        'valordesconto',
        'valorfrete',
        'valoroutras',
        'valorprodutos',
        'valorseguro',
        'valortotal',
        'volumes',
        'volumesespecie',
        'volumesmarca',
        'volumesnumero'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'emissao',
        'nfedataautorizacao',
        'nfedatacancelamento',
        'nfedataenvio',
        'nfedatainutilizacao',
        'saida'
    ];

    protected $casts = [
        'codestadoplaca' => 'integer',
        'codestoquelocal' => 'integer',
        'codfilial' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codnotafiscal' => 'integer',
        'codoperacao' => 'integer',
        'codpessoa' => 'integer',
        'codpessoatransportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cpf' => 'float',
        'emitida' => 'boolean',
        'frete' => 'integer',
        'icmsbase' => 'float',
        'icmsstbase' => 'float',
        'icmsstvalor' => 'float',
        'icmsvalor' => 'float',
        'ipibase' => 'float',
        'ipidevolucaovalor' => 'float',
        'ipivalor' => 'float',
        'modelo' => 'integer',
        'nfeimpressa' => 'boolean',
        'numero' => 'integer',
        'pesobruto' => 'float',
        'pesoliquido' => 'float',
        'serie' => 'integer',
        'tpemis' => 'integer',
        'valordesconto' => 'float',
        'valorfrete' => 'float',
        'valoroutras' => 'float',
        'valorprodutos' => 'float',
        'valorseguro' => 'float',
        'valortotal' => 'float',
        'volumes' => 'integer'
    ];


    // Chaves Estrangeiras
    public function EstadoPlaca()
    {
        return $this->belongsTo(Estado::class, 'codestadoplaca', 'codestado');
    }

    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function Operacao()
    {
        return $this->belongsTo(Operacao::class, 'codoperacao', 'codoperacao');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaTransportador()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoatransportador', 'codpessoa');
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
    public function MdfeNfeS()
    {
        return $this->hasMany(MdfeNfe::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalCartaCorrecaoS()
    {
        return $this->hasMany(NotaFiscalCartaCorrecao::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalCreditoIcmsSimplesS()
    {
        return $this->hasMany(NotaFiscalCreditoIcmsSimples::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalDuplicatasS()
    {
        return $this->hasMany(NotaFiscalDuplicatas::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalPagamentoS()
    {
        return $this->hasMany(NotaFiscalPagamento::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalReferenciadaS()
    {
        return $this->hasMany(NotaFiscalReferenciada::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'codnotafiscal', 'codnotafiscal');
    }

}