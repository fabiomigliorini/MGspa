<?php

namespace Mg\NotaFiscalServico;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use Mg\Filial\Filial;

/**
 * Monta os XMLs da NFS-e Nacional (TASK-279), sem assinatura: a DPS e o pedido de
 * registro de evento de cancelamento. A ordem dos elementos segue o XSD oficial 1.01
 * (api/resources/nfse/schemes) — trocar a ordem e' rejeicao de schema.
 */
class NfseXmlService
{
    const NAMESPACE_NFSE = 'http://www.sped.fazenda.gov.br/nfse';
    const VERSAO = '1.01';
    const VER_APLIC = 'MGspa';

    // tpInsc do id da DPS
    const INSCRICAO_CPF = '1';
    const INSCRICAO_CNPJ = '2';

    // opSimpNac
    const SIMPLES_NAO_OPTANTE = '1';
    const SIMPLES_MEI = '2';
    const SIMPLES_ME_EPP = '3';

    // regApTribSN: federais e ISS pelo Simples (decisao da FDF, ISS no DAS)
    const SIMPLES_APURACAO_TUDO_NO_SN = '1';

    // tblfilial.crt (codigo de regime tributario da NF-e)
    const CRT_SIMPLES = 1;
    const CRT_SIMPLES_EXCESSO = 2;

    // tpRetISSQN
    const ISS_NAO_RETIDO = '1';
    const ISS_RETIDO_TOMADOR = '2';

    // cMotivo do cancelamento
    const MOTIVOS_CANCELAMENTO = [
        1 => 'Erro na Emissão',
        2 => 'Serviço não Prestado',
        9 => 'Outros',
    ];

    // subst/cMotivo da substituicao (TASK-279 #5); vai na DPS com dois digitos
    const MOTIVOS_SUBSTITUICAO = [
        1 => 'Desenquadramento do Simples Nacional',
        2 => 'Enquadramento no Simples Nacional',
        3 => 'Inclusão retroativa de imunidade/isenção',
        4 => 'Exclusão retroativa de imunidade/isenção',
        5 => 'Rejeição pelo tomador ou intermediário',
        99 => 'Outros',
    ];

    const TIPO_EVENTO_CANCELAMENTO = '101101';
    // Gerado pelo Sistema Nacional ao autorizar a substituta: cancela a anterior
    const TIPO_EVENTO_SUBSTITUICAO = '105102';

    public static function simples(Filial $filial): bool
    {
        return in_array((int) $filial->crt, [static::CRT_SIMPLES, static::CRT_SIMPLES_EXCESSO]);
    }

    // CPF com 11 ou CNPJ com 14 (pode ter letras, TASK-283)
    public static function documentoFilial(Filial $filial): string
    {
        $pessoa = $filial->Pessoa;
        return (string) ($pessoa->fisica ? cpfLimpo($pessoa->cnpj) : cnpjLimpo($pessoa->cnpj));
    }

    /**
     * "DPS" + municipio (7) + tipo de inscricao (1) + inscricao (14) + serie (5) + numero (15)
     */
    public static function idDps(NotaFiscalServico $nfs): string
    {
        $filial = $nfs->Filial;
        $cMun = str_pad((string) $filial->Pessoa->Cidade->codigooficial, 7, '0', STR_PAD_LEFT);
        $tipo = $filial->Pessoa->fisica ? static::INSCRICAO_CPF : static::INSCRICAO_CNPJ;
        $inscricao = str_pad(static::documentoFilial($filial), 14, '0', STR_PAD_LEFT);
        return 'DPS'
            . $cMun
            . $tipo
            . $inscricao
            . str_pad((string) $nfs->serie, 5, '0', STR_PAD_LEFT)
            . str_pad((string) $nfs->numero, 15, '0', STR_PAD_LEFT);
    }

