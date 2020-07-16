<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jul/2020 15:30:02
 */

namespace Mg\NfeTerceiro;

use Mg\MgModel;
use Mg\NfeTerceiro\NfeTerceiroDuplicata;
use Mg\NfeTerceiro\NfeTerceiroItem;
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
        'emissao',
        'emitente',
        'entrada',
        'icmsbase',
        'icmsstbase',
        'icmsstvalor',
        'icmsvalor',
        'ie',
        'ignorada',
        'indmanifestacao',
        'indsituacao',
        'ipivalor',
        'justificativa',
        'nfechave',
        'nfedataautorizacao',
        'nsu',
        'numero',
        'serie',
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
        'nfedataautorizacao'
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
        'icmsbase' => 'float',
        'icmsstbase' => 'float',
        'icmsstvalor' => 'float',
        'icmsvalor' => 'float',
        'ignorada' => 'boolean',
        'indmanifestacao' => 'integer',
        'indsituacao' => 'integer',
        'ipivalor' => 'float',
        'numero' => 'integer',
        'serie' => 'integer',
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


    // Tabelas Filhas
    public function NfeTerceiroDuplicataS()
    {
        return $this->hasMany(NfeTerceiroDuplicata::class, 'codnfeterceiro', 'codnfeterceiro');
    }

    public function NfeTerceiroItemS()
    {
        return $this->hasMany(NfeTerceiroItem::class, 'codnfeterceiro', 'codnfeterceiro');
    }

}