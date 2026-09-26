<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Negocio\NegocioService;
use Mg\Negocio\NegocioVale;
use Mg\Negocio\NegocioValeProdutoBarra;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\NotaFiscal\NotaFiscalStatusService;
use Mg\NotaFiscal\NotaFiscalNegocioService;
use Mg\Titulo\BoletoBb\BoletoBbService;
use Mg\Titulo\TituloService;
use Illuminate\Support\Facades\Log;
use Mg\Rh\ProcessarVendaJob;

class PdvNegocioService
{

    public static function negocio($data, Pdv $pdv)
    {
        // Procura se já existe um negocio com o uuid na base
        $negocio = Negocio::firstOrNew(['uuid' => $data['uuid']]);

        // Verifica se o Status do Front e Backend conferem
        if ($negocio->exists && $negocio->codnegociostatus != $data['codnegociostatus']) {
            $back = NegocioService::CODNEGOCIOSTATUS_DESCRICAO[$negocio->codnegociostatus] ?? $negocio->codnegociostatus;
            $front = NegocioService::CODNEGOCIOSTATUS_DESCRICAO[$data['codnegociostatus']] ?? $data['codnegociostatus'];
            throw new Exception("Negocio consta como {$back} no Servidor, mas {$front} no PDV. Verifique com o suporte!");
        }

        // Se Fechado/Cancelado
        if (in_array($negocio->codnegociostatus, [NegocioService::STATUS_FECHADO, NegocioService::STATUS_CANCELADO])) {
            return static::negocioFechado($negocio, $data, $pdv);
        }

        // Se Aberto
        return static::negocioAberto($negocio, $data, $pdv);
    }

    // Verifica se o somatorio dos itens bate com o negocio
    //
    // O negocio tem DUAS colecoes que somam: os itens de mercadoria e os
    // vales compras (decisao 2 do plano). Cada lado tem o seu totalizador
    // bruto -- valorprodutos para a mercadoria, valorvales para a face dos
    // vales -- e o DESCONTO de cabecalho soma as duas fatias (decisao 20).
    // Frete, seguro e "outras" nao sao rateados para o vale e continuam
    // batendo so' contra a mercadoria.
    //
    // Negocio sem vale: as somas do vale dao zero e a conferencia fica
    // identica ao que sempre foi.
    public static function confereTotais(Negocio $negocio)
    {
        $sql = '
            select 
                sum(npb.valorprodutos) as valorprodutos,
                sum(npb.valordesconto) as valordesconto,
                sum(npb.valorfrete ) as valorfrete,
                sum(npb.valoroutras ) as valoroutras,
                sum(npb.valorseguro) as valorseguro,
                sum(npb.valorjuros) as valorjuros,
                sum(npb.valortotal) as valortotal
            from tblnegocioprodutobarra npb
            where npb.codnegocio = :codnegocio
            and npb.inativo is null
        ';
        $tot = DB::select($sql, [
            'codnegocio' => $negocio->codnegocio
        ])[0];

        // Frete, seguro e "outras" nao entram: eles nao sao rateados para o
        // vale (nao se cobra frete de vale compras), entao continuam sendo
        // 100% da mercadoria.
        $sqlVale = '
            select
                coalesce(sum(nv.valorvale), 0) as valorvales,
                coalesce(sum(nv.valordesconto), 0) as valordesconto,
                coalesce(sum(nv.valorjuros), 0) as valorjuros,
                coalesce(sum(nv.valortotal), 0) as valortotal
            from tblnegociovale nv
            where nv.codnegocio = :codnegocio
            and nv.inativo is null
        ';
        $totVale = DB::select($sqlVale, [
            'codnegocio' => $negocio->codnegocio
        ])[0];

        // Qual total divergiu fica no log: a mensagem que chega no PDV e'
        // sempre a mesma ("nao bate"), e sem isso descobrir qual dos sete
        // valores brigou custava reproduzir o negocio inteiro na mao.
        $divergencias = [];
        $confere = function ($campo, $cabecalho, $somado) use (&$divergencias) {
            if (!static::valoresBatem($cabecalho, $somado)) {
                $divergencias[$campo] = [
                    'cabecalho' => round(floatval($cabecalho), 2),
                    'itens' => round(floatval($somado), 2),
                ];
            }
        };

        $confere('valorprodutos', $negocio->valorprodutos, $tot->valorprodutos);
        $confere('valorvales', $negocio->valorvales, $totVale->valorvales);
        $confere('valordesconto', $negocio->valordesconto, floatval($tot->valordesconto) + floatval($totVale->valordesconto));
        $confere('valorfrete', $negocio->valorfrete, $tot->valorfrete);
        $confere('valoroutras', $negocio->valoroutras, $tot->valoroutras);
        $confere('valorseguro', $negocio->valorseguro, $tot->valorseguro);
        $confere('valortotal', $negocio->valortotal - $negocio->valorjuros, floatval($tot->valortotal) + floatval($totVale->valortotal));

        // O juros do parcelamento nasce no pagamento, no cabecalho, e e'
        // rateado entre os itens e os vales -- e' o item que a nota fiscal
        // le depois. Ele NAO entra no valortotal do item (por isso a linha
        // acima subtrai o do cabecalho); esta checagem so' garante que a
        // distribuicao nao se perdeu no caminho.
        //
        // Sem item e sem vale nao ha' onde ratear (existe negocio assim na
        // base: cancelado, so' com pagamento). Ai a checagem nao se aplica.
        $baseJuros = floatval($tot->valorprodutos) + floatval($totVale->valorvales);
        if ($baseJuros > 0) {
            $confere('valorjuros', $negocio->valorjuros, floatval($tot->valorjuros) + floatval($totVale->valorjuros));
        }

        if (count($divergencias) > 0) {
            Log::warning("Totais do negocio {$negocio->codnegocio} nao batem com os itens", $divergencias);
            return false;
        }

        return true;
    }

