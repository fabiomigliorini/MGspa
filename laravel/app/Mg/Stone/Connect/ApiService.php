<?php

namespace Mg\Stone\Connect;

use Mg\Stone\StoneFilial;

class ApiService
{
    public static function token ($chaveprivada)
    {
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/token';
        $auth = "Authorization: Bearer {$chaveprivada}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function establishmentCreateExistingStone (
        $token,
        $legal_name,
        $business_name,
        $document_number,
        $stone_code
    )
    {
        $body = json_encode([
            "is_establishment_to_production" => true,
            "legal_name" => $legal_name,
            "business_name" => $business_name,
            "document_number" => $document_number,
            "stone_code" => $stone_code
        ]);
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/establishment/create-existing-stone';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function EstablishmentGetAll ($token)
    {
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/establishment/get-all';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function webhook ($token)
    {
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/webhook';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function webhookPosApplication ($token, $establishment_id)
    {
        $body = json_encode([
            "establishment_id" => $establishment_id,
            "postback_url" => "https://api.mgspa.mgpapelaria.com.br/api/v1/stone-connect/webhook/pos-application",
            "new_domain" => true,
        ]);
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/webhook/pos-application';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function webhookPreTransactionStatus ($token, $establishment_id)
    {
        $body = json_encode([
            "establishment_id" => $establishment_id,
            "postback_url" => "https://api.mgspa.mgpapelaria.com.br/api/v1/stone-connect/webhook/pre-transaction-status",
            "new_domain" => true,
        ]);
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/webhook/pre-transaction-status';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function webhookProcessedTransaction ($token, $establishment_id)
    {
        $body = json_encode([
            "establishment_id" => $establishment_id,
            "postback_url" => "https://api.mgspa.mgpapelaria.com.br/api/v1/stone-connect/webhook/processed-transaction",
            "new_domain" => true,
        ]);
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/webhook/processed-transaction';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function webhookPrintNoteStatus ($token, $establishment_id)
    {
        $body = json_encode([
            "establishment_id" => $establishment_id,
            "postback_url" => "https://api.mgspa.mgpapelaria.com.br/api/v1/stone-connect/webhook/print-note-status",
            "new_domain" => true,
        ]);
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/webhook/print-note-status';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function preTransactionCreate (
        $token,
        $establishment_id,
        $amount,
        $information_title,
        $pos_reference_id = null,
        $payment_type = null,
        $installment = null,
        $installment_type = null)
    {

        $amount = floatval($amount) * 100;
        $amount = round($amount, 0);
        $body = [
            "amount" => $amount,
            "establishment_id" => $establishment_id,
        ];
        if (!empty($pos_reference_id)) {
            $body["pos_reference_id"] = $pos_reference_id;
        }
        if (!empty($information_title)) {
            $body["information_title"] = $information_title;
        }
        if (!empty($payment_type)) {
            $body["payment"]["type"] = intval($payment_type);
        }
        if (!empty($installment)) {
            if ($installment >= 2) {
                $body["payment"]["installment"] = intval($installment);
                if (!empty($installment_type)) {
                    $body["payment"]["installment_type"] = intval($installment_type);
                }
            }
        }
        $body = json_encode($body);

        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/pre-transaction/create';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
	    CURLOPT_SSL_VERIFYHOST => 0,
	    CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function preTransactionSingle ($token, $pre_transaction_id)
    {
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/pre-transaction/single/' . $pre_transaction_id;
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function transactionSinglePreTransacion ($token, $pre_transaction_id)
    {
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/transactions/single/pre-transaction/' . $pre_transaction_id;
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function transactionSingleStone ($token, $stone_transaction_id)
    {
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/transactions/single/stone/' . $stone_transaction_id;
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	     
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function posControlConfigurationCreate (
        $token,
        $establishment_id,
        $use_without_pos_config = false,
        $activate_linked_pos_config = false,
        $activate_unlinked_and_linked_pos_config = true,
        $activate_single_information_automatic_select = false,
        $activate_dispose_transaction_any_pos = false,
        $lock_app = false,
        $view_error_request = true,
        $display_view_cancel_pre_transaction = true,
        $instruction_activation_time = 1500
    )
    {
        $body = [
            "establishment_id" => $establishment_id,
            "use_without_pos_config" => $use_without_pos_config,
            "activate_linked_pos_config" => $activate_linked_pos_config,
            "activate_unlinked_and_linked_pos_config" => $activate_unlinked_and_linked_pos_config,
            "activate_single_information_automatic_select" => $activate_single_information_automatic_select,
            "activate_dispose_transaction_any_pos" => $activate_dispose_transaction_any_pos,
            "lock_app" => $lock_app,
            "view_error_request" => $view_error_request,
            "display_view_cancel_pre_transaction" => $display_view_cancel_pre_transaction,
            "instruction_activation_time" => $instruction_activation_time
        ];
        $body = json_encode($body);

        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/pos/control-configuration';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function activatePosLink (
        $token,
        $pos_serial_number_to_link = null,
        $pos_link_label = null,
        $pdv_number = null,
        $cashier_number = null,
        $pos_reference_id_to_link = null
    )
    {
        $body = [];

        if (!empty($pos_link_label)) {
            $body["pos_link_label"] = $pos_link_label;
        }

        if (!empty($pdv_number)) {
            $body["pdv_number"] = $pdv_number;
        }

        if (!empty($cashier_number)) {
            $body["cashier_number"] = $cashier_number;
        }

        if (!empty($pos_serial_number_to_link)) {
            $body["pos_serial_number_to_link"] = $pos_serial_number_to_link;
        }

        if (!empty($pos_reference_id_to_link)) {
            $body["pos_reference_id_to_link"] = $pos_reference_id_to_link;
        }

        $body = json_encode($body);

        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/pos/activate-pos-link';
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

    public static function deactivatePosLink (
        $token,
        $pos_reference_id = null
    )
    {
        $curl = curl_init();
        $url = env('STONE_CONNECT_URL') . '/pos/deactivate-pos-link/' . $pos_reference_id;
        $auth = "Authorization: Bearer {$token}";
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	     
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Stone Connect!', 1);
        }
        return json_decode($response, true);
    }

}
