<?php

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Estoque\EstoqueLocal;
use Mg\NaturezaOperacao\Operacao;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;
use Mg\Cidade\Estado;

class NotaFiscal extends MGModel
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
      'codnaturezaoperacao',
      'emitida',
      'nfechave',
      'nfeimpressa',
      'serie',
      'numero',
      'emissao',
      'saida',
      'codfilial',
      'codpessoa',
      'observacoes',
      'volumes',
      'codoperacao',
      'nfereciboenvio',
      'nfedataenvio',
      'nfeautorizacao',
      'nfedataautorizacao',
      'valorfrete',
      'valorseguro',
      'valordesconto',
      'valoroutras',
      'nfecancelamento',
      'nfedatacancelamento',
      'nfeinutilizacao',
      'nfedatainutilizacao',
      'justificativa',
      'modelo',
      'valorprodutos',
      'valortotal',
      'icmsbase',
      'icmsvalor',
      'icmsstbase',
      'icmsstvalor',
      'ipibase',
      'ipivalor',
      'frete',
      'tpemis',
      'codestoquelocal',
    ];
    protected $dates = [
        'emissao',
        'saida',
        'nfedataenvio',
        'nfedataautorizacao',
        'nfedatacancelamento',
        'nfedatainutilizacao',
        'alteracao',
        'criacao',
    ];

    // Chaves Estrangeiras
    public function Operacao()
    {
        return $this->belongsTo(Operacao::class, 'codoperacao', 'codoperacao');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaTransportador()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoatransportador', 'codpessoa');
    }

    public function EstadoPlaca()
    {
        return $this->belongsTo(Estado::class, 'codestadoplaca', 'codestado');
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
    public function NotaFiscalProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalDuplicatasS()
    {
        return $this->hasMany(NotaFiscalDuplicatas::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalCartaCorrecaoS()
    {
        return $this->hasMany(NotaFiscalCartaCorrecao::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalReferenciadaS()
    {
        return $this->hasMany(NotaFiscalReferenciada::class, 'codnotafiscal', 'codnotafiscal');
    }
}
