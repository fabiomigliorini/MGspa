<?php

namespace Mg\Pix;

use Carbon\Carbon;

use Dompdf\Dompdf;

use Mg\NaturezaOperacao\Operacao;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Portador\Portador;
use Mg\Pix\GerenciaNet\GerenciaNetService;
use Mg\FormaPagamento\FormaPagamento;
use DB;

class PixService
{
    public static function criarPixCobNegocio (Negocio $negocio)
    {
        // Valida se é de saída
        if ($negocio->NaturezaOperacao->codoperacao != Operacao::SAIDA) {
            throw new \Exception("Operação não é de saída!", 1);
        }

        // calcula saldo a pagar do negocio
        $pago = $negocio->NegocioFormaPagamentoS()->sum('valorpagamento');
        $saldo = $negocio->valortotal - $pago;
        if ($saldo <= 0) {
            throw new \Exception("Não existe saldo à pagar para gerar o PIX!", 1);
        }

        // procura ou cria registro
        $cob = new PixCob([
            'codnegocio' => $negocio->codnegocio,
            'valororiginal' => $saldo
        ]);

        // 3 dias = 3 * 24 * 60 * 60
        $cob->expiracao = 259200;

        // Status NOVA
        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => 'NOVA'
        ]);
        $cob->codpixcobstatus = $status->codpixcobstatus;

        // CNPJ ou CPF
        if (!empty($negocio->Pessoa->cnpj)) {
            $cob->nome = $negocio->Pessoa->pessoa;
            if ($negocio->Pessoa->fisica) {
                $cob->cpf = $negocio->Pessoa->cnpj;
            } else {
                $cob->cnpj = $negocio->Pessoa->cnpj;
            }
        // } elseif (!empty($negocio->cpf)) {
            // $cob->cpf = $negocio->cpf;
        }

        // Texto para ser apresentado pro cliente
        $codnegocio = str_pad($negocio->codnegocio, 8, '0', STR_PAD_LEFT);
        $cob->solicitacaopagador = "MG Papelaria! Pagamento referente negócio #{$codnegocio}!";

        // Portador Hardcoded por enquanto
        // $cob->codportador = env('PIX_GERENCIANET_CODPORTADOR');
        //procura portador do BB pra filial com convenio
        $portador = Portador::where('codfilial', $negocio->codfilial)
            ->whereNull('inativo')
            ->where('codbanco', 1)
            ->whereNotNull('pixdict')
            ->orderBy('codportador')
            ->first();

        //procura portador do BB sem filial com convenio
        if ($portador === null) {
            $portador = Portador::whereNull('codfilial')
                ->whereNull('inativo')
                ->where('codbanco', 1)
                ->whereNotNull('pixdict')
                ->orderBy('codportador')
                ->first();
        }

        // se nao localizou nenhum portador
        if ($portador === null) {
            throw new \Exception('Nenhum portador disponível para a filial');
        }

        $cob->codportador = $portador->codportador;
        $cob->save();

        $cob->txid = 'PIXCOB' . str_pad($cob->codpixcob, 29, '0', STR_PAD_LEFT);
        $cob->save();

        return $cob;
    }

    public static function transmitirPixCob(PixCob $cob)
    {
        if (empty($cob->Portador->pixdict)) {
            throw new \Exception("Não existe Chave PIX DICT cadastrada para o portador!", 1);
        }
        switch ($cob->Portador->Banco->numerobanco) {
            case 1:
                return PixBbService::transmitirPixCob($cob);
                break;

            case 364:
                return GerenciaNetService::transmitirPixCob($cob);
                break;

            default:
                throw new \Exception("Sem integração definida para o Banco {$cob->Portador->Banco->numerobanco}!", 1);
                break;
        }
    }

    public static function consultarPixCob(PixCob $cob)
    {
        if (empty($cob->Portador->pixdict)) {
            throw new \Exception("Não existe Chave PIX DICT cadastrada para o portador!", 1);
        }
        switch ($cob->Portador->Banco->numerobanco) {
            case 1:
                $cob = PixBbService::consultarPixCob($cob);
                break;

            case 364:
                $cob = GerenciaNetService::consultarPixCob($cob);
                break;

            default:
                throw new \Exception("Sem integração definida para o Banco {$cob->Portador->Banco->numerobanco}!", 1);
                break;
        }
        return $cob;
    }

    public static function importarPix(Portador $portador, array $arrPix, PixCob $pixCob = null)
    {
        $pix = Pix::firstOrNew([
            'e2eid' => $arrPix['endToEndId'],
            'codportador' => $portador->codportador,
        ]);
        $pix->txid = $arrPix['txid']??null;
        if (empty($pix->codpixcob) && !empty($pix->txid)) {
            $pixCob = PixCob::where('codportador', $pix->codportador)
                ->where('txid', $pix->txid)->first();
        }
        if (!empty($pixCob)) {
            $pix->codpixcob = $pixCob->codpixcob;
        }
        $pix->valor = $arrPix['valor']??null;

        $horario = Carbon::parse($arrPix['horario']??null);
        $horario->setTimezone(config('app.timezone'));
        $pix->horario = $horario;

        if (isset($arrPix['pagador'])) {
            $pix->nome = $arrPix['pagador']['nome']??null;
            $pix->cpf = $arrPix['pagador']['cpf']??null;
            $pix->cnpj = $arrPix['pagador']['cnpj']??null;
        }
        if (!empty($pix->nome)) {
            $pix->nome = primeiraLetraMaiuscula($pix->nome);
        }
        $pix->infopagador = $arrPix['infoPagador']??null;
        $pix->save();

        $arrDevs = $arrPix['devolucoes']??[];
        foreach ($arrDevs as $arrDev) {
            $pixDevolucao = PixDevolucao::firstOrNew([
                'codpix' => $pix->codpix,
                'rtrid' => $arrDev['rtrId']
            ]);
            $pixDevolucao->id = $arrDev['id']??null;
            $pixDevolucao->valor = $arrDev['valor']??null;
            if (!empty($arrDev['horario']['solicitacao'])) {
                $pixDevolucao->solicitacao = Carbon::parse($arrDev['horario']['solicitacao']);
            }
            if (!empty($arrDev['horario']['liquidacao'])) {
                $pixDevolucao->liquidacao = Carbon::parse($arrDev['horario']['liquidacao']);
            }
            $status = PixDevolucaoStatus::firstOrCreate([
                'pixdevolucaostatus' => $arrDev['status']
            ]);
            $pixDevolucao->codpixdevolucaostatus = $status->codpixdevolucaostatus;
            $pixDevolucao->save();
        }

        if (!empty($pix->codpixcob)) {
            static::processarPixCobNegocio($pix->PixCob);
        }
        return $pix;
    }

    public static function processarPixCobNegocio (PixCob $cob)
    {
        if (empty($cob->codnegocio)) {
            return;
        }
        $valorpagamento = $cob->PixS()->sum('valor');
        if ($valorpagamento <= 0) {
            return;
        }
        $nfp = NegocioFormaPagamento::firstOrNew([
            'codpixcob' => $cob->codpixcob
        ]);
        $nfp->codnegocio = $cob->codnegocio;
        $nfp->valorpagamento = $valorpagamento;
        $fp = FormaPagamento::firstOrNew(['pix' => true, 'integracao' => true]);
        if (!$fp->exists) {
            $fp->formapagamento = 'PIX';
            $fp->avista = true;
            $fp->integracao = true;
            $fp->save();
        }
        $nfp->codformapagamento = $fp->codformapagamento;
        $nfp->save();
        $fechado = \Mg\Negocio\NegocioService::fecharSePago($cob->Negocio);
    }

    public static function consultarPix(
        Portador $portador,
        Carbon $inicio = null,
        Carbon $fim = null,
        int $pagina = 0
    ){
        if (empty($portador->pixdict)) {
            throw new \Exception("Não existe Chave PIX DICT cadastrada para o portador!", 1);
        }
        switch ($portador->Banco->numerobanco) {
            case 1:
                $pixRecebidos = PixBbService::consultarPix(
                    $portador,
                    $inicio,
                    $fim,
                    $pagina
                );
                break;

            case 364:
                $pixRecebidos = GerenciaNetService::consultarPix($portador);
                break;

            default:
                throw new \Exception("Sem integração definida para o Banco {$portador->Banco->numerobanco}!", 1);
                break;
        }
        return $pixRecebidos;
    }

    public static function pdf(PixCob $cob)
    {
        switch ($cob->Portador->Banco->numerobanco) {
            case 1:
                if (empty($cob->qrcode)) {
                    throw new \Exception('Sem QRcode registrado!', 1);
                }
                $qrcode = PixBbApiService::qrCode($cob->qrcode);
                $qrcode = 'data:image/png;base64,' . base64_encode($qrcode);
                break;

            case 364:
                if (empty($cob->locationid)) {
                    throw new \Exception('Sem LocationID registrado!', 1);
                }
                $qrcode = GerenciaNetService::qrCode($cob->locationid);
                $qrcode = $qrcode['imagemQrcode'];
                break;

            default:
                throw new \Exception("Sem integração definida para o Banco {$cob->Portador->Banco->numerobanco}!", 1);
                break;
        }


        $html = view('pix/imprimir', ['cob' => $cob, 'qrcode' => $qrcode])->render();
        $dompdf = new Dompdf();

        $options = $dompdf->getOptions();
        $options->setDefaultFont('helvetica');
        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);
        $dompdf->set_paper(array(0,0,204,650));
        // $dompdf->set_option('dpi', 72);
        $dompdf->render();
        $pdf = $dompdf->output();
        return $pdf;

    }

    public static function imprimirQrCode(PixCob $cob, $impressora)
    {
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "' . env('ABLY_APP_KEY') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora . '", "data": "{\"url\": \"' . env('APP_URL') . 'api/v1/pix/cob/' . $cob->codpixcob . '/pdf\", \"method\": \"get\", \"options\": [\"fit-to-page\"], \"copies\": 1}" }\'';
        exec($cmd);
    }

    public static function listagem (
        $page = 1,
        $per_page = 50,
        $sort = 'horario',
        $nome = null,
        $cpf = null,
        $negocio = 'todos',
        float $valorinicial = null,
        float $valorfinal = null,
        Carbon $horarioinicial = null,
        Carbon $horariofinal = null
    ) {

        if (empty($page)) {
            $page = 1;
        }

        $from = $per_page * ($page - 1);
        $params = [
            'limit' => $per_page,
            'offset' => $from
        ];

        $sql = '
            select
        		coalesce(pix.horario, cob.criacao) as horario,
        		coalesce(pix.valor, cob.valororiginal) as valor,
                pix.codpix,
        		pix.nome,
        		pix.cpf,
        		pix.cnpj,
                cob.codpixcob,
        		cob.codnegocio,
                u.codusuario,
        		u.usuario,
                port.codportador,
        		port.portador,
        		pix.e2eid,
        		pix.txid,
        		pix.infopagador
        	from tblpix pix
        ';

        switch ($negocio) {
            case 'com':
                $sql .= ' inner join tblpixcob cob on (cob.codpixcob = pix.codpixcob) ';
                break;
            case 'sem':
            case 'todos':
            default:
                $sql .= ' full join tblpixcob cob on (cob.codpixcob = pix.codpixcob) ';
                break;
        }

        $sql .= '
        	left join tblportador port on (port.codportador = coalesce(pix.codportador, cob.codportador))
        	left join tblnegocio n on (n.codnegocio = cob.codnegocio)
        	left join tblusuario u on (u.codusuario = n.codusuario)
        ';

        $where = 'where';

        if (!empty($nome)) {
            $sql .= " {$where}  pix.nome ilike :nome ";
            $params['nome'] = '%' . str_replace(' ', '%', $nome) . '%';
            $where = 'and';
        }

        switch ($negocio) {
            case 'sem':
                $sql .= " {$where} cob.codnegocio is null ";
                $where = 'and';
                break;
            case 'todos':
            case 'com':
            default:
                break;
        }

        if (!empty($horarioinicial)) {
            $sql .= " {$where} coalesce(pix.horario, cob.criacao) >= :horarioinicial ";
            $params['horarioinicial'] = $horarioinicial->format('Y-m-d H:i:s');
            $where = 'and';
        }

        if (!empty($horariofinal)) {
            $sql .= " {$where} coalesce(pix.horario, cob.criacao) <= :horariofinal ";
            $params['horariofinal'] = $horariofinal->format('Y-m-d H:i:s');
            $where = 'and';
        }

        if (!empty($valorinicial)) {
            $sql .= " {$where} coalesce(pix.valor, cob.valororiginal) >= :valorinicial ";
            $params['valorinicial'] = $valorinicial;
            $where = 'and';
        }

        if (!empty($valorfinal)) {
            $sql .= " {$where} coalesce(pix.valor, cob.valororiginal) <= :valorfinal ";
            $params['valorfinal'] = $valorfinal;
            $where = 'and';
        }

        if (!empty($cpf)) {
            $cpf = numeroLimpo($cpf);
            if (!empty($cpf)) {
                $sql .= " {$where} coalesce(to_char(pix.cpf, '00000000000'), to_char(pix.cnpj, '00000000000000')) ilike :cpf ";
                $params['cpf'] = "%{$cpf}%";
                $where = 'and';
            }
        }

        switch ($sort) {
            case 'nome':
                $sql .= ' order by pix.nome asc, coalesce(pix.horario, cob.criacao) desc ';
                break;
            case 'valor':
                $sql .= ' order by coalesce(pix.valor, cob.valororiginal) desc, coalesce(pix.horario, cob.criacao) desc ';
                break;
            case 'horario':
            default:
                $sql .= ' order by coalesce(pix.horario, cob.criacao) desc ';
                break;
        }

        $sql .= '
            limit :limit
            offset :offset
        ';
        $data = DB::select($sql, $params);

        foreach ($data as $reg) {
            $reg->valor = doubleval($reg->valor);
        }

        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $per_page,
            'from' => $from,
            'to' => $from + count($data),
        ];
    }

}
