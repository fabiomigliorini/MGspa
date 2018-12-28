<?php

namespace Mg\NFePHP;

use Illuminate\Support\Facades\Mail;

use Mg\NFePHP\Mail\NFeAutorizadaMail;
use Mg\NotaFiscal\NotaFiscal;
use Validator;

class NFePHPRepositoryMail
{

    public static function mail(NotaFiscal $nf, $destinatario = null)
    {

        // se destinatario veio em branco, busca do cadastro
        if (empty($destinatario)) {
            $destinatario = $nf->Pessoa->emailnfe??$nf->Pessoa->email??$nf->Pessoa->emailcobranca;
        }

        if (empty($destinatario)) {
            return [
                'sucesso' => true,
                'mensagem' => 'Não foi informado nenhum destinatário!',
                'destinatario' => [],
                'error' => []
            ];
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
            $validator = Validator::make(['destinatario'=>$destinatario], [
                'destinatario' => 'required|email'
            ]);
            if ($validator->fails()) {
                $erros = $validator->errors();
                return [
                    'sucesso' => false,
                    'mensagem' => $erros->first(),
                    'destinatario' => $destinatarios,
                    'error' => $erros
                ];
            }
        }

        // verifica se a nota fiscal é valida
        if (empty($nf->nfeautorizacao)) {
            throw new \Exception('Esta NFe não está autorizada!');
        }
        if (!empty($nf->nfecancelamento)) {
            throw new \Exception('Esta NFe está cancelada!');
        }
        if (!empty($nf->nfeinutilizacao)) {
            throw new \Exception('Esta NFe está inutilizada!');
        }

        // envia o email
        Mail::to($destinatarios)->queue(new NFeAutorizadaMail($nf));

        // retorna o sucesso na execucao
        return [
          'sucesso' => true,
          'mensagem' => 'Email enviado para: ' . implode(', ', $destinatarios),
          'destinatario' => $destinatarios,
        ];
    }

    public static function mailCancelamento (NotaFiscal $nf)
    {
      dd('mail-cancelamento');
    }

}
