<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jul/2021 11:31:28
 */

namespace Mg\NfeTerceiro;

use Mg\MgModel;
use Mg\NfeTerceiro\NfeTerceiroDuplicata;
use Mg\NfeTerceiro\NfeTerceiroItem;
use Mg\Dfe\DistribuicaoDfe;
use Mg\NfeTerceiro\NfeTerceiroPagamento;
use Mg\Titulo\TituloNfeTerceiro;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NaturezaOperacao\Operacao;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class NfeTerceiro extends MgModel
{
    protected $table = 'tblnfeterceiro';
    protected $primaryKey = 'codnfeterceiro';


    protected $fillable = [
        'cnpj',
        'codfilial',
        'codnaturezaoperacao',
        'codnegocio',
        'codnotafiscal',
        'codoperacao',
        'codpessoa',
        'codusuariorevisao',
        'emissao',
        'emitente',
        'entrada',
        'finalidade',
        'icmsbase',
        'icmsstbase',
        'icmsstvalor',
        'icmsvalor',
        'ie',
        'ignorada',
        'indmanifestacao',
        'indsituacao',
        'informacoes',
        'ipivalor',
        'justificativa',
        'modelo',
        'natureza',
        'nfechave',
        'nfedataautorizacao',
        'nsu',
        'numero',
        'observacoes',
        'revisao',
        'serie',
        'tipo',
        'valordesconto',
        'valorfrete',
        'valoroutras',
        'valorprodutos',
        'valorseguro',
        'valortotal'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'emissao',
        'entrada',
        'nfedataautorizacao',
        'revisao'
    ];

    protected $casts = [
        'cnpj' => 'float',
        'codfilial' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codnegocio' => 'integer',
        'codnfeterceiro' => 'integer',
        'codnotafiscal' => 'integer',
        'codoperacao' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuariorevisao' => 'integer',
        'finalidade' => 'integer',
        'icmsbase' => 'float',
        'icmsstbase' => 'float',
        'icmsstvalor' => 'float',
        'icmsvalor' => 'float',
        'ignorada' => 'boolean',
        'indmanifestacao' => 'integer',
        'indsituacao' => 'integer',
        'ipivalor' => 'float',
        'modelo' => 'integer',
        'numero' => 'integer',
        'serie' => 'integer',
        'tipo' => 'integer',
        'valordesconto' => 'float',
        'valorfrete' => 'float',
        'valoroutras' => 'float',
        'valorprodutos' => 'float',
        'valorseguro' => 'float',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function Operacao()
    {
        return $this->belongsTo(Operacao::class, 'codoperacao', 'codoperacao');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioRevisao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariorevisao', 'codusuario');
    }


    // Tabelas Filhas
    public function DistribuicaoDfeS()
    {
        return $this->hasMany(DistribuicaoDfe::class, 'codnfeterceiro', 'codnfeterceiro');
    }

    public function NfeTerceiroDuplicataS()
    {
        return $this->hasMany(NfeTerceiroDuplicata::class, 'codnfeterceiro', 'codnfeterceiro');
    }

    public function NfeTerceiroItemS()
    {
        return $this->hasMany(NfeTerceiroItem::class, 'codnfeterceiro', 'codnfeterceiro');
    }

    public function NfeTerceiroPagamentoS()
    {
        return $this->hasMany(NfeTerceiroPagamento::class, 'codnfeterceiro', 'codnfeterceiro');
    }

    public function TituloNfeTerceiroS()
    {
        return $this->hasMany(TituloNfeTerceiro::class, 'codnfeterceiro', 'codnfeterceiro');
    }

}