<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class InscricaoEstadual implements Rule
{

    private $uf = null;
    private $ie = null;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($uf)
    {
        $this->uf = strtoupper($uf);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (strtoupper($value) == "ISENTO" or empty($value)) {
            return true;
        }
        $this->ie = $value;

        switch ($this->uf) {
            case "AC":
                return $this->validateAC();
                break;
            case "AL":
                return $this->validateAL();
                break;
            case "AP":
                return $this->validateAP();
                break;
            case "AM":
                return $this->validateAM();
                break;
            case "BA":
                return $this->validateBA();
                break;
            case "CE":
                return $this->validateCE();
                break;
            case "DF":
                return $this->validateDF();
                break;
            case "ES":
                return $this->validateES();
                break;
            case "GO":
                return $this->validateGO();
                break;
            case "MA":
                return $this->validateMA();
                break;
            case "MT":
                return $this->validateMT();
                break;
            case "MS":
                return $this->validateMS();
                break;
            case "MG":
                return $this->validateMG();
                break;
            case "PA":
                return $this->validatePA();
                break;
            case "PB":
                return $this->validatePB();
                break;
            case "PR":
                return $this->validatePR();
                break;
            case "PE":
                return $this->validatePE();
                break;
            case "PI":
                return $this->validatePI();
                break;
            case "RJ":
                return $this->validateRJ();
                break;
            case "RN":
                return $this->validateRN();
                break;
            case "RS":
                return $this->validateRS();
                break;
            case "RO":
                return $this->validateRO();
                break;
            case "RR":
                return $this->validateRR();
                break;
            case "SC":
                return $this->validateSC();
                break;
            case "SP":
                return $this->validateSP();
                break;
            case "SE":
                return $this->validateSE();
                break;
            case "TO":
                return $this->validateTO();
                break;
        }
    }

    public static function padPelaUf($uf, $ie)
    {
        $ie = preg_replace("/[^[:alnum:]]/u", '', $ie);;
        switch ($uf) {
            case 'AC':
                return str_pad($ie, 13, '0', STR_PAD_LEFT);

            case 'AL':
            case 'AM':
            case 'AP':
            case 'CE':
            case 'ES':
            case 'GO':
            case 'MA':
            case 'MS':
            case 'PA':
            case 'PB':
            case 'PI':
            case 'RR':
            case 'SC':
            case 'SE':
                return str_pad($ie, 9, '0', STR_PAD_LEFT);

            case 'BA':
                if (strlen($ie) <= 8) {
                    return str_pad($ie, 8, '0', STR_PAD_LEFT);
                }
                return str_pad($ie, 9, '0', STR_PAD_LEFT);

            case 'DF':
            case 'MG':
                return str_pad($ie, 13, '0', STR_PAD_LEFT);

            case 'MT':
                if (strlen($ie) <= 9) {
                    return str_pad($ie, 9, '0', STR_PAD_LEFT);
                }
                return str_pad($ie, 11, '0', STR_PAD_LEFT);

            case 'PE':
                if (strlen($ie) <= 9) {
                    return str_pad($ie, 9, '0', STR_PAD_LEFT);
                }
                return str_pad($ie, 14, '0', STR_PAD_LEFT);

            case 'PR':
                return str_pad($ie, 10, '0', STR_PAD_LEFT);

            case 'RJ':
                return str_pad($ie, 8, '0', STR_PAD_LEFT);

            case 'RN':
                if (strlen($ie) <= 9) {
                    return str_pad($ie, 9, '0', STR_PAD_LEFT);
                }
                return str_pad($ie, 10, '0', STR_PAD_LEFT);

            case 'RO':
                if (strlen($ie) <= 9) {
                    return str_pad($ie, 9, '0', STR_PAD_LEFT);
                }
                return str_pad($ie, 14, '0', STR_PAD_LEFT);

            case 'RS':
                return str_pad($ie, 10, '0', STR_PAD_LEFT);

            case 'SP':
                if (strlen($ie) <= 12) {
                    return str_pad($ie, 12, '0', STR_PAD_LEFT);
                }
                return str_pad($ie, 13, '0', STR_PAD_LEFT);
                
            case 'TO':
                if (strlen($ie) <= 9) {
                    return str_pad($ie, 9, '0', STR_PAD_LEFT);
                }
                return str_pad($ie, 11, '0', STR_PAD_LEFT);
        }
    }


    private function validateAC()
    {
        /*
		* VERIFICAÇÃO 1
		* onze dígitos mais dois dígitos verificadores
		*/
        if (strlen($this->ie) != 13) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $number_01 = $caracteres[0];
        $number_02 = $caracteres[1];
        $dv_01 = $caracteres[11];
        $dv_02 = $caracteres[12];

        /*
		* VERIFICAÇÃO 2
		* os dois primeiros dígitos são sempre 01 (número do estado)
		*/
        if ($number_01 != 0 || $number_02 != 1) {
            return false;
        }

        $remontagem_01 = $caracteres[0] . $caracteres[1] . $caracteres[2];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 4; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $remontagem_02 = $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9] . $caracteres[10];
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_01_obtido = 11 - $resto;
        if ($dv_01_obtido == 10 || $dv_01_obtido == 11) {
            $dv_01_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 3
		* dígitos devem coincidir
		*/
        if ($dv_01 != $dv_01_obtido) {
            return false;
        }

        $remontagem_03 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3];
        $caracteresRemontagem_03 = str_split($remontagem_03);
        $i = 0;
        $soma = 0;
        for ($j = 5; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_03[$i];
            $i++;
        }
        $remontagem_04 = $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9] . $caracteres[10] . $dv_01_obtido;
        $caracteresRemontagem_04 = str_split($remontagem_04);
        $i = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_04[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_02_obtido = 11 - $resto;
        if ($dv_02_obtido == 10 || $dv_02_obtido == 11) {
            $dv_02_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 4
		* dígitos devem coincidir
		*/
        if ($dv_02 != $dv_02_obtido) {
            return false;
        }

        return true;
    }

    private function validateAL()
    {
        /*
		* VERIFICAÇÃO 1
		* nove dígitos
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $number_01 = $caracteres[0];
        $number_02 = $caracteres[1];
        $tipoEmpresa = $caracteres[2];
        $dv = $caracteres[8];

        /*
		* VERIFICAÇÃO 2
		* os dois primeiros dígitos são o código do estado: 24
		*/
        if ($number_01 != 2 || $number_02 != 4) {
            return false;
        }

        /*
		* VERIFICAÇÃO 3
		* 3o. dígito refere-se ao tipo de empresa (0-Normal, 3-Produtor Rural, 5-Substituta, 7- Micro-Empresa Ambulante, 8-Micro-Empresa)
		*/
        // Comentado porque a IE 242009280 do fornecedor Alaplast nao estava passando
        // 3o digito e 2	
        //if($tipoEmpresa!=0 && $tipoEmpresa!=3 && $tipoEmpresa!=5 && $tipoEmpresa!=7 && $tipoEmpresa!=8){		
        //	return false;
        //}

        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        //$dv_obtido = ($soma*10)-(int)(($soma*10)/11)*11;
        $resto = $soma % 11;
        $dv_obtido = 11 - $resto;
        if ($dv_obtido == 10) {
            $dv_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 4
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateAP()
    {
        /*
		* VERIFICAÇÃO 1
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $number_01 = $caracteres[0];
        $number_02 = $caracteres[1];
        $dv = $caracteres[8];

        /*
		* VERIFICAÇÃO 2
		* código do estado: 03
		*/
        if ($number_01 != 0 || $number_02 != 3) {
            return false;
        }

        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];

        if ($remontagem >= "03000001" && $remontagem <= "03017000") {
            $p = 5;
            $d = 0;
        } else if ($remontagem >= "03017001" && $remontagem <= "03019022") {
            $p = 9;
            $d = 1;
        } else if ($remontagem >= "03019023") {
            $p = 0;
            $d = 0;
        }

        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $soma += $p;
        $resto = $soma % 11;
        $dv_obtido = 11 - $resto;
        if ($dv_obtido == 10) {
            $dv_obtido = 0;
        } else if ($dv_obtido == 11) {
            $dv_obtido = $d;
        }

        /*
		* VERIFICAÇÃO 3
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateAM()
    {
        /*
		* VERIFICAÇÃO 1
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv = $caracteres[8];
        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        if ($soma < 11) {
            $dv_obtido = 11 - $soma;
        } else {
            $resto = $soma % 11;
            if ($resto <= 1) {
                $dv_obtido = 0;
            } else {
                $dv_obtido = 11 - $resto;
            }
        }

        /*
		* VERIFICAÇÃO 3
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateBA()
    {
        /*
		* VERIFICAÇÃO 1:
		* Bahia pode ter 2 tipos de ie, com 8 e com 9 dígitos
		*/
        if (strlen($this->ie) != 8 && strlen($this->ie) != 9) {
            return false;
        }
        return strlen($this->ie) == 8 ? $this->validateBA_8D() : $this->validateBA_9D();
    }

    private function validateBA_8D()
    {
        $caracteres = str_split($this->ie);
        if ($caracteres[0] == 0 || $caracteres[0] == 1 || $caracteres[0] == 2 || $caracteres[0] == 3 || $caracteres[0] == 4 || $caracteres[0] == 5 || $caracteres[0] == 8) {
            //Para Inscrições cujo primeiro dígito da I.E. é 0,1,2,3,4,5,8 cálculo pelo módulo 10:
            return $this->validateBA_8D_Modulo(10, $caracteres);
        } else {
            //Para Inscrições cujo primeiro dígito da I.E. é 6,7,9 cálculo pelo módulo 11:
            return $this->validateBA_8D_Modulo(11, $caracteres);
        }
    }

    private function validateBA_9D()
    {
        $caracteres = str_split($this->ie);
        if ($caracteres[1] == 0 || $caracteres[1] == 1 || $caracteres[1] == 2 || $caracteres[1] == 3 || $caracteres[1] == 4 || $caracteres[1] == 5 || $caracteres[1] == 8) {
            //Para Inscrições cujo primeiro dígito da I.E. é 0,1,2,3,4,5,8 cálculo pelo módulo 10:
            return $this->validateBA_9D_Modulo(10, $caracteres);
        } else {
            //Para Inscrições cujo primeiro dígito da I.E. é 6,7,9 cálculo pelo módulo 11:
            return $this->validateBA_9D_Modulo(11, $caracteres);
        }
        return true;
    }

    private function validateBA_8D_Modulo($modulo, $caracteres)
    {
        $dv_01 = $caracteres[6];
        $dv_02 = $caracteres[7];
        $remontagem_01 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 7; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $resto = $soma % $modulo;
        if ($resto == 0) {
            $dv_02_obtido = 0;
        } else {
            $dv_02_obtido = $modulo - $resto;
        }

        /*
		* VERIFICAÇÃO 01:
		*/
        if ($dv_02 != $dv_02_obtido) {
            return false;
        }

        $remontagem_02 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $dv_02_obtido;
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        $soma = 0;
        for ($j = 8; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $resto = $soma % $modulo;
        if ($resto == 0) {
            $dv_01_obtido = 0;
        } else {
            $dv_01_obtido = $modulo - $resto;
        }

        /*
		* VERIFICAÇÃO 02:
		*/
        if ($dv_01 != $dv_01_obtido) {
            return false;
        }

        return true;
    }

    private function validateBA_9D_Modulo($modulo, $caracteres)
    {
        $dv_01 = $caracteres[7];
        $dv_02 = $caracteres[8];
        $remontagem_01 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 8; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $resto = $soma % $modulo;
        if ($resto == 0) {
            $dv_02_obtido = 0;
        } else {
            $dv_02_obtido = $modulo - $resto;
        }

        /*
		* VERIFICAÇÃO 01:
		*/
        if ($dv_02 != $dv_02_obtido) {
            return false;
        }

        $remontagem_02 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $dv_02_obtido;
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $resto = $soma % $modulo;
        if ($resto == 0) {
            $dv_01_obtido = 0;
        } else {
            $dv_01_obtido = $modulo - $resto;
        }

        /*
		* VERIFICAÇÃO 02:
		*/
        if ($dv_01 != $dv_01_obtido) {
            return false;
        }

        return true;
    }

    private function validateCE()
    {
        /*
		* VERIFICAÇÃO 1
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv = $caracteres[8];
        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }

        //echo "<script>alert('".$soma."');</script >";

        $resto = $soma % 11;

        //echo "<script>alert('".$resto."');</s cript>";

        $dv_obtido = 11 - $resto;
        if ($dv_obtido == 10 || $dv_obtido == 11) {
            $dv_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateDF()
    {
        /*
		* VERIFICAÇÃO 1
		*/
        if (strlen($this->ie) != 13) {
            return false;
        }

        $caracteres = str_split($this->ie);

        /*
		* VERIFICAÇÃO 2
		* campos fixos (07)
		*/
        if ($caracteres[0] != 0 && $caracteres[1] != 7) {
            return false;
        }

        $dv_01 = $caracteres[11];
        $remontagem_01 = $caracteres[0] . $caracteres[1] . $caracteres[2];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 4; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $remontagem_02 = $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9] . $caracteres[10];
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_01_obtido = 11 - $resto;
        if ($dv_01_obtido == 10 || $dv_01_obtido == 11) {
            $dv_01_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv_01 != $dv_01_obtido) {
            return false;
        }

        $dv_02 = $caracteres[12];
        $remontagem_03 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3];
        $caracteresRemontagem_03 = str_split($remontagem_03);
        $i = 0;
        $soma = 0;
        for ($j = 5; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_03[$i];
            $i++;
        }
        $remontagem_04 = $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9] . $caracteres[10] . $dv_01_obtido;
        $caracteresRemontagem_04 = str_split($remontagem_04);
        $i = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_04[$i];
            $i++;
        }

        $resto = $soma % 11;
        $dv_02_obtido = 11 - $resto;
        if ($dv_02_obtido == 10 || $dv_02_obtido == 11) {
            $dv_02_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 3
		*/
        if ($dv_02 != $dv_02_obtido) {
            return false;
        }

        return true;
    }

    private function validateES()
    {
        /*
		* VERIFICAÇÃO 1
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }
        $caracteres = str_split($this->ie);
        $dv = $caracteres[8];
        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $resto = $soma % 11;
        if ($resto < 2) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateGO()
    {
        /*
		* VERIFICAÇÃO 1:
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $ab = $caracteres[0] . $caracteres[1];

        /*
		* VERIFICAÇÃO 2:
		* dois primeiros dígitos devem ser 10, 11 ou 15
		*/
        if ($ab != 10 && $ab != 11 && $ab != 15) {
            return false;
        }

        $dv = $caracteres[8];
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteres[$i];
            $i++;
        }
        $resto = $soma % 11;

        /*
		* VERIFICAÇÃO 3:
		*/
        if ($this->ie == 11094402 && $dv != 0 && $dv != 1) {
            return false;
        }

        /*
		* VERIFICAÇÃO 4:
		*/
        if ($resto == 0 && $dv != 0) {
            return false;
        }

        /*
		* VERIFICAÇÃO 5:
		*/
        if ($resto == 1 && $this->ie >= 10103105 && $this->ie <= 10119997 && $dv != 1) {
            return false;
        }

        /*
		* VERIFICAÇÃO 6:
		*/
        if ($resto == 1 && $this->ie < 10103105 && $this->ie > 10119997 && $dv != 0) {
            return false;
        }

        /*
		* VERIFICAÇÃO 7:
		*/
        if ($resto != 1 && $resto != 0 && $dv != (11 - $resto)) {
            return false;
        }

        return true;
    }

    private function validateMA()
    {
        /*
		* VERIFICAÇÃO 1
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $number_01 = $caracteres[0];
        $number_02 = $caracteres[1];

        /*
		* VERIFICAÇÃO 1
		* número fixo do estado: 12
		*/
        if ($number_01 != 1 || $number_02 != 2) {
            return false;
        }

        $dv = $caracteres[8];
        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $resto = $soma % 11;
        if ($resto == 0 || $resto == 1) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateMT()
    {
        /*
		* VERIFICAÇÃO 1
		*/

        //echo "<h1>$this->ie</h1>";

        if (strlen($this->ie) != 11 && strlen($this->ie) != 9) {
            return false;
        }

        if (strlen($this->ie) == 11) {
            return $this->validateMT_11D();
        }

        if (strlen($this->ie) == 9) {
            return $this->validateMT_9D();
        }
    }

    private function validateMT_11D()
    {
        $caracteres = str_split($this->ie);
        $dv = $caracteres[10];

        $remontagem_01 = $caracteres[0] . $caracteres[1];
        $caracteresRemontagem_01 = str_split($remontagem_01);

        $i = 0;
        $soma = 0;
        for ($j = 3; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $remontagem_02 = $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9];
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }

        $resto = $soma % 11;

        if ($resto < 2) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = 11 - $resto;
        }

        if ($dv_obtido != $dv) {
            return false;
        }

        return true;
    }

    private function validateMT_9D()
    {
        $caracteres = str_split($this->ie);
        $dv = $caracteres[8];

        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }

        $resto = $soma % 11;

        //echo "<pre>"; print_r($resto); echo "</pre>";

        if ($resto < 2) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = 11 - $resto;
        }

        if ($dv_obtido != $dv) {
            return false;
        }

        return true;
    }

    private function validateMS()
    {
        /*
		* VERIFICAÇÃO 1
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $number_01 = $caracteres[0];
        $number_02 = $caracteres[1];
        /*
		* VERIFICAÇÃO 1
		* número fixo do estado: 28
		*/
        if ($number_01 != 2 || $number_02 != 8) {
            return false;
        }

        $dv = $caracteres[8];
        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $resto = $soma % 11;
        if ($resto == 0) {
            $dv_obtido = 0;
        } else if ($resto > 0) {
            $t = 11 - $resto;
            if ($t > 9) {
                $dv_obtido = 0;
            } else {
                $dv_obtido = $t;
            }
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateMG()
    {
        /*
		* VERIFICAÇÃO 1
		*/
        if (strlen($this->ie) != 13) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv_01 = $caracteres[11];
        $dv_02 = $caracteres[12];
        $remontagem_01 = $caracteres[0] . $caracteres[1] . $caracteres[2] . "0" . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9] . $caracteres[10];
        $caracteresRemontagem_01 = str_split($remontagem_01);

        //echo "<pre>"; print_r($caracteresRemontagem_01); echo "</pre>";
        $concat = "";
        $i = 1;
        for ($j = 0; $j < count($caracteresRemontagem_01); $j++) {
            $concat .= $i * $caracteresRemontagem_01[$j];
            if ($i == 1) {
                $i = 2;
            } else {
                $i = 1;
            }
        }
        $caracteres_concat = str_split($concat);
        $soma = 0;
        for ($i = 0; $i < strlen($concat); $i++) {
            $soma += $caracteres_concat[$i];
        }

        //echo "<h1>$soma</h1>";

        $caracteresSoma = str_split($soma);
        $dezena = $caracteresSoma[0] + 1;
        $dezena .= "0";
        $dv_01_obtido = $dezena - $soma;

        //correção bug
        if ($dv_01_obtido == 10) {
            $dv_01_obtido = 0;
        }

        //echo "<h1>$dezena</h1>";

        //echo "<h1>$dv_01 - $dv_01_obtido</h1>";

        /*
		* VERIFICAÇÃO 2:
		*/
        if ($dv_01 != $dv_01_obtido) {
            return false;
        }

        $remontagem_02 = $caracteres[0] . $caracteres[1];
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        $soma = 0;
        for ($j = 3; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $remontagem_03 = $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9] . $caracteres[10] . $dv_01_obtido;
        $caracteresRemontagem_03 = str_split($remontagem_03);
        $i = 0;
        for ($j = 11; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_03[$i];
            $i++;
        }
        $resto = $soma % 11;
        if ($resto == 1 || $resto == 0) {
            $dv_02_obtido = 0;
        } else {
            $dv_02_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 3:
		*/
        if ($dv_02 != $dv_02_obtido) {
            return false;
        }

        return true;
    }

    private function validatePA()
    {
        /*
		* VERIFICAÇÃO 1
		* oito dígitos mais um dígito verificador
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $number_01 = $caracteres[0];
        $number_02 = $caracteres[1];

        /*
		* VERIFICAÇÃO 2
		* número fixo do estado: 15
		*/
        if ($number_01 != 1 || $number_02 != 5) {
            return false;
        }

        $dv = $caracteres[8];
        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $resto = $soma % 11;
        if ($resto == 0 || $resto == 1) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validatePB()
    {
        /*
		* VERIFICAÇÃO 1:
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv = $caracteres[8];
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteres[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_obtido = 11 - $resto;
        if ($dv_obtido == 10 || $dv_obtido == 11) {
            $dv_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 2:
		* dígitos devem coincidir
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validatePR()
    {
        /*
		* VERIFICAÇÃO 1:
		*/
        if (strlen($this->ie) != 10) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv_01 = $caracteres[8];
        $dv_02 = $caracteres[9];
        $remontagem_01 = $caracteres[0] . $caracteres[1];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 3; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $remontagem_02 = $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        for ($j = 7; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_01_obtido = 11 - $resto;

        if ($dv_01_obtido == 10 || $dv_01_obtido == 11) {
            $dv_01_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 2:
		*/
        if ($dv_01 != $dv_01_obtido) {
            return false;
        }

        $remontagem_03 = $caracteres[0] . $caracteres[1] . $caracteres[2];
        $caracteresRemontagem_03 = str_split($remontagem_03);
        $i = 0;
        $soma = 0;
        for ($j = 4; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_03[$i];
            $i++;
        }
        $remontagem_04 = $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $dv_01_obtido;
        $caracteresRemontagem_04 = str_split($remontagem_04);
        $i = 0;
        for ($j = 7; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_04[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_02_obtido = 11 - $resto;

        if ($dv_02_obtido == 10 || $dv_02_obtido == 11) {
            $dv_02_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 3:
		*/
        if ($dv_02 != $dv_02_obtido) {
            return false;
        }

        return true;
    }

    private function validatePE()
    {
        /*
		* VERIFICAÇÃO 1:
		* inscrição estadual do e-Fisco: 9 dígitos
		* inscrição antiga: 14 dígitos
		*/
        if (strlen($this->ie) == 9) {
            return $this->validatePE_9D();
        } else if (strlen($this->ie) == 14) {
            return $this->validatePE_14D();
        } else {
            return false;
        }
    }

    private function validatePE_9D()
    {
        $caracteres = str_split($this->ie);
        $dv_01 = $caracteres[7];
        $dv_02 = $caracteres[8];

        $remontagem_01 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 8; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $resto = $soma % 11;
        if ($resto == 0 || $resto == 1) {
            $dv_01_obtido = 0;
        } else {
            $dv_01_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 1
		*/
        if ($dv_01 != $dv_01_obtido) {
            return false;
        }

        $remontagem_02 = $remontagem_01 . $dv_01_obtido;
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $resto = $soma % 11;
        if ($resto == 0 || $resto == 1) {
            $dv_02_obtido = 0;
        } else {
            $dv_02_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv_02 != $dv_02_obtido) {
            return false;
        }

        return true;
    }

    private function validatePE_14D()
    {
        $caracteres = str_split($this->ie);
        $dv = $caracteres[13];

        $remontagem_01 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 5; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $remontagem_02 = $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9] . $caracteres[10] . $caracteres[11] . $caracteres[12];
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_obtido = 11 - $resto;
        if ($dv_obtido > 9) {
            $dv_obtido = $dv_obtido - 10;
        }

        /*
		* VERIFICAÇÃO 1
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validatePI()
    {
        /*
		* VERIFICAÇÃO 1:
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv = $caracteres[8];

        $remontagem_01 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_obtido = 11 - $resto;
        if ($dv_obtido == 10 || $dv_obtido == 11) {
            $dv_obtido = 0;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateRJ()
    {
        /*
		* VERIFICAÇÃO 1:
		*/

        if (strlen($this->ie) != 8) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv = $caracteres[7];

        $remontagem = substr($this->ie, 0, -1);
        $caracteresRemontagem = str_split($remontagem);

        $i = 1;
        $soma = ($caracteres[0] * 2);

        for ($j = 7; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }

        $resto = $soma % 11;
        if ($resto <= 1) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateRN()
    {
        /*
		* VERIFICAÇÃO 1:
		*/
        if (strlen($this->ie) == 9) {
            return $this->validateRN_9D();
        } else if (strlen($this->ie) == 10) {
            return $this->validateRN_10D();
        } else return false;
    }

    private function validateRN_9D()
    {
        $caracteres = str_split($this->ie);
        $number_01 = $caracteres[0];
        $number_02 = $caracteres[1];

        /*
		* VERIFICAÇÃO 1:
		* número do estado: 20
		*/
        if ($number_01 != 2 || $number_02 != 0) {
            return false;
        }

        $dv = $caracteres[8];
        $remontagem = substr($this->ie, 0, -1);
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $soma = $soma * 10;
        $resto = $soma % 11;
        if ($resto == 10) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = $resto;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateRN_10D()
    {
        $caracteres = str_split($this->ie);
        $number_01 = $caracteres[0];
        $number_02 = $caracteres[1];

        /*
		* VERIFICAÇÃO 1:
		* número do estado: 20
		*/
        if ($number_01 != 2 || $number_02 != 0) {
            return false;
        }

        $dv = $caracteres[9];
        $remontagem = substr($this->ie, 0, -1);
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 10; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $soma = $soma * 10;
        $resto = $soma % 11;
        if ($resto == 10) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = $resto;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateRS()
    {
        /*
		* VERIFICACAO 1
		*/
        if (strlen($this->ie) != 10) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv = $caracteres[9];
        $remontagem = substr($this->ie, 0, -1);
        $caracteresRemontagem = str_split($remontagem);
        $i = 1;
        $soma = ($caracteres[0] * 2);
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        //print_r($caracteresRemontagem);
        //echo "<script>alert('".$soma."')</scr ipt>";

        $resto = $soma % 11;
        $dv_obtido = 11 - $resto;
        if ($dv_obtido == 10 || $dv_obtido == 11) {
            $dv_obtido = 0;
        }

        //echo "<script>alert('".$dv_obtido."')</script		>";

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateRO()
    {
        /*
		* VERIFICACAO 1
		*/
        if (strlen($this->ie) == 9) {
            return $this->validateRO_9D();
        } else if (strlen($this->ie) == 14) {
            return $this->validateRO_14D();
        }
    }

    private function validateRO_9D()
    {
        $caracteres = str_split($this->ie);
        $dv = $caracteres[8];
        $remontagem = $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 6; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_obtido = 11 - $resto;
        if ($dv_obtido == 10 || $dv_obtido == 11) {
            $dv_obtido = $dv_obtido - 10;;
        }

        /*
		* VERIFICAÇÃO 1
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateRO_14D()
    {
        $caracteres = str_split($this->ie);
        $dv = $caracteres[13];

        $remontagem_01 = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 6; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $remontagem_02 = $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9] . $caracteres[10] . $caracteres[11] . $caracteres[12];
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $resto = $soma % 11;
        $dv_obtido = 11 - $resto;
        if ($dv_obtido == 10 || $dv_obtido == 11) {
            $dv_obtido = $dv_obtido - 10;;
        }

        /*
		* VERIFICAÇÃO 1
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateRR()
    {
        /*
		* VERIFICACAO 1
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $remontagem = substr($this->ie, 0, -1);
        $dv = substr($this->ie, -1);
        $caracteresRemontagem = str_split($remontagem);
        $soma = 0;
        for ($i = 0; $i < count($caracteresRemontagem); $i++) {
            $soma += ($i + 1) * $caracteresRemontagem[$i];
        }
        $dv_obtido = $soma % 11;

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateSC()
    {
        /*
		* VERIFICACAO 1
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $remontagem = substr($this->ie, 0, -1);
        $dv = substr($this->ie, -1);
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }

        $resto = $soma % 11;

        //echo "<script>alert('".$resto."');</scrip t>";

        if ($resto == 0 || $resto == 1) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateSP()
    {
        /*
		* São Paulo possui dois tipos de IE: Industriais e Comerciantes no formato 110.042.490.114
		* e Produtor Rural no formato P-01100424.3/002, então verificamos o primeiro dígito para 
		* determinar o tipo de validação a ser feita.
		*/
        $caracteres = str_split($this->ie);
        return $caracteres[0] == "P" ? $this->validateSpRural() : $this->validateSpIndustrial();
    }

    private function validateSpIndustrial()
    {
        /*
		* VERIFICAÇÃO 1:
		* doze dígitos
		*/
        if (strlen($this->ie) != 12) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv_01 = $caracteres[8];
        $dv_02 = $caracteres[11];
        $remontagem_01 = $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        $soma = 0;
        for ($j = 3; $j <= 8; $j++) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $soma += $caracteres[0] + ($caracteres[7] * 10);
        $resto = "" . $soma % 11;
        $dv_01_obtido = $resto[strlen($resto) - 1];

        /*
		* VERIFICAÇÃO 2:
		*/
        if ($dv_01 != $dv_01_obtido) {
            return false;
        }

        $remontagem_02 = $caracteres[0] . $caracteres[1];
        $caracteresRemontagem_02 = str_split($remontagem_02);
        $i = 0;
        $soma = 0;
        for ($j = 3; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_02[$i];
            $i++;
        }
        $remontagem_03 = $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9] . $caracteres[10];
        $caracteresRemontagem_03 = str_split($remontagem_03);
        $i = 0;
        for ($j = 10; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem_03[$i];
            $i++;
        }
        $resto = "" . $soma % 11;
        $dv_02_obtido = $resto[strlen($resto) - 1];

        /*
		* VERIFICAÇÃO 3:
		*/
        if ($dv_02 != $dv_02_obtido) {
            return false;
        }

        return true;
    }

    private function validateSpRural()
    {
        /*
		* VERIFICAÇÃO 1:
		*/
        if (strlen($this->ie) != 13) {
            return false;
        }

        $caracteres = str_split($this->ie);
        $dv = $caracteres[9];
        $remontagem_01 = $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem_01 = str_split($remontagem_01);
        $i = 0;
        for ($j = 3; $j <= 10; $j++) {
            $soma += $j * $caracteresRemontagem_01[$i];
            $i++;
        }
        $soma += $caracteres[1] + ($caracteres[8] * 10);
        $resto = "" . $soma % 11;
        $dv_obtido = $resto[strlen($resto) - 1];

        /*
		* VERIFICAÇÃO 2:
		* dígitos devem coincidir
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateSE()
    {
        /*
		* VERIFICACAO 1
		*/
        if (strlen($this->ie) != 9) {
            return false;
        }

        $remontagem = substr($this->ie, 0, -1);
        $dv = substr($this->ie, -1);
        $caracteresRemontagem = str_split($remontagem);
        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $resto = $soma % 11;
        if ($resto == 10 || $resto == 11) {
            $dv_obtido = 0;
        } else {
            $dv_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 2
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }

    private function validateTO()
    {
        /*
		* No Tocantins pode-se ou não informar os dígitos que determinam o tipo de empresa
		*/
        if (strlen($this->ie) == 11) {
            return $this->validateTO_11D();
        } else if (strlen($this->ie) == 9) {
            //echo "<script>alert('".strlen($this->ie)."');< /script>";
            return $this->validateTO_9D();
        } else return false;
    }

    private function validateTO_11D()
    {
        $caracteres = str_split($this->ie);
        $digitos_34 = $caracteres[2] . $caracteres[3];
        $dv = $caracteres[10];
        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7] . $caracteres[8] . $caracteres[9];
        $caracteresRemontagem = str_split($remontagem);

        /*
		* VERIFICAÇÃO 1:
		* o terceiro e quarto dígitos não entram na conta, mas só podem assumir os seguintes valores:
		* 01 = Produtor Rural (não possui CGC)
		* 02 = Industria e Comércio
		* 03 = Empresas Rudimentares
		* 99 = Empresas do Cadastro Antigo (SUSPENSAS) 		
		*/
        if ($digitos_34 != "01" && $digitos_34 != "02" && $digitos_34 != "03" && $digitos_34 != "99") {
            return false;
        }

        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $resto = $soma % 11;

        /*
		* VERIFICAÇÃO 2:
		* se resto menor que dois, dígito == 0
		*/
        if ($resto < 2 && $dv != 0) {
            return false;
        }

        /*
		* VERIFICAÇÃO 3:
		* se resto >= 2 que dois, dígito == 11-resto
		*/
        if ($resto >= 2 && ($dv != 11 - $resto)) {
            return false;
        }

        return true;
    }

    private function validateTO_9D()
    {
        $caracteres = str_split($this->ie);
        $dv = $caracteres[8];
        $remontagem = $caracteres[0] . $caracteres[1] . $caracteres[2] . $caracteres[3] . $caracteres[4] . $caracteres[5] . $caracteres[6] . $caracteres[7];
        $caracteresRemontagem = str_split($remontagem);

        $i = 0;
        $soma = 0;
        for ($j = 9; $j > 1; $j--) {
            $soma += $j * $caracteresRemontagem[$i];
            $i++;
        }
        $resto = $soma % 11;
        if ($resto < 2) {
            $dv_obtido = 0;;
        } else {
            $dv_obtido = 11 - $resto;
        }

        /*
		* VERIFICAÇÃO 1:
		*/
        if ($dv != $dv_obtido) {
            return false;
        }

        return true;
    }



    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Inscrição Estadual Inválida!';
    }
}