    // Dois valores de dinheiro conferem?
    //
    // Comparar float com == nao serve aqui. Todo valor deste negocio tem no
    // maximo 2 casas, mas os dois lados chegam como float e QUALQUER conta
    // entre eles vira ruido de ponto flutuante: 12.90 + 86.26 da
    // 99.16000000000001, que para o PHP e diferente de 99.16. Com duas
    // colecoes somando (mercadoria + vale) isso deixou de ser raro e virou o
    // "Total do Negocio nao bate" aleatorio.
    //
    // Meio centavo de folga mata o ruido e nao deixa passar divergencia de
    // verdade: o front arredonda tudo para 2 casas, entao erro real e de
    // um centavo para cima.
    public static function valoresBatem($esperado, $obtido)
    {
        return abs(floatval($esperado) - floatval($obtido)) < 0.005;
    }

    // Importa os vales do negocio vindos do PDV.
    //
    // Upsert por uuid, igual aos itens de mercadoria: o PDV offline pode
    // retransmitir o mesmo negocio varias vezes e nada pode duplicar. Vale
    // excluido no PDV chega com "inativo" carimbado -- nunca some da lista,
    // pela mesma razao.
    public static function importarVales(Negocio $negocio, $vales)
    {
        // Vale so existe onde ha financeiro: ele vira titulo de credito no
        // fechamento, e em natureza sem financeiro nunca viraria credito
        // nenhum -- seria dinheiro cobrado do cliente sem contrapartida.
        // Vale que esta sendo EXCLUIDO passa, senao um negocio que trocou de
        // natureza nao conseguiria mais se livrar do vale.
        $ativos = array_filter($vales, function ($vale) {
            return empty($vale['inativo']);
        });
        if (count($ativos) > 0 && !$negocio->NaturezaOperacao->financeiro) {
            throw new Exception('A Natureza de Operação deste negócio não gera financeiro: não é possível vender Vale Compras nela!', 1);
        }

        foreach ($vales as $vale) {
            $nv = NegocioVale::firstOrNew(['uuid' => $vale['uuid']]);
            if (!empty($nv->codnegocio) && $nv->codnegocio != $negocio->codnegocio) {
                throw new Exception("Tentando atualizar um vale de outro negocio {$nv->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            $nv->fill($vale);
            $nv->codnegocio = $negocio->codnegocio;
            $nv->save();

            foreach ($vale['itens'] ?? [] as $item) {
                $nvpb = NegocioValeProdutoBarra::firstOrNew(['uuid' => $item['uuid']]);
                if (!empty($nvpb->codnegociovale) && $nvpb->codnegociovale != $nv->codnegociovale) {
                    throw new Exception("Tentando atualizar um item de outro vale {$nvpb->codnegociovale}/{$nv->codnegociovale}!", 1);
                }
                $nvpb->fill($item);
                $nvpb->codnegociovale = $nv->codnegociovale;
                $nvpb->save();
            }
        }
    }

    public static function negocioAberto(Negocio $negocio, $data, Pdv $pdv)
    {

        // só considera o status do frontend caso seja novo
        if ($negocio->exists) {
            unset($data['codnegociostatus']);
        }

        // importa os dados do negocio
        $negocio->fill($data);
        $negocio->codpdv = $pdv->codpdv;
        $negocio->codfilial = $negocio->EstoqueLocal->codfilial;
        $negocio->codusuario = Auth::user()->codusuario;
        $negocio->save();

        // importa os itens
        foreach ($data['itens'] as $item) {
            $npb = NegocioProdutoBarra::firstOrNew(['uuid' => $item['uuid']]);
            if (!empty($npb->codnegocio) && $npb->codnegocio != $negocio->codnegocio) {
                throw new Exception("Tentando atualizar um item de outro negocio {$npb->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            $npb->fill($item);
            $npb->codnegocio = $negocio->codnegocio;
            $npb->save();
        }

        // importa os vales compras (bloco proprio, nao e' item)
        // Antes da conferencia de totais: o valorvales do negocio e' somado
        // a partir do que esta' gravado aqui.
        static::importarVales($negocio, $data['vales'] ?? []);

        if (!static::confereTotais($negocio)) {
            throw new Exception('Total do Negócio não bate com o Total dos Itens! Tente transmitir novamente para o servidor (Botão Roxo)!', 1);
        }

        // importa os pagamentos
        foreach ($data['pagamentos'] as $pagto) {
            // ignora pagamentos criados por integracao de algum sistema
            if ($pagto['integracao']) {
                continue;
            }
            // procura se pagamento já existe
            $nfp = NegocioFormaPagamento::firstOrNew(['uuid' => $pagto['uuid']]);
            if (!empty($nfp->codnegocio) && $nfp->codnegocio != $negocio->codnegocio) {
                throw new Exception("Tentando atualizar um pagamento de outro negocio {$nfp->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            // vincula pagamento
            $nfp->fill($pagto);
            $nfp->codnegocio = $negocio->codnegocio;
            $nfp->save();
        }

        // exclui pagamentos que nao vieram no post
        $uuids = array_column($data['pagamentos'], 'uuid');
        NegocioFormaPagamento::where('codnegocio', $negocio->codnegocio)->where('integracao', false)->whereNotIn('uuid', $uuids)->delete();

        return $negocio;
    }

    public static function negocioFechado(Negocio $negocio, $data, Pdv $pdv)
    {
        // Vale do sistema antigo convertido em negocio (vale_conversao.sql):
        // e' registro historico. O loop do fim reescreveria tipo e conta dos
        // titulos a prazo dele com os da natureza Venda.
        if ($negocio->NegocioValeS()->whereNotNull('codvalecompra')->exists()) {
            throw new Exception('Este negócio é um Vale Compras convertido do sistema antigo e não pode ser alterado!', 1);
        }
        if ($negocio->valortotal != $data['valortotal']) {
            throw new Exception("Não é permitido alterar os valores de um negocio Fechado ou Cancelado {$negocio->codnegocio} {$negocio->valortotal} != {$data['valortotal']}!", 1);
        }
        if ($negocio->NaturezaOperacao->financeiro != $data['financeiro']) {
            throw new Exception("Não é permitido alterar de uma Natureza que não gera financeiro para outra que gera, ou vice-versa {$negocio->codnegocio}!", 1);
        }

        $natNova = NaturezaOperacao::findOrFail($data['codnaturezaoperacao']);
        if ($negocio->NaturezaOperacao->codoperacao !== $natNova->codoperacao) {
            throw new Exception("Não é permitido alterar de uma Natureza de Saída para outra de Entrada ou vice-versa {$negocio->codnegocio}!", 1);
        }

        // Ignorar do front:
        // Usuario
        // PDV 
        // Data de lançamento
        // Status
        unset($data['codusuario']);
        unset($data['lancamento']);
        unset($data['codnegociostatus']);
        unset($data['codpdv']);
        $negocio->fill($data);
        $negocio->codfilial = $negocio->EstoqueLocal->codfilial;
        $negocio->save();

        foreach ($negocio->NegocioFormaPagamentoS as $nfp) {
            foreach ($nfp->TituloS as $titulo) {
                $titulo->codpessoa = $negocio->codpessoa;
                $titulo->codtipotitulo = $natNova->codtipotitulo;
                $titulo->codcontacontabil = $natNova->codcontacontabil;
                $titulo->save();
            }
        }

        // agenda movimentacao de estoque
        static::movimentarEstoque($negocio);

        return $negocio;
    }


    public static function movimentarEstoque(Negocio $negocio)
    {
        // Chama MGLara para fazer movimentacao do estoque com delay de 10 segundos
        $url = config('services.mglara.url') . "estoque/gera-movimento-negocio/{$negocio->codnegocio}?delay=10";
        try {
            $ret = json_decode(file_get_contents($url, false, stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ]
            ])));
            if (@$ret->response !== 'Agendado') {
                Log::error("Erro ao agendar movimentacao de estoque do negocio {$negocio->codnegocio}: ", (array) $ret);
                return false;
            }
        } catch (\Throwable $th) {
            Log::error("Erro ao agendar movimentacao de estoque do negocio {$negocio->codnegocio}: {$th->getMessage()}");
            return false;
        }
        return true;
    }

    public static function fechar(Negocio $negocio, Pdv $pdv)
    {
        // inicia transacao no Banco
        DB::beginTransaction();

        // validacao de status
        if ($negocio->codnegociostatus != NegocioService::STATUS_ABERTO) {
            throw new Exception('O Status do Negócio não permite Fechamento!', 1);
        }

        // validacao de itens informados
        //
        // Um vale compras sozinho E' conteudo: a venda avulsa de vale sem
        // nenhuma mercadoria junto e' o caso mais comum do vale (era assim
        // no MGLara, e sao 3.718 vales historicos quase todos assim).
        if (
            $negocio->NegocioProdutoBarraS()->whereNull('inativo')->count() == 0
            && !$negocio->NegocioValeS()->whereNull('inativo')->exists()
        ) {
            throw new Exception('Não foi informado nenhum produto neste negócio!', 1);
        }

        if (!static::confereTotais($negocio)) {
            throw new Exception('Total do Negócio não bate com o Total dos Itens! Tente transmitir novamente para o servidor (Botão Roxo)!', 1);
        }

        // vale compras: natureza tem que gerar financeiro e vale nao pode
        // estar zerado (negocio sem vale passa reto)
        PdvNegocioValeService::validarFechamento($negocio);

        // validacoes de venda
        if ($negocio->NaturezaOperacao->venda == true) {
            // valida se tem CPF/CNPJ
            if (($negocio->valortotal >= 1000) && (empty($negocio->Pessoa->cnpj)) && (empty($negocio->cpf))) {
                throw new Exception('Obrigatório Identificar CPF para vendas acima de R$ 1.000,00!', 1);
            }
        }

        if ($negocio->NaturezaOperacao->financeiro == true) {

            // 1. Inicializa os totalizadores de pagamento
            $valorPagamentosLiquidos = 0; // Total pago, descontando o troco
            $valorPagamentosPrazo = 0;
            $valorLimiteCredito = 0;

            // 2. Itera sobre os pagamentos para validações e cálculo dos totais
            foreach ($negocio->NegocioFormaPagamentos as $nfp) {
                // Validações de regra de negócio
                if ($negocio->codpessoa == 1) { // Consumidor final
                    if (!$nfp->FormaPagamento->avista && $nfp->parcelas > 1) {
                        throw new Exception('Somente é permitido Parcelamento para Pessoas ou Empresas Cadastradas!', 1);
                    }
                    if ($nfp->FormaPagamento->boleto) {
                        throw new Exception('Somente é permitido Boleto para Pessoas ou Empresas Cadastradas!', 1);
                    }
                    if ($nfp->FormaPagamento->fechamento) {
                        throw new Exception('Somente é permitido Fechamento para Pessoas ou Empresas Cadastradas!', 1);
                    }
                }

                // cheque: cliente identificado, sem troco, CMC7 válido
                if (PdvNegocioChequeService::ehCheque($nfp)) {
                    PdvNegocioChequeService::validar($negocio, $nfp);
                }

                // Cálculo dos totais
                // O valor que realmente cobre o negócio é o valor total pago menos o troco
                $valorPagamentosLiquidos += $nfp->valortotal - $nfp->valortroco;

                // Acumula os valores a prazo
                if (!$nfp->FormaPagamento->avista) {
                    $valorPagamentosPrazo += $nfp->valorpagamento;
                    if (!$nfp->FormaPagamento->entrega && !$nfp->FormaPagamento->pix) {
                        $valorLimiteCredito += $nfp->valorpagamento;
                    }
                }
            }

            // 3. Validação principal: total do negócio vs. total líquido dos pagamentos
            // Usa uma pequena tolerância para evitar erros de ponto flutuante
            $tolerancia = 0.009999999999999; // 1 centavo

            if (abs($valorPagamentosLiquidos - $negocio->valortotal) > $tolerancia) {
                $valorPagamentosFormatado = formataNumero($valorPagamentosLiquidos, 2);
                $valorTotalFormatado = formataNumero($negocio->valortotal, 2);
                throw new Exception("O valor dos Pagamentos ({$valorPagamentosFormatado}) não bate com o Total ({$valorTotalFormatado})!", 1);
            }

            // 4. Validação do valor total à prazo
            $diferencaPrazo = abs($valorPagamentosPrazo - $negocio->valortotal);
            if ($valorPagamentosPrazo > $negocio->valortotal && $diferencaPrazo >= $tolerancia) {
                $valorPagamentosPrazoFormatado = formataNumero($valorPagamentosPrazo, 2);
                $valorTotalFormatado = formataNumero($negocio->valortotal, 2);
                throw new Exception("O valor à prazo ({$valorPagamentosPrazoFormatado}) é superior ao Total ({$valorTotalFormatado})!", 1);
            }

            // 5. Validação de limite de crédito
            if (!PdvNegocioPrazoService::avaliaLimiteCredito($negocio->Pessoa, $valorLimiteCredito)) {
                throw new Exception('Solicite Liberação de Crédito ao Departamento Financeiro!', 1);
            }
        }

        // validacoes de transferencia
        if ($negocio->NaturezaOperacao->transferencia == true) {
            $mesmaEmpresa = false;
            foreach ($negocio->Pessoa->FilialS as $fil) {
                if ($fil->codempresa == $negocio->Filial->codempresa) {
                    $mesmaEmpresa = true;
                } else {
                    $mesmaEmpresa = false;
                }
            }
            if (!$mesmaEmpresa) {
                throw new Exception("A Pessoa destino precisa ser uma Filial!", 1);
            }
        }

        // marca negocio como fechado
        $negocio->codnegociostatus = NegocioService::STATUS_FECHADO;
        if ($usuario = Auth::user()) {
            $negocio->codusuario = $usuario->codusuario;
        }
        $negocio->codpdv = $pdv->codpdv;
        $negocio->lancamento = Carbon::now();
        $negocio->save();

        // gera titulos do financeiro
        if ($negocio->NaturezaOperacao->financeiro) {
            $prazo = PdvNegocioPrazoService::gerarTitulos($negocio);
            $negocio->valoraprazo = $prazo;
            $negocio->valoravista = $negocio->valortotal - $prazo;
            $negocio->save();
            // Reconfere, COM LOCK, o saldo de cada vale usado como pagamento
            // -- antes de baixar. Com o consumo por escopo, dois PDVs montam
            // o FIFO sobre o mesmo pool da escola e os dois passam na
            // validacao da tela; quem separa e' esta trava.
            PdvValeEscopoService::reconferirSaldos($negocio);
            PdvNegocioPrazoService::baixarVales($negocio);
            PdvNegocioChequeService::gerar($negocio);
            // emite o credito de cada vale VENDIDO neste negocio (titulo
            // tipo 3 / conta 83 em nome do favorecido). Vem depois do
            // baixarVales de proposito: aquele consome vale ANTIGO como
            // forma de pagamento, este cria o vale NOVO.
            PdvNegocioValeService::emitirCreditos($negocio);
        } else {
            $negocio->valoraprazo = 0;
            $negocio->valoravista = 0;
            $negocio->save();
        }

        // salva transacao no banco de dados
        DB::commit();

        // Dispara processamento de indicadores RH
        ProcessarVendaJob::dispatch($negocio->codnegocio);

        // busca dados atualizados no banco de dados
        $negocio = $negocio->fresh();

        // registra boletos se houver
        try {
            BoletoBbService::registrarPeloNegocio($negocio);
        } catch (\Throwable $th) {
        }

        // agenda movimentacao de estoque
        static::movimentarEstoque($negocio);

        // retorna
        return $negocio;
    }

    public static function apropriar(Negocio $negocio, Pdv $pdv)
    {
        if (!in_array($negocio->codnegociostatus, [NegocioService::STATUS_ABERTO])) {
            throw new Exception("Status do Negócio Não Permite Troca do PDV!", 1);
        }

        if ($negocio->codpdv == $pdv->codpdv) {
            throw new Exception("Este negócio já está vinculado à este PDV!", 1);
        }

        $negocio->codpdv = $pdv->codpdv;
        $negocio->lancamento = Carbon::now();
        $negocio->save();

        return $negocio;
    }


    public static function cancelar(Negocio $negocio, Pdv $pdv, String $justificativa)
    {
        if (!in_array($negocio->codnegociostatus, [NegocioService::STATUS_ABERTO, NegocioService::STATUS_FECHADO])) {
            throw new Exception("Status do Negócio Não Permite Cancelamento!", 1);
        }

        // vale ja' usado nao volta: avisa antes de comecar a estornar
        // qualquer coisa
        PdvNegocioValeService::validarCancelamento($negocio);

        $nfs = NotaFiscalNegocioService::notasDoNegocio($negocio->codnegocio);
        foreach ($nfs as $nf) {
            if (NotaFiscalStatusService::isAtiva($nf)) {
                throw new Exception("Negócio possui Nota Fiscal ativa!", 1);
            } else if (NotaFiscalStatusService::isDigitacao($nf)) {
                NotaFiscalService::excluir($nf);
            }
        }

        foreach ($negocio->NegocioFormaPagamentoS as $nfp) {
            foreach ($nfp->TituloS as $tit) {
                if (($tit->debito - $tit->credito) != $tit->saldo) {
                    throw new Exception("O Título {$tit->numero} já foi movimentado. Impossível cancelar!", 1);
                }
                if (!empty($tit->estornado)) {
                    continue;
                }
                TituloService::estornar($tit);
            }
        }
        PdvNegocioPrazoService::estornarBaixaVales($negocio);
        // o credito emitido por ESTE negocio e' titulo solto: so'
        // tblnegociovale.codtitulo aponta para ele, entao o loop dos
        // pagamentos acima nunca o alcanca
        PdvNegocioValeService::estornarCreditos($negocio);
        PdvNegocioChequeService::cancelar($negocio);

        $negocio->codnegociostatus = NegocioService::STATUS_CANCELADO;
        $negocio->justificativa = $justificativa;
        $negocio->save();
        static::movimentarEstoque($negocio);
        return $negocio;
    }
}