    /**
     * $rtc: leiaute da reforma tributaria (o da homologacao hoje): finNFSe depois do
     * tpEmit e xDescServ no fim do cServ. Sem ele, o "atual" do portal (producao).
     */
    public static function dps(NotaFiscalServico $nfs, int $ambiente, bool $rtc): string
    {
        $filial = $nfs->Filial;
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $dps = $doc->createElementNS(static::NAMESPACE_NFSE, 'DPS');
        $dps->setAttribute('versao', static::VERSAO);
        $doc->appendChild($dps);

        $inf = static::el($doc, $dps, 'infDPS');
        $inf->setAttribute('Id', static::idDps($nfs));

        static::el($doc, $inf, 'tpAmb', (string) $ambiente);
        static::el($doc, $inf, 'dhEmi', static::dataHora($nfs->emissao ?? Carbon::now()));
        static::el($doc, $inf, 'verAplic', static::VER_APLIC);
        static::el($doc, $inf, 'serie', (string) $nfs->serie);
        static::el($doc, $inf, 'nDPS', (string) $nfs->numero);
        static::el($doc, $inf, 'dCompet', $nfs->competencia->format('Y-m-d'));
        // 1 = o prestador emite
        static::el($doc, $inf, 'tpEmit', '1');
        if ($rtc) {
            // 0 = NFS-e regular
            static::el($doc, $inf, 'finNFSe', '0');
        }
        static::el($doc, $inf, 'cLocEmi', (string) $filial->Pessoa->Cidade->codigooficial);

        // Substituicao (TASK-279 #5): autorizada esta, o Sistema Nacional cancela a
        // anterior. A descricao do motivo e' opcional; quando vem, de 15 a 255 caracteres.
        if (!empty($nfs->codnotafiscalservicosubstituida)) {
            $subst = static::el($doc, $inf, 'subst');
            static::el($doc, $subst, 'chSubstda', $nfs->NotaFiscalServicoSubstituida->nfsechave);
            static::el($doc, $subst, 'cMotivo', str_pad((string) $nfs->substituicaomotivo, 2, '0', STR_PAD_LEFT));
            $xMotivo = static::texto($nfs->substituicaojustificativa, 255);
            if (mb_strlen($xMotivo) >= 15) {
                static::el($doc, $subst, 'xMotivo', $xMotivo);
            }
        }

        // Prestador: documento, IM e regime; nome e endereco o Sistema Nacional
        // tira do cadastro (CNC) quando quem emite e' o proprio prestador.
        $prest = static::el($doc, $inf, 'prest');
        static::el($doc, $prest, $filial->Pessoa->fisica ? 'CPF' : 'CNPJ', static::documentoFilial($filial));
        // Sinop exige a IM do prestador (E0116). Documento da empresa: mora na pessoa da filial
        $im = trim((string) $filial->Pessoa->inscricaomunicipal);
        if ($im !== '') {
            static::el($doc, $prest, 'IM', $im);
        }
        $regTrib = static::el($doc, $prest, 'regTrib');
        if (static::simples($filial)) {
            static::el($doc, $regTrib, 'opSimpNac', static::SIMPLES_ME_EPP);
            static::el($doc, $regTrib, 'regApTribSN', static::SIMPLES_APURACAO_TUDO_NO_SN);
        } else {
            static::el($doc, $regTrib, 'opSimpNac', static::SIMPLES_NAO_OPTANTE);
        }
        // 0 = nenhum regime especial
        static::el($doc, $regTrib, 'regEspTrib', '0');

        // Tomador (opcional)
        if (!empty($nfs->codpessoa)) {
            static::tomador($doc, $inf, $nfs);
        }

        // Servico
        $serv = static::el($doc, $inf, 'serv');
        $locPrest = static::el($doc, $serv, 'locPrest');
        static::el($doc, $locPrest, 'cLocPrestacao', (string) $nfs->CidadePrestacao->codigooficial);
        $cServ = static::el($doc, $serv, 'cServ');
        static::el($doc, $cServ, 'cTribNac', $nfs->TributacaoNacional->codigo);
        if ($rtc) {
            static::el($doc, $cServ, 'cNBS', $nfs->Nbs->codigo);
            static::el($doc, $cServ, 'xDescServ', static::texto($nfs->descricao, 2000));
        } else {
            static::el($doc, $cServ, 'xDescServ', static::texto($nfs->descricao, 2000));
            static::el($doc, $cServ, 'cNBS', $nfs->Nbs->codigo);
        }
        if (!empty(trim((string) $nfs->observacoes))) {
            $infoCompl = static::el($doc, $serv, 'infoCompl');
            static::el($doc, $infoCompl, 'xInfComp', static::texto($nfs->observacoes, 2000));
        }

        // Valores
        $valores = static::el($doc, $inf, 'valores');
        $vServPrest = static::el($doc, $valores, 'vServPrest');
        static::el($doc, $vServPrest, 'vServ', static::valor($nfs->valorservico));
        if ($nfs->valordescontoincondicionado > 0 || $nfs->valordescontocondicionado > 0) {
            $desc = static::el($doc, $valores, 'vDescCondIncond');
            if ($nfs->valordescontoincondicionado > 0) {
                static::el($doc, $desc, 'vDescIncond', static::valor($nfs->valordescontoincondicionado));
            }
            if ($nfs->valordescontocondicionado > 0) {
                static::el($doc, $desc, 'vDescCond', static::valor($nfs->valordescontocondicionado));
            }
        }
        $trib = static::el($doc, $valores, 'trib');
        $tribMun = static::el($doc, $trib, 'tribMun');
        // 1 = operacao tributavel
        static::el($doc, $tribMun, 'tribISSQN', '1');
        static::el($doc, $tribMun, 'tpRetISSQN', $nfs->issretido ? static::ISS_RETIDO_TOMADOR : static::ISS_NAO_RETIDO);
        // Simples (ME/EPP, ISS no DAS): a aliquota so vai com ISS retido pelo tomador;
        // sem retencao o Sistema Nacional recusa (E0625).
        if ($nfs->issretido && $nfs->issaliquota !== null) {
            static::el($doc, $tribMun, 'pAliq', static::valor($nfs->issaliquota));
        }
        $totTrib = static::el($doc, $trib, 'totTrib');
        if (static::simples($filial)) {
            // ME/EPP: % aproximado dos tributos do Simples; "nao informar" e' recusado (E0712)
            static::el($doc, $totTrib, 'pTotTribSN', static::valor($nfs->tributospercentual));
        } else {
            // 0 = nao informar estimativa de tributos (Decreto 8.264/2014)
            static::el($doc, $totTrib, 'indTotTrib', '0');
        }

        return $doc->saveXML($doc->documentElement);
    }

