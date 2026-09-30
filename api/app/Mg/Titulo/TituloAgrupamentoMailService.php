<?php

namespace Mg\Titulo;

use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\DB;
use Mg\Filial\Filial;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscal;
use stdClass;
// use Mg\NFePHP\Mail\NFeAutorizadaMail;
// use Mg\TituloAgrupamento\TituloAgrupamento;
use Validator;

class TituloAgrupamentoMailService
{
    public static function mail(TituloAgrupamento $ta, $destinatario = null)
    {

        // se destinatario veio em branco, busca do cadastro
        if (empty($destinatario)) {
            $destinatario = $ta->Pessoa->PessoaEmailS()->where('cobranca', true)->get()->pluck('email')->implode(',');
        }
        if (empty($destinatario)) {
            throw new Exception("Não foi informado nenhum destinatário!");
        }

        // transforma em array
        if (strpos($destinatario, ',')) {
            $destinatarios = explode(',', $destinatario);
        } else {
            $destinatarios = [$destinatario];
        }

        // percorre array validando todos os enderecos
        foreach ($destinatarios as $key => $destinatario) {
            $destinatario = trim($destinatario);
            $destinatarios[$key] = $destinatario;
            $validator = Validator::make(['destinatario' => $destinatario], [
                'destinatario' => 'required|email'
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }
        }

        // envia o email
        $nfs = static::notasFiscaisVenda($ta);
        $negs = static::negocios($ta);
        $bols = static::boletos($ta);
        $baixas = static::titulosBaixados($ta);
        $mail = new TituloAgrupamentoMail($ta, $nfs, $negs, $bols, $baixas);
        Mail::to($destinatarios)->queue($mail);

        // retorna o sucesso na execucao
        return [
            'sucesso' => true,
            'mensagem' => 'Email enviado para: ' . implode(', ', $destinatarios),
            'destinatario' => $destinatarios,
        ];
    }


    public static function notasFiscaisVenda(TituloAgrupamento $ta)
    {
        $sql = '
            select distinct nf.* 
            from tblmovimentotitulo mov 
            inner join tbltitulo t on (t.codtitulo = mov.codtitulo)
            inner join tblnegocioformapagamento nfp on (nfp.codnegocioformapagamento = t.codnegocioformapagamento)
            inner join tblnegocioprodutobarra npb on (npb.codnegocio = nfp.codnegocio)
            inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnegocioprodutobarra = npb.codnegocioprodutobarra)
            inner join tblnotafiscal nf on (nf.codnotafiscal = nfpb.codnotafiscal)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
            where mov.codtituloagrupamento = :codtituloagrupamento
            and nat.venda = true 
            and nf.nfeautorizacao is not null
            and nf.nfecancelamento is null
            and nf.nfeinutilizacao is null
        ';
        return NotaFiscal::hydrate(
            DB::select($sql, [
                'codtituloagrupamento' => $ta->codtituloagrupamento
            ])
        );
    }

    public static function negocios(TituloAgrupamento $ta)
    {
        $sql = '
            select distinct neg.* 
            from tblmovimentotitulo mov 
            inner join tbltitulo t on (t.codtitulo = mov.codtitulo)
            inner join tblnegocioformapagamento nfp on (nfp.codnegocioformapagamento = t.codnegocioformapagamento)
            inner join tblnegocio neg on (neg.codnegocio = nfp.codnegocio)
            where mov.codtituloagrupamento = :codtituloagrupamento
            and neg.codnegociostatus = 2 
        ';
        return Negocio::hydrate(
            DB::select($sql, [
                'codtituloagrupamento' => $ta->codtituloagrupamento
            ])
        );
    }


    public static function boletos(TituloAgrupamento $ta)
    {
        $sql = '
            select distinct bol.* 
            from tblmovimentotitulo mov 
            inner join tbltitulo t on (t.codtitulo = mov.codtitulo)
            inner join tbltituloboleto bol on (bol.codtitulo = t.codtitulo)
            where mov.codtituloagrupamento = :codtituloagrupamento 
            and t.saldo > 0
            and bol.estadotitulocobranca = 1
        ';
        return TituloBoleto::hydrate(
            DB::select($sql, [
                'codtituloagrupamento' => $ta->codtituloagrupamento
            ])
        );
    }


    public static function titulosBaixados(TituloAgrupamento $ta)
    {
        $baixas = [];
        foreach ($ta->MovimentoTituloS as $mov) {
            if ($mov->codtipomovimentotitulo == MovimentoTituloService::TIPO_IMPLANTACAO || $mov->ehEstorno()) {
                continue;
            }
            // Pula as parcelas novas geradas pelo próprio agrupamento
            if ($mov->Titulo->codtituloagrupamento == $ta->codtituloagrupamento) {
                continue;
            }
            if (!isset($baixas[$mov->codtitulo])) {
                $baixas[$mov->codtitulo] = (object)[
                    'codtitulo' => $mov->codtitulo,
                    'filial' => $mov->Titulo->Filial->filial,
                    'numero' => $mov->Titulo->numero,
                    'emissao' => $mov->Titulo->emissao,
                    'vencimento' => $mov->Titulo->vencimentooriginal,
                    'valor' => $mov->Titulo->valor,
                    'principal' => null,
                    'desconto' => null,
                    'juros' => null,
                    'multa' => null,
                    'outras' => null,
                    'total' => null,
                ];
            }
            $b = $baixas[$mov->codtitulo];
            if ($mov->codtipomovimentotitulo == MovimentoTituloService::TIPO_AGRUPAMENTO) {
                // a baixa: juros, multa e desconto estao na mesma linha
                $b->principal += abs($mov->principal);
                $b->juros += $mov->juros;
                $b->multa += $mov->multa;
                $b->desconto += $mov->desconto;
                $b->total += abs($mov->total);
            } else {
                // outros movimentos no agrupamento (juros/multa/desconto de
                // historico nao convertido, ajuste...)
                $b->outras += $mov->principal;
            }
        }
        return collect($baixas)->sortBy('vencimento');
    }
}
