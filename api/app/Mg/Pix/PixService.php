<?php

namespace Mg\Pix;

use Carbon\Carbon;

use Dompdf\Dompdf;

use Mg\NaturezaOperacao\Operacao;
use Mg\Negocio\Negocio;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Portador\Portador;
use Mg\Pix\Sicredi\PixSicrediService;
use Illuminate\Support\Facades\DB;
use Mg\Pdv\Pdv;
use Mg\Pessoa\Pessoa;

class PixService
{
    public static function criarPixCobNegocio(Negocio $negocio)
    {
        // Valida se é de saída
        if ($negocio->NaturezaOperacao->codoperacao != Operacao::SAIDA) {
            throw new \Exception("Operação não é de saída!", 1);
        }

        // calcula saldo a pagar do negocio
        $pago = \Mg\Negocio\NegocioService::valorPago($negocio);
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

    // Cobranca PIX do wizard do PDV para um documento (M5 doc-3): o negocio,
    // ou nenhum (recebimento no balcao, M7), com a pessoa que paga
    // PDV nulo: cobranca feita pelo contas (recebimento de titulo, M6.1)
    public static function criarPixCobPdv(Float $valor, ?Pdv $pdv, ?Negocio $negocio, int $codportador = null, ?Pessoa $pessoa = null)
    {
        $pessoa = $negocio->Pessoa ?? $pessoa;
        $codfilial = $negocio->codfilial ?? $pdv->codfilial ?? null;

        // procura ou cria registro
        $cob = new PixCob([
            'codnegocio' => $negocio->codnegocio ?? null,
            'codpdv' => $pdv->codpdv ?? null,
            'valororiginal' => $valor
        ]);

        // 3 dias = 3 * 24 * 60 * 60
        $cob->expiracao = 259200;

        // Status NOVA
        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => 'NOVA'
        ]);
        $cob->codpixcobstatus = $status->codpixcobstatus;

        // CNPJ ou CPF
        if (!empty($pessoa->cnpj)) {
            $cob->nome = $pessoa->pessoa;
            if ($pessoa->fisica) {
                $cob->cpf = $pessoa->cnpj;
            } else {
                $cob->cnpj = $pessoa->cnpj;
            }
        }

        // Texto para ser apresentado pro cliente
        if ($negocio && $pdv) {
            $codnegocio = str_pad($negocio->codnegocio, 8, '0', STR_PAD_LEFT);
            $cob->solicitacaopagador = "MG Papelaria! Pagamento referente negócio #{$codnegocio} PDV #{$pdv->uuid}!";
        } elseif ($pdv) {
            $cob->solicitacaopagador = "MG Papelaria! Pagamento PDV #{$pdv->uuid}!";
        } else {
            $cob->solicitacaopagador = "MG Papelaria! Pagamento de títulos!";
        }

