<?php

namespace Mg\Titulo\BoletoBb;

use Mg\Titulo\TituloBoleto;

use OpenBoleto\Banco\BancoDoBrasil;
use OpenBoleto\Agente;
use JasperPHP\Report;
//use JasperPHP\ado\TTransaction;
//use JasperPHP\ado\TLoggerHTML;

class BoletoBbPdf
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
    }

    public function __construct(TituloBoleto $tituloBoleto)
    {

        // Cliente
        $pessoa = $tituloBoleto->Titulo->Pessoa;
        $endereco = $pessoa->enderecocobranca;
        if (!empty($pessoa->numerocobranca)) {
            $endereco .= ", {$pessoa->numerocobranca}";
        }
        if (!empty($pessoa->complementocobranca)) {
            $endereco .= " - {$pessoa->complementocobranca}";
        }
        $sacado = new Agente(
            $pessoa->pessoa,
            $pessoa->cnpj,
            $endereco,
            $pessoa->cepcobranca,
            $pessoa->CidadeCobranca->cidade,
            $pessoa->CidadeCobranca->Estado->sigla
        );

        // Filial
        $pessoa = $tituloBoleto->Titulo->Filial->Pessoa;
        $endereco = $pessoa->enderecocobranca;
        if (!empty($pessoa->numerocobranca)) {
            $endereco .= ", {$pessoa->numerocobranca}";
        }
        if (!empty($pessoa->complementocobranca)) {
            $endereco .= " - {$pessoa->complementocobranca}";
        }
        $cedente = new Agente(
            $pessoa->fantasia . ' - ' . $pessoa->pessoa,
            $pessoa->cnpj,
            $endereco,
            $pessoa->cepcobranca,
            $pessoa->CidadeCobranca->cidade,
            $pessoa->CidadeCobranca->Estado->sigla
        );
        $boleto = new BancoDoBrasil([
            'dataVencimento' => $tituloBoleto->vencimento,
            'valor' => $tituloBoleto->valoratual,
            'sequencial' => substr($tituloBoleto->nossonumero, -7), // Para gerar o nosso número
            'sacado' => $sacado,
            'cedente' => $cedente,
            'agencia' => $tituloBoleto->Portador->agencia, // Até 4 dígitos
            'agenciaDv' => $tituloBoleto->Portador->agenciadigito,
            'carteira' => $tituloBoleto->Portador->carteira,
            'conta' => $tituloBoleto->Portador->conta, // Até 8 dígitos
            'contaDv' => $tituloBoleto->Portador->contadigito,
            'convenio' => $tituloBoleto->Portador->convenio, // 4, 6 ou 7 dígitos
            'numeroDocumento' => $tituloBoleto->Titulo->numero, // 7 dígitos
            'codigoCliente' => 12345, // 5 dígitos

            // Parâmetros recomendáveis
            'descricaoDemonstrativo' => array( // Até 5
                'Sujeito à multa de 5%, mais juros de 5% ao mês após o vencimento.',
            ),

            'instrucoes' => [  // Até 8
                'Sujeito à multa de 5%, mais juros de 5% ao mês após o vencimento.',
            ],

            // Parâmetros opcionais
            'moeda' => BancoDoBrasil::MOEDA_REAL,
            'dataDocumento' => $tituloBoleto->Titulo->emissao,
            'dataProcessamento' => $tituloBoleto->dataregistro,
            'aceite' => 'S',
            'especieDoc' => 'DM',
        ]);
        $boleto->getOutput();
        $this->data = array_merge($this->data,$boleto->getData());
        $this->data['qrcode'] = $tituloBoleto->qrcodeemv;

    }

    /* método para interceptar  a requisição e adicionar o codigo html necessario para correta exibição do demostrativo    */
    public function get_demonstrativo()
    {
        return '<table><tr><td>'
            . implode('<br>', $this->data['demonstrativo'])
            . '</td></tr><table>';
    }

    /* método para interceptar  a requisição e adicionar o codigo html necessario para correta exibição das instrucoes    */
    public function get_instrucoes()
    {
        return '<table><tr><td>'
            . implode('<br>',$this->data['instrucoes'])
            . '</td></tr><table>';
    }

    /* este metodo esta aqui para manter compatibilidade do jxml criado para o meu sistema*/
    public function get_carteiras_nome()
    {
        return $this->data['carteira'];
    }

}
