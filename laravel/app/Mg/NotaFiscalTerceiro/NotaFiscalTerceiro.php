<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Jul/2020 16:23:43
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\Dfe\DistribuicaoDfe;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroDuplicata;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroGrupo;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Pessoa\Pessoa;

class NotaFiscalTerceiro extends MgModel
{
    protected $table = 'tblnotafiscalterceiro';
    protected $primaryKey = 'codnotafiscalterceiro';


    protected $fillable = [
        'cnpj',
        'codfilial',
        'codnaturezaoperacao',
        'codnegocio',
        'codnotafiscal',
        'codoperacao',
        'codpessoa',
        'cpf',
        'digito',
        'download',
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
        'ipivalor',
        'justificativa',
        'modelo',
        'natop',
        'nfechave',
        'numero',
        'protocolo',
        'recebimento',
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
        'recebimento'
    ];

    protected $casts = [
        'cnpj' => 'float',
        'codfilial' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codnegocio' => 'integer',
        'codnotafiscal' => 'integer',
        'codnotafiscalterceiro' => 'integer',
        'codoperacao' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cpf' => 'float',
        'download' => 'boolean',
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
        'protocolo' => 'integer',
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

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }


    // Tabelas Filhas
    public function DistribuicaoDfeS()
    {
        return $this->hasMany(DistribuicaoDfe::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

    public function NotaFiscalTerceiroDuplicataS()
    {
        return $this->hasMany(NotaFiscalTerceiroDuplicata::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

    public function NotaFiscalTerceiroGrupoS()
    {
        return $this->hasMany(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

}