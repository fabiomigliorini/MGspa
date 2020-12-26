<?php

namespace Mg\Pix;

use Carbon\Carbon;

class BrCodeService
{
    /*
    * Esta função retorna a string preenchendo com 0 na esquerda, com tamanho o especificado, concatenando com o valor do campo
    */
    public static function preencherCampo($valor)
    {
        return str_pad(strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
    }

    /*
    * Esta função auxiliar calcula o CRC-16/CCITT-FALSE
    */
    public static function calcularChecksum($str)
    {
        function charCodeAt($str, $i)
        {
            return ord(substr($str, $i, 1));
        }
        $crc = 0xFFFF;
        $strlen = strlen($str);
        for ($c = 0; $c < $strlen; $c++) {
            $crc ^= charCodeAt($str, $c) << 8;
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
            }
        }
        $hex = $crc & 0xFFFF;
        $hex = dechex($hex);
        $hex = strtoupper($hex);

        return $hex;
    }

    // public static function montar(PixCob $cob)
    public static function montar($cob)
    {
        if ($cob->PixStatus->pixstatus == 'NOVA') {
            return null;
        }

        // Rotina montará a variável que correspondente ao payload no padrão EMV-QRCPS-MPM
        $payload_format_indicator = '01';
        $point_of_initiation_method = '12';
        $merchant_account_information = '00' . static::preencherCampo('BR.GOV.BCB.PIX');
        $merchant_category_code = '0000';
        $transaction_currency = '986';
        $country_code = 'BR';

        $payloadBrCode = "00" . static::preencherCampo($payload_format_indicator); // [obrigatório] Payload Format Indicator, valor fixo: 01

        $pagoUmaVez = true; // Insira se deseja que o QR Code pode ser pago uma única vez. Opções: true ou false
        if ($pagoUmaVez) { // Se o QR Code for para pagamento único (só puder ser utilizado uma vez), a variável $pagoUmaVez deverá ser true
            $payloadBrCode .= "01" . static::preencherCampo($point_of_initiation_method); // [opcional] Point of Initiation Method Se o valor 12 estiver presente, significa que o BR Code só pode ser utilizado uma vez.
        }

        $tipo = "dinamico"; // Insira o tipo de QR Code que deseja gerar. Opções: dinamico ou estatico
        if ($tipo === "dinamico") {
            $location = str_replace("https://", "", $cob->location); // [obrigatório] URL payload do PSP do recebedor que contém as informações da cobrança
            $merchant_account_information .= '25' . static::preencherCampo($location);
        } else { // Caso seja estático
            $merchant_account_information .= '01' . static::preencherCampo($cob->Portador->pixdict); //Chave do destinatário do pix, pode ser EVP, e-mail, CPF ou CNPJ.
        }
        $payloadBrCode .= '26' .  static::preencherCampo($merchant_account_information); // [obrigatório] Indica arranjo específico; “00” (GUI) e valor fixo: br.gov.bcb.pix

        $payloadBrCode .= '52' . static::preencherCampo($merchant_category_code); // [obrigatório] Merchant Category Code “0000” ou MCC ISO18245

        $payloadBrCode .= '53' . static::preencherCampo($transaction_currency); // [obrigatório] Moeda, “986” = BRL: real brasileiro - ISO4217

        $payloadBrCode .= '54';  // [opcional] Valor da transação. Utilizar o . como separador decimal.

        $valorLivre = false; // Insira se deseja que o valor da cobrança seja livre. Opções: true ou false
        $payloadBrCode .= ($valorLivre === true) ? static::preencherCampo('0.00') : static::preencherCampo($cob->valororiginal) ;

        $payloadBrCode .= '58' . static::preencherCampo($country_code); // [obrigatório] “BR” – Código de país ISO3166-1 alpha 2

        $payloadBrCode .= '59';
        $payloadBrCode .= static::preencherCampo($cob->nomerecebedor); // [obrigatório] Nome do beneficiário/recebedor. Máximo: 25 caracteres.

        $payloadBrCode .= '60' . static::preencherCampo($cob->Portador->Filial->Pessoa->Cidade->cidade); // [obrigatório] Nome cidade onde é efetuada a transação. Máximo 15 caracteres.

        $payloadBrCode .= '61' . static::preencherCampo($cob->Portador->Filial->Pessoa->cep); // [opcional] CEP da cidade onde é efetuada a transação.

        $txID = ($tipo === "dinamico") ? '***' : $cob->txid; // [opcional] Identificador da transação.
        $aditional_data_field_template = '05' . static::preencherCampo($txID);
        $payloadBrCode .= '62' . static::preencherCampo($aditional_data_field_template);


        $payloadBrCode .= "6304"; // Adiciona o campo do CRC no fim da linha do pix.

        $payloadBrCode .= static::calcularChecksum($payloadBrCode); // Calcula o checksum CRC16 e acrescenta ao final.

        return $payloadBrCode;
    }
}
