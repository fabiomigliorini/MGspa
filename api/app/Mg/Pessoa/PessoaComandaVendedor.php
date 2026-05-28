<?php

namespace Mg\Pessoa;

use Mg\Titulo\TituloBoleto;

//use JasperPHP\ado\TTransaction;
//use JasperPHP\ado\TLoggerHTML;

class PessoaComandaVendedor
{
    /* Variavel que armazenara os dados do boleto
    / @var array();
    */
    private $data = array();
    /*
    * método __set()
    * executado sempre que uma propriedade for atribuída.
    */
    public function __set($prop, $value)
    {
        // verifica se existe método set_<propriedade>
        if (method_exists($this, 'set_'.$prop))
        {
            // executa o método set_<propriedade>
            call_user_func(array($this, 'set_'.$prop), $value);
        }
        else
        {
            if ($value === NULL)
            {
                unset($this->data[$prop]);
            }
            else
            {
                // atribui o valor da propriedade
                $this->data[$prop] = $value;
            }
        }
    }
    /*
    * método __get()
    * executado sempre que uma propriedade for requerida
    */
    public function __get($prop)
    {
        // verifica se existe método get_<propriedade>
        if (method_exists($this, 'get_'.$prop))
        {
            // executa o método get_<propriedade>
            return call_user_func(array($this, 'get_'.$prop));
        }
        else
        {
            // retorna o valor da propriedade
            if (isset($this->data[$prop]))
            {
                return ($this->data[$prop]);
            }
        }
        return null;
    }

    public function __construct(Pessoa $pessoa)
    {
        $this->data['codpessoa'] = '#' . str_pad($pessoa->codpessoa, 8, '0', STR_PAD_LEFT);
        $this->data['fantasia'] = $pessoa->fantasia;
        $this->data['barras'] = "VDD" . str_pad($pessoa->codpessoa, 8, '0', STR_PAD_LEFT);
        $this->data['logo'] = 'data:image/jpg;base64,' . base64_encode(file_get_contents(public_path('MGPapelariaLogoPretoBranco.jpeg')));
    }

}