        // Se codportador informado, busca direto
        if ($codportador) {
            $portador = Portador::where('codportador', $codportador)
                ->whereNull('inativo')
                ->whereNotNull('pixdict')
                ->first();
        } else {
            // Fallback: procura portador do BB pra filial com convenio
            $portador = Portador::where('codfilial', $codfilial)
                ->whereNull('inativo')
                ->where('codbanco', 1)
                ->whereNotNull('pixdict')
                ->orderBy('codportador')
                ->first();

            // Procura portador do BB sem filial com convenio
            if ($portador === null) {
                $portador = Portador::whereNull('codfilial')
                    ->whereNull('inativo')
                    ->where('codbanco', 1)
                    ->whereNotNull('pixdict')
                    ->orderBy('codportador')
                    ->first();
            }
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

            case 748:
                return PixSicrediService::transmitirPixCob($cob);
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

            case 748:
                $cob = PixSicrediService::consultarPixCob($cob);
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
        ]);
        $pix->codportador = $portador->codportador;
        $pix->txid = $arrPix['txid'] ?? null;
        if (empty($pix->codpixcob) && !empty($pix->txid)) {
            $pixCob = PixCob::where('codportador', $pix->codportador)
                ->where('txid', $pix->txid)->first();
        }
        if (!empty($pixCob)) {
            $pix->codpixcob = $pixCob->codpixcob;
        }
        $pix->valor = $arrPix['valor'] ?? null;

        $horario = Carbon::parse($arrPix['horario'] ?? null);
        $horario->setTimezone(config('app.timezone'));
        $pix->horario = $horario;

        if (isset($arrPix['pagador'])) {
            $pix->nome = $arrPix['pagador']['nome'] ?? null;
            $pix->cpf = $arrPix['pagador']['cpf'] ?? null;
            $pix->cnpj = $arrPix['pagador']['cnpj'] ?? null;
        }
        if (!empty($pix->nome)) {
            $pix->nome = primeiraLetraMaiuscula($pix->nome);
        }
        $pix->infopagador = $arrPix['infoPagador'] ?? null;
        $pix->save();

        $arrDevs = $arrPix['devolucoes'] ?? [];
        foreach ($arrDevs as $arrDev) {
            $pixDevolucao = PixDevolucao::firstOrNew([
                'codpix' => $pix->codpix,
                'rtrid' => $arrDev['rtrId']
            ]);
            $pixDevolucao->id = $arrDev['id'] ?? null;
            $pixDevolucao->valor = $arrDev['valor'] ?? null;
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
        } else {
            static::processarPixChave($pix);
        }
        return $pix;
    }

    // Pessoa do pagador (CPF/CNPJ do PIX ou da cobranca), quando cadastrada
    public static function pessoaDoPagador($cpf, $cnpj): ?int
    {
        $doc = !empty($cpf) ? $cpf : $cnpj;
        if (empty($doc)) {
            return null;
        }
        return Pessoa::where('cnpj', $doc)->whereNull('inativo')->orderBy('codpessoa')->value('codpessoa');
    }

    // PIX pela chave (sem cobranca) que o banco confirmou: e' um fato, vira
    // pagamento efetivado, sem documento, no razao do banco (conceito do
    // Fabio, 09/10/2026). Fica em "Pagamentos nao resolvidos" ate' alguem
    // amarrar (titulo, venda, adiantamento) ou devolver. So' a partir do
    // inicio do razao; uma vez criado, a importacao nao mexe nele.
    public static function processarPixChave(Pix $pix): ?Pagamento
    {
        if (empty($pix->valor) || $pix->valor <= 0 || empty($pix->horario)) {
            return null;
        }
        if (Carbon::parse($pix->horario)->lt(\Mg\Conferencia\ConferenciaService::inicio())) {
            return null;
        }
        if (Pagamento::where('codpix', $pix->codpix)->exists()) {
            return null;
        }
        $portador = Portador::find($pix->codportador);
        return PagamentoService::criar([
            'meio' => PagamentoService::MEIO_PIX,
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'principal' => (float) $pix->valor,
            'juros' => 0,
            'multa' => 0,
            'desconto' => 0,
            'transacao' => Carbon::parse($pix->horario),
            'efetivacao' => Carbon::now(),
            'codportadordestino' => $pix->codportador,
            'codfilial' => $portador->codfilial ?? null,
            'codpix' => $pix->codpix,
            'autorizacao' => $pix->e2eid,
            'codpessoa' => static::pessoaDoPagador($pix->cpf, $pix->cnpj),
            'observacoes' => $pix->infopagador ? mb_substr($pix->infopagador, 0, 255) : null,
        ]);
    }

    // PIX confirmado vira pagamento efetivado (o banco ja' confirmou, M4
    // doc-3). Sem negocio (recebimento de titulo, M6.1) nasce sem documento:
    // a tela que criou a cobranca o amarra aos titulos ao finalizar.
    public static function processarPixCobNegocio(PixCob $cob)
    {
        $valorpagamento = $cob->PixS()->sum('valor');
        if ($valorpagamento <= 0) {
            return;
        }
        $pag = Pagamento::firstOrNew([
            'codpixcob' => $cob->codpixcob
        ]);
        // sem negocio, depois de amarrado aos titulos os valores sao deles
        if (empty($cob->codnegocio) && $pag->exists) {
            return;
        }
        PagamentoService::preencher($pag, [
            'codnegocio' => $cob->codnegocio,
            'codfilial' => $cob->Negocio->codfilial ?? $cob->Pdv->codfilial ?? $cob->Portador->codfilial,
            'codpdv' => $cob->codpdv ?? $cob->Negocio->codpdv ?? null,
            'meio' => PagamentoService::MEIO_PIX,
            'principal' => $valorpagamento,
            'codportadordestino' => $cob->codportador,
            // sem negocio: a pessoa da cobranca (o pagador), para o orfao
            // aparecer com nome
            'codpessoa' => empty($cob->codnegocio)
                ? ($pag->codpessoa ?? static::pessoaDoPagador($cob->cpf, $cob->cnpj))
                : $cob->Portador->codpessoa,
            'codpix' => $cob->PixS[0]->codpix,
            'autorizacao' => $cob->PixS[0]->e2eid,
        ]);
        $pag->save();
        if ($pag->estado != PagamentoService::ESTADO_CANCELADO) {
            PagamentoService::efetivar($pag);
        }
        if (!empty($cob->codnegocio)) {
            \Mg\Negocio\NegocioService::fecharSePago($cob->Negocio);
        }
    }

    public static function consultarPix(
        Portador $portador,
        Carbon $inicio = null,
        Carbon $fim = null,
        int $pagina = 0
    ) {
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

            case 748:
                $pixRecebidos = PixSicrediService::consultarPix(
                    $portador,
                    $inicio,
                    $fim,
                    $pagina
                );
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

            case 748:
                if (empty($cob->qrcode)) {
                    throw new \Exception('Sem QRcode registrado!', 1);
                }
                $qrcode = PixBbApiService::qrCode($cob->qrcode);
                $qrcode = 'data:image/png;base64,' . base64_encode($qrcode);
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

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper([0, 0, 204, 650]);
        $dompdf->render();
        $pdf = $dompdf->output();
        return $pdf;
    }

    public static function imprimirQrCode(PixCob $cob, $impressora)
    {
        $url = \URL::temporarySignedRoute('pix.cob.pdf', now()->addMinutes(10), ['codpixcob' => $cob->codpixcob]);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "' . config('services.ably.key') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [\"fit-to-page\"], \"copies\": 1}" }\'';
        exec($cmd);
    }

    public static function listagem(
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

        $inner = '
            select
                pix.horario as horario,
                pix.valor as valor,
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
                $inner .= ' inner join tblpixcob cob on (cob.codpixcob = pix.codpixcob) ';
                break;
            default:
                $inner .= ' left join tblpixcob cob on (cob.codpixcob = pix.codpixcob) ';
                break;
        }

        $inner .= '
            left join tblportador port on (port.codportador = pix.codportador)
            left join tblnegocio n on (n.codnegocio = cob.codnegocio)
            left join tblusuario u on (u.codusuario = n.codusuario)
        ';

        // if ($negocio !== 'com') {
        //     $inner .= '
        //         union all
        //         select
        //             cob.criacao as horario,
        //             cob.valororiginal as valor,
        //             null as codpix,
        //             null as nome,
        //             null as cpf,
        //             null as cnpj,
        //             cob.codpixcob,
        //             cob.codnegocio,
        //             u.codusuario,
        //             u.usuario,
        //             port.codportador,
        //             port.portador,
        //             null as e2eid,
        //             null as txid,
        //             null as infopagador
        //         from tblpixcob cob
        //         --inner join tblpixcobstatus st on (st.codpixcobstatus = cob.codpixcobstatus and st.pixcobstatus = \'NOVA\')
        //         left join tblportador port on (port.codportador = cob.codportador)
        //         left join tblnegocio n on (n.codnegocio = cob.codnegocio)
        //         left join tblusuario u on (u.codusuario = n.codusuario)
        //         where cob.codpixcobstatus = 1
        //         --where not exists (select 1 from tblpix p where p.codpixcob = cob.codpixcob)
        //     ';
        // }

        $sql = "select * from ({$inner}) resultado ";

        $where = 'where';

        if (!empty($nome)) {
            $sql .= " {$where} nome ilike :nome ";
            $params['nome'] = '%' . str_replace(' ', '%', $nome) . '%';
            $where = 'and';
        }

        switch ($negocio) {
            case 'sem':
                $sql .= " {$where} codnegocio is null ";
                $where = 'and';
                break;
        }

        if (!empty($horarioinicial)) {
            $sql .= " {$where} horario >= :horarioinicial ";
            $params['horarioinicial'] = $horarioinicial->format('Y-m-d H:i:s');
            $where = 'and';
        }

        if (!empty($horariofinal)) {
            $sql .= " {$where} horario <= :horariofinal ";
            $params['horariofinal'] = $horariofinal->format('Y-m-d H:i:s');
            $where = 'and';
        }

        if (!empty($valorinicial)) {
            $sql .= " {$where} valor >= :valorinicial ";
            $params['valorinicial'] = $valorinicial;
            $where = 'and';
        }

        if (!empty($valorfinal)) {
            $sql .= " {$where} valor <= :valorfinal ";
            $params['valorfinal'] = $valorfinal;
            $where = 'and';
        }

        if (!empty($cpf)) {
            $cpf = numeroLimpo($cpf);
            if (!empty($cpf)) {
                $sql .= " {$where} coalesce(to_char(cpf, '00000000000'), to_char(cnpj, '00000000000000')) ilike :cpf ";
                $params['cpf'] = "%{$cpf}%";
                $where = 'and';
            }
        }

        switch ($sort) {
            case 'nome':
                $sql .= ' order by nome asc, horario desc ';
                break;
            case 'valor':
                $sql .= ' order by valor desc, horario desc ';
                break;
            case 'horario':
            default:
                $sql .= ' order by horario desc ';
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

    public static function descobreNome($cnpjCpf)
    {

        $sql = '
            select
                pi.cpf,
                pi.cnpj,
                pi.nome
            from tblpix pi
            where cast(coalesce(to_char(pi.cnpj, \'FM00000000000000\'), to_char(pi.cpf, \'FM00000000000\')) as varchar) = :cnpjCpf
            ORDER BY criacao desc
            LIMIT 1
        ';

        $result = DB::select($sql, [
            'cnpjCpf' => $cnpjCpf
        ]);

        return $result[0] ?? null;
    }
}
