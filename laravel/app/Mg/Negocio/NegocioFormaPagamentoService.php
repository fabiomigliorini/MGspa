<?php

namespace Mg\Negocio;

class NegocioFormaPagamentoService
{
    const BANDEIRAS = [
        1 => 'Visa',
        2 => 'Mastercard',
        3 => 'American Express',
        4 => 'Sorocred',
        5 => 'Diners Club',
        6 => 'Elo',
        7 => 'Hipercard',
        8 => 'Aura',
        9 => 'Cabal',
        99 => 'Outros'
    ];

    const TIPOS = [
        01 => 'Dinheiro',
        02 => 'Cheque',
        03 => 'Cartão de Crédito',
        04 => 'Cartão de Débito',
        05 => 'Crédito Loja',
        10 => 'Vale Alimentação',
        11 => 'Vale Refeição',
        12 => 'Vale Presente',
        13 => 'Vale Combustível',
        15 => 'Boleto Bancário',
        16 => 'Depósito Bancário',
        17 => 'Pagamento Instantâneo (PIX)',
        18 => 'Transferência bancária, Carteira Digital',
        19 => 'Programa de fidelidade, Cashback, Crédito Virtual',
        90 => 'Sem pagamento',
        99 => 'Outros'
    ];

    const CODFORMAPAGAMENTO_VALE = 1030;
    const CODFORMAPAGAMENTO_ENTREGA_AVISTA = 1099;

}
