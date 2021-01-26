<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Jan/2021 08:52:09
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
use Mg\Cidade\Estado;
use Mg\Estoque\EstoqueLocal;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\NaturezaOperacao\Operacao;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class NotaFiscal extends MgModel
{
    const MODELO_NFE              = 55;
    const MODELO_NFCE             = 65;

    const FRETE_EMITENTE          = 0;
    const FRETE_DESTINATARIO      = 1;
    const FRETE_TERCEIROS         = 2;
    const FRETE_SEM               = 9;

    const TPEMIS_NORMAL           = 1; // Emissão normal (não em contingência);
    const TPEMIS_FS_IA            = 2; // Contingência FS-IA, com impressão do DANFE em formulário de segurança;
    const TPEMIS_SCAN             = 3; // Contingência SCAN (Sistema de Contingência do Ambiente Nacional) Desativação prevista para 30/06/2014;
    const TPEMIS_DPEC             = 4; // Contingência DPEC (Declaração Prévia da Emissão em Contingência);
    const TPEMIS_FS_DA            = 5; // Contingência FS-DA, com impressão do DANFE em formulário de segurança;
    const TPEMIS_SVC_AN           = 6; // Contingência SVC-AN (SEFAZ Virtual de Contingência do AN);
    const TPEMIS_SVC_RS           = 7; // Contingência SVC-RS (SEFAZ Virtual de Contingência do RS);
    const TPEMIS_OFFLINE          = 9; // Contingência off-line da NFC-e (as demais opções de contingência são válidas também para a NFC-e);

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