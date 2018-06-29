<?php

namespace Mg\NFePHP;

use Illuminate\Support\Facades\Mail;

use Mg\NFePHP\Mail\NFeAutorizadaMail;
use Mg\NotaFiscal\NotaFiscal;

class NFePHPRepositoryMail
{

    public static function mail(NotaFiscal $nf, $destinatario = null)
    {

        if ($nf->Filial->nfeambiente == 2) {
            return [
              'sucesso' => true,
              'mensagem' => 'Ignorado envio - BASE HOMOLOGACAO',
              'destinatario' => $destinatario,
            ];
        }

        if (empty($destinatario)) {
            $destinatario = $nf->Pessoa->emailnfe??$nf->Pessoa->email??$nf->Pessoa->emailcobranca;
        }

        if (empty($destinatario)) {
            throw new \Exception('Nenhum endereço de e-mail informado!');
        }

        if (empty($nf->nfeautorizacao)) {
            throw new \Exception('Esta NFe não está autorizada!');
        }

        if (!empty($nf->nfecancelamento)) {
            throw new \Exception('Esta NFe está cancelada!');
        }

        if (!empty($nf->nfeinutilizacao)) {
            throw new \Exception('Esta NFe está inutilizada!');
        }

        if (strpos($destinatario, ',')) {
            $destinatario = explode(',', $destinatario);
        } else {
            $destinatario = [$destinatario];
        }

        //return new NFeAutorizadaMail($nf);
        Mail::to($destinatario)->queue(new NFeAutorizadaMail($nf));

        return [
          'sucesso' => true,
          'mensagem' => 'Email enviado para: ' . implode(', ', $destinatario),
          'destinatario' => $destinatario,
        ];
    }

    public static function mailCancelamento (NotaFiscal $nf)
    {
      dd('mail-cancelamento');
    }

}