    protected static function tomador(DOMDocument $doc, DOMElement $inf, NotaFiscalServico $nfs): void
    {
        $pessoa = $nfs->Pessoa;
        $toma = static::el($doc, $inf, 'toma');
        if ($pessoa->fisica) {
            static::el($doc, $toma, 'CPF', cpfLimpo($pessoa->cnpj));
        } else {
            static::el($doc, $toma, 'CNPJ', cnpjLimpo($pessoa->cnpj));
        }
        static::el($doc, $toma, 'xNome', static::texto($pessoa->pessoa, 300));

        // Endereco so quando completo: o grupo inteiro e' opcional, mas se vier
        // precisa de municipio, CEP, logradouro, numero e bairro.
        $cep = preg_replace('/\D/', '', (string) $pessoa->cep);
        if (
            !empty($pessoa->Cidade?->codigooficial)
            && strlen($cep) == 8
            && !empty(trim((string) $pessoa->endereco))
            && !empty(trim((string) $pessoa->bairro))
        ) {
            $end = static::el($doc, $toma, 'end');
            $endNac = static::el($doc, $end, 'endNac');
            static::el($doc, $endNac, 'cMun', (string) $pessoa->Cidade->codigooficial);
            static::el($doc, $endNac, 'CEP', $cep);
            static::el($doc, $end, 'xLgr', static::texto($pessoa->endereco, 255));
            static::el($doc, $end, 'nro', static::texto(empty(trim((string) $pessoa->numero)) ? 'S/N' : $pessoa->numero, 60));
            if (!empty(trim((string) $pessoa->complemento))) {
                static::el($doc, $end, 'xCpl', static::texto($pessoa->complemento, 156));
            }
            static::el($doc, $end, 'xBairro', static::texto($pessoa->bairro, 60));
        }

        $fone = preg_replace('/\D/', '', (string) $pessoa->telefone1);
        if (strlen($fone) >= 6 && strlen($fone) <= 20) {
            static::el($doc, $toma, 'fone', $fone);
        }
        $email = $pessoa->PessoaEmailS->where('nfe', true)->pluck('email')->first();
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 80) {
            static::el($doc, $toma, 'email', $email);
        }
    }

    /**
     * Pedido de registro do evento de cancelamento (e101101).
     * Id = "PRE" + chave (50) + tipo do evento (6).
     */
    public static function cancelamento(NotaFiscalServico $nfs, int $ambiente, int $motivo, string $justificativa): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $ped = $doc->createElementNS(static::NAMESPACE_NFSE, 'pedRegEvento');
        $ped->setAttribute('versao', static::VERSAO);
        $doc->appendChild($ped);

        $inf = static::el($doc, $ped, 'infPedReg');
        $inf->setAttribute('Id', 'PRE' . $nfs->nfsechave . static::TIPO_EVENTO_CANCELAMENTO);
        static::el($doc, $inf, 'tpAmb', (string) $ambiente);
        static::el($doc, $inf, 'verAplic', static::VER_APLIC);
        static::el($doc, $inf, 'dhEvento', static::dataHora(Carbon::now()));
        $filial = $nfs->Filial;
        static::el($doc, $inf, $filial->Pessoa->fisica ? 'CPFAutor' : 'CNPJAutor', static::documentoFilial($filial));
        static::el($doc, $inf, 'chNFSe', $nfs->nfsechave);
        $evento = static::el($doc, $inf, 'e' . static::TIPO_EVENTO_CANCELAMENTO);
        static::el($doc, $evento, 'xDesc', 'Cancelamento de NFS-e');
        static::el($doc, $evento, 'cMotivo', (string) $motivo);
        static::el($doc, $evento, 'xMotivo', static::texto($justificativa, 255));

        return $doc->saveXML($doc->documentElement);
    }

    protected static function el(DOMDocument $doc, DOMElement $pai, string $nome, ?string $valor = null): DOMElement
    {
        $el = $doc->createElementNS(static::NAMESPACE_NFSE, $nome);
        if ($valor !== null) {
            $el->appendChild($doc->createTextNode($valor));
        }
        $pai->appendChild($el);
        return $el;
    }

    // AAAA-MM-DDThh:mm:ss-04:00 (horario de Cuiaba, o do app)
    protected static function dataHora(Carbon $data): string
    {
        return $data->copy()->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i:sP');
    }

    protected static function valor($valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    /**
     * Texto no formato que o XSD aceita: uma linha so (os campos de texto da DPS nao
     * aceitam quebra de linha) e so caracteres de espaco a U+00FF. A quebra de linha
     * digitada vira " | "; aspas, travessoes e reticencias tipograficos viram ASCII; o
     * resto fora da faixa (emoji etc.) sai. Corta no tamanho maximo do campo.
     */
    public static function texto($texto, int $max): string
    {
        $texto = str_replace(["\r\n", "\r", "\n"], ' | ', (string) $texto);
        $texto = strtr($texto, [
            "\t" => ' ',
            '“' => '"', '”' => '"', '„' => '"',
            '‘' => "'", '’' => "'", '‚' => "'",
            '–' => '-', '—' => '-', '•' => '-',
            '…' => '...',
        ]);
        $texto = preg_replace('/[^\x{20}-\x{FF}]/u', '', $texto);
        $texto = preg_replace('/ +/', ' ', $texto);
        $texto = preg_replace('/\s*\|(\s*\|)*\s*/', ' | ', $texto);
        $texto = trim($texto, " |");
        return trim(mb_substr($texto, 0, $max));
    }
}
