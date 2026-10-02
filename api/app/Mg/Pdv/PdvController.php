<?php

namespace Mg\Pdv;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Cidade\Cidade;
use Mg\Negocio\NegocioResource;
use Mg\Negocio\NegocioListagemResource;
use Mg\Negocio\NegocioComandaService;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\NotaFiscal\NotaFiscalNegocioService;
use Mg\Pagamento\CobrancaService;
use Mg\PagarMe\PagarMePedidoResource;
use Mg\Pix\PixCobResource;
use Mg\PagarMe\PagarMeService;
use Mg\PagarMe\PagarMePedido;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloResource;
use App\Rules\InscricaoEstadual;
use Carbon\Carbon;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Produto\ProdutoService;
use Mg\Saurus\S2Pay\ApiService;
use Mg\Saurus\SaurusPdv;
use Mg\Saurus\SaurusPedido;
use Mg\Saurus\SaurusPedidoResource;
use Mg\Saurus\SaurusPinPad;
use Mg\Saurus\SaurusService;
use Mg\Rh\ProcessarVendaJob;
use Mg\Usuario\Autorizador;

class PdvController
{

    public function getDispositivo(Request $request)
    {
        Autorizador::autoriza(['Administrador']);
        $query = Pdv::with(['Filial', 'Setor', 'Portador'])->orderBy('criacao', 'desc');
        if ($request->apelido) {
            $query->where('apelido', 'ilike', "%{$request->apelido}%");
        }
        if ($request->codfilial) {
            $query->where('codfilial', $request->codfilial);
        }
        if ($request->status) {
            match ($request->status) {
                'autorizado' => $query->where('autorizado', true)->whereNull('inativo'),
                'inativo' => $query->whereNotNull('inativo'),
                'nao_autorizado' => $query->where(function ($q) {
                    $q->where('autorizado', false)->orWhereNull('autorizado');
                })->whereNull('inativo'),
            };
        } else {
            $query->whereNull('inativo');
        }
        if ($request->ip) {
            $query->where('ip', 'ilike', "%{$request->ip}%");
        }
        if ($request->uuid) {
            $query->where('uuid', 'ilike', "%{$request->uuid}%");
        }
        if ($request->codsetor) {
            $query->where('codsetor', $request->codsetor);
        }
        return PdvResource::collection($query->get());
    }

    public function putDispositivo(Request $request)
    {
        $request->validate([
            'uuid' => 'required|uuid',
            'desktop' => 'required|boolean',
            'navegador' => 'required',
            'versaonavegador' => 'required',
            'plataforma' => 'required',
            // 'codsetor' => 'required',
        ]);
        $pdv = PdvService::dispositivo(
            $request->uuid,
            $request->ip(),
            $request->latitude,
            $request->longitude,
            $request->precisao,
            $request->desktop,
            $request->navegador,
            $request->versaonavegador,
            $request->plataforma,
            $request->apelido,
            $request->codfilial,
            $request->codsetor,
            $request->observacoes
        );
        return new PdvResource($pdv);
    }

    public static function autorizar($codpdv)
    {
        Autorizador::autoriza([]);
        $pdv = Pdv::findOrFail($codpdv);
        $pdv = PdvService::autorizar($pdv);
        return new PdvResource($pdv);
    }

    public static function desautorizar($codpdv)
    {
        Autorizador::autoriza([]);
        $pdv = Pdv::findOrFail($codpdv);
        $pdv = PdvService::desautorizar($pdv);
        return new PdvResource($pdv);
    }

    public static function inativar($codpdv)
    {
        Autorizador::autoriza([]);
        $pdv = Pdv::findOrFail($codpdv);
        $pdv = PdvService::inativar($pdv);
        return new PdvResource($pdv);
    }

    public static function reativar($codpdv)
    {
        Autorizador::autoriza([]);
        $pdv = Pdv::findOrFail($codpdv);
        $pdv = PdvService::reativar($pdv);
        return new PdvResource($pdv);
    }

    public function produtoCount(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::produtoCount();
    }

    public function produto(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $codprodutobarra = $request->codprodutobarra ?? 0;
        $limite = $request->limite ?? 10000;
        return PdvService::produto($codprodutobarra, $limite);
    }

    public function produtoBarras(PdvRequest $request, $barras)
    {
        PdvService::autoriza($request->pdv);
        $codprodutobarra = -1;
        if ($pb = ProdutoService::buscaPorBarras($barras)) {
            $codprodutobarra = $pb->codprodutobarra;
        }
        return PdvService::produto($codprodutobarra, 1);
    }

    public function produtoDetalhe(PdvRequest $request, $barras)
    {
        PdvService::autoriza($request->pdv);
        return ProdutoService::detalheQuiosque($barras);
    }

    public function pessoaCount(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::pessoaCount();
    }

    public function pessoa(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $codpessoa = $request->codpessoa ?? 0;
        $limite = $request->limite ?? 10000;
        return PdvService::pessoa($codpessoa, null, $limite);
    }

    public function pessoaPeloCnpj(PdvRequest $request, $cnpj)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::pessoa(null, $cnpj, 999999);
    }

    public function postPessoa(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);

        // caso tenha IE
        if (!empty($request->ie)) {

            // busca a cidade do cadastro
            $cidade = Cidade::find($request['enderecos'][0]['codcidade']);
            if (!$cidade) {
                throw new Exception("Cidade não informada!", 1);
            }

            // descobre a UF
            $uf = $cidade->Estado->sigla;

            // completa com zero a esquerda de acordo com a UF
            $request['ie'] = InscricaoEstadual::padPelaUf($uf, $request['ie']);

            // valida a IE na UF
            $request->validate([
                'ie' => new InscricaoEstadual($uf),
            ]);
        }

        // valida restante dos campos
        $request->validate([
            'cnpj' => 'required|cpf_cnpj',
            'fisica' => 'required|boolean',
            'fantasia' => 'required|min:3',
            'pessoa' => 'required|min:3',
        ]);

        //salva dados no banco
        $data = (object) $request->all();
        DB::beginTransaction();
        $pessoa = PdvPessoaService::novaPessoa($data);
        DB::commit();

        // retorna pessoa salva
        return PdvService::pessoa($pessoa->codpessoa, null, 1);
    }

    public function naturezaOperacao(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::naturezaOperacao();
    }

    public function estoqueLocal(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::estoqueLocal();
    }

    public function formaPagamento(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::formaPagamento();
    }

    public function valeModelo(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::valeModelo();
    }

    public function impressora(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::impressora();
    }

    public function getPrancheta(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvPranchetaService::getPrancheta();
    }

    public function putPrancheta(PdvRequest $request)
    {
        DB::beginTransaction();
        PdvService::autoriza($request->pdv);
        PdvPranchetaService::updatePrancheta($request->prancheta);
        DB::commit();
        return true;
    }

    public function putNegocio(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        DB::beginTransaction();
        $negocio = PdvNegocioService::negocio($request->negocio, $pdv);
        DB::commit();
        return new NegocioResource($negocio);
    }

    public function deleteNegocio(PdvRequest $request, $codnegocio)
    {
        $request->validate([
            'justificativa' => [
                'required',
                'string',
                'min:15',
            ]
        ]);
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = Negocio::findOrFail($codnegocio);
        DB::beginTransaction();
        $negocio = PdvNegocioService::cancelar($negocio, $pdv, $request->justificativa);
        DB::commit();
        ProcessarVendaJob::dispatch($negocio->codnegocio);
        return new NegocioResource($negocio);
    }

    public function getNegocio(PdvRequest $request, $codnegocio)
    {
        PdvService::autoriza($request->pdv);
        if (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $codnegocio)) {
            $negocio = Negocio::where(['uuid' => $codnegocio])->firstOrFail();
        } else {
            $negocio = Negocio::findOrFail($codnegocio);
        }
        return new NegocioResource($negocio);
    }

    public function getNegocios(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);

        $qry = Negocio::query();

        foreach ($request->all() as $filtro => $valor) {
            if (empty($valor)) {
                continue;
            }
            switch ($filtro) {
                case 'valor_de':
                    $qry->where('valortotal', '>=', $valor);
                    break;
                case 'valor_ate':
                    $qry->where('valortotal', '<=', $valor);
                    break;
                case 'lancamento_de':
                    $qry->where('lancamento', '>=', $valor);
                    break;
                case 'lancamento_ate':
                    $qry->where('lancamento', '<=', $valor);
                    break;
                case 'codnegocio':
                    $qry->where('codnegocio', $valor);
                    break;
                case 'codestoquelocal':
                    $qry->where('codestoquelocal', $valor);
                    break;
                case 'codnegociostatus':
                    $qry->where('codnegociostatus', $valor);
                    break;
                case 'codnaturezaoperacao':
                    $qry->where('codnaturezaoperacao', $valor);
                    break;
                case 'codpessoa':
                    $qry->where('codpessoa', $valor);
                    break;
                case 'codpessoavendedor':
                    $qry->where('codpessoavendedor', $valor);
                    break;
                case 'codpessoatransportador':
                    $qry->where('codpessoatransportador', $valor);
                    break;
                case 'codpdv':
                    $qry->where('codpdv', $valor);
                    break;
                case 'codusuario':
                    $qry->where('codusuario', $valor);
                    break;
                case 'pdv':
                    break;
                default:
                    break;
            }
        }
        $qry->orderBy('lancamento', 'desc')->orderBy('codnegocio', 'desc');
        return NegocioListagemResource::collection($qry->paginate(100));
    }

    public function getOrcamentos(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $qry = Negocio::where('codnegociostatus', 1)->where('uuid', 'ilike', "{$request->uuid}%");
        $qry->orderBy('lancamento', 'desc')->orderBy('codnegocio', 'desc');
        return NegocioListagemResource::collection($qry->limit(50)->get());
    }

    public function fecharNegocio(PdvRequest $request, $codnegocio)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = Negocio::findOrFail($codnegocio);
        $negocio = PdvNegocioService::fechar($negocio, $pdv);
        // vales e contra vales com saldo saem sozinhos na termica do caixa
        if (!empty($request->impressora)) {
            ImprimirValesNegocioJob::dispatch($negocio->codnegocio, $request->impressora)->onQueue('high');
        }
        return new NegocioResource($negocio);
    }

    public function apropriar(PdvRequest $request, $codnegocio)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = Negocio::findOrFail($codnegocio);
        $negocio = PdvNegocioService::apropriar($negocio, $pdv);
        return new NegocioResource($negocio);
    }

    public function romaneio($codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $pdf = RomaneioService::pdf($negocio);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Romaneio' . $codnegocio . '.pdf"'
        ]);
    }

    // ?uuid= imprime so' aquele vale do negocio; sem ele, todos
    public function imprimirVale(Request $request, $codnegocio, $impressora)
    {
        ValeService::imprimir($codnegocio, $impressora, $request->uuid, $request->codtitulo);
    }

    public function vale(Request $request, $codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $pdf = ValeService::pdf($negocio, $request->uuid, $request->codtitulo);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ValeCompras' . $codnegocio . '.pdf"'
        ]);
    }

    public function imprimirRomaneio($codnegocio, $impressora)
    {
        RomaneioService::imprimir($codnegocio, $impressora);
    }

    public function comanda($codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $pdf = NegocioComandaService::pdf($negocio);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comanda' . $codnegocio . '.pdf"'
        ]);
    }

    public function imprimirComanda($codnegocio, $impressora)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        NegocioComandaService::imprimir($negocio, $impressora);
    }

    public function unificarComanda(PdvUnificarComandaRequest $request, $codnegocio, $codnegociocomanda)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = Negocio::findOrFail($codnegocio);
        $negocioComanda = Negocio::findOrFail($codnegociocomanda);
        // só manda o que o usuario escolheu na tela de conflito
        $escolhas = [];
        foreach (['codpessoa', 'codpessoavendedor'] as $campo) {
            if ($request->has($campo)) {
                $escolhas[$campo] = $request->input($campo);
            }
        }
        $negocio = NegocioComandaService::unificar($negocio, $negocioComanda, $pdv, $escolhas);
        return [
            'negocio' => new NegocioResource($negocio),
            'comanda' => new NegocioResource($negocioComanda->fresh())
        ];
    }

    // Cobrancas integradas do wizard (M5 doc-3): para um documento, que pode
    // nao ser negocio (codnegocio nulo, com a pessoa que paga). Devolve a
    // cobranca criada.
    // cobranca integrada do wizard (M5/M6.1 doc-3): o negocio ou nenhum
    public function criarPixCob(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $cob = CobrancaService::pix($request->all(), $pdv);
        return new PixCobResource($cob->fresh());
    }

    public function criarPagarMePedido(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $ped = CobrancaService::pagarMe($request->all(), $pdv);
        return new PagarMePedidoResource($ped->fresh());
    }

    public function criarSaurusPedido(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $ped = CobrancaService::saurus($request->all());
        return new SaurusPedidoResource($ped->fresh());
    }

    public function reenviarSaurusPedido($codsauruspedido)
    {
        $pedido = SaurusPedido::findOrFail($codsauruspedido);

        $pdv = SaurusPdv::where('codsauruspdv', $pedido->codsauruspdv)->firstOrFail();
        $pos = SaurusPinPad::where('codsauruspdv', $pdv->codsauruspdv)->firstOrFail();

        if ($pdv->vencimento < now()) {

            $pessoa = Pessoa::findOrFail(Filial::findOrFail($pdv->codfilial)->codpessoa);

            $autorizacao = ApiService::functionAutorizacao($pdv->id, $pessoa->cnpj);

            SaurusPdv::updateOrCreate(
                [
                    'id' => $pdv->id,
                ],
                [

                    'autorizacao' => $autorizacao->response->chavePublica,
                    'vencimento' => Carbon::parse($autorizacao->response->vencimento)->subHour(1)->subMinutes(10),
                ]
            );

            $pdv->fresh();
        }

        $pedidoResponse = ApiService::functionPedidoCriar($pedido, $pdv, $pos);

        return new SaurusPedidoResource($pedido->fresh());
    }

    public function consultarPagarMePedido($codpagarmepedido)
    {
        $pedido = PagarMePedido::findOrFail($codpagarmepedido);
        $pedido = PagarMeService::consultarPedido($pedido);
        return new PagarMePedidoResource($pedido);
    }

    public function consultarSaurusPedido($codsauruspedido)
    {
        $pedido = SaurusPedido::findOrFail($codsauruspedido);
        if ($pedido->status == 0) {
            $pedido = SaurusService::consultarPedido($pedido);
        }
        // $pedido = SaurusService::consultarPedido($pedido);
        return new SaurusPedidoResource($pedido);
    }

    public function cancelarPagarMePedido($codpagarmepedido)
    {
        $pedido = PagarMePedido::findOrFail($codpagarmepedido);
        $pedido = PagarMeService::cancelarPedido($pedido);
        return new PagarMePedidoResource($pedido);
    }


    public function cancelarSaurusPedido($codsauruspedido)
    {
        $pedido = SaurusPedido::findOrFail($codsauruspedido);
        $pedido = SaurusService::cancelarPedido($pedido);
        return new SaurusPedidoResource($pedido);
    }

    public function importarPagarMePedidosPendentes(request $request)
    {
        $peds = PagarMeService::importarPendentes();
        $peds = PagarMePedido::where('status', PagarMeService::STATUS_NUMBER['pending'])
            ->orderBy('criacao', 'desc')
            ->orderBy('codpagarmepedido', 'desc')
            ->get();
        return PagarMePedidoResource::collection($peds);
    }

    public function pagarMePedidosPendentes(request $request)
    {
        $peds = PagarMePedido::where('status', PagarMeService::STATUS_NUMBER['pending'])
            ->orderBy('criacao', 'desc')
            ->orderBy('codpagarmepedido', 'desc')
            ->get();
        return PagarMePedidoResource::collection($peds);
    }

    public function  notaFiscal(PdvRequest $request, $codnegocio)
    {
        PdvService::autoriza($request->pdv);
        $modelo = intval($request->modelo ?? 65);
        $negocio = Negocio::findOrFail($request->codnegocio);
        NotaFiscalNegocioService::gerarNotaFiscalDoNegocio($negocio, $modelo);
        return new NegocioResource($negocio);
    }


    public function conferencia(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $request->validate([
            'codpdv' => 'required',
            'dia' => 'required'
        ]);
        $negocios = PdvService::conferencia($request->codpdv, $request->dia);
        return response()->json($negocios, 200);
    }

    public function update(PdvRequest $request, $codpdv)
    {
        Autorizador::autoriza([]);
        PdvService::autoriza($request->pdv);
        $pdv =  Pdv::findOrFail($codpdv);
        $data = $request->all();

        unset($data['pdv']);

        $pdvUpdate = PdvService::update($pdv, $data);

        return new PdvResource($pdvUpdate);
    }

    public function devolucao(PdvRequest $request, $codnegocio)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $negocioOriginal = Negocio::findOrFail($codnegocio);
        DB::beginTransaction();
        $negocioDev = PdvNegocioDevolucaoService::gerarDevolucao($pdv, $negocioOriginal, $request->devolucao);
        DB::commit();
        // o vale de credito da devolucao sai sozinho na termica, como no fechar
        if (!empty($request->impressora)) {
            ImprimirValesNegocioJob::dispatch($negocioDev->codnegocio, $request->impressora)->onQueue('high');
        }
        return new NegocioResource($negocioDev);
    }


    /**
     * Escolas com credito de vale em aberto (consumo por escopo).
     */
    public function valeEscopoFavorecidos(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return response()->json(
            PdvValeEscopoService::favorecidos($request->input('busca')),
            200
        );
    }

    /** Turmas com credito em aberto dentro de uma escola. */
    public function valeEscopoTurmas(PdvRequest $request, $codpessoafavorecido)
    {
        PdvService::autoriza($request->pdv);
        return response()->json(
            PdvValeEscopoService::turmas($codpessoafavorecido),
            200
        );
    }

    /**
     * Escolhe em FIFO os vales do escopo que cobrem o valor pedido.
     *
     * A escolha nao reserva nada: quem garante que o saldo ainda existe no
     * fechamento e' o PdvValeEscopoService::reconferirSaldos(), com lock.
     */
    public function valeEscopoSelecionar(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $request->validate([
            'codpessoafavorecido' => 'required|integer',
            'valor' => 'required|numeric',
        ]);
        return response()->json(
            PdvValeEscopoService::selecionar(
                $request->input('codpessoafavorecido'),
                $request->input('turma'),
                $request->input('valor'),
                array_filter(explode(',', (string) $request->input('usados')))
            ),
            200
        );
    }

    public function buscarVale($codtitulo)
    {
        $titulo = Titulo::find($codtitulo);
        if (!$titulo) {
            throw new Exception("Nenhum título localizado com este código!");
        }
        // todo titulo com saldo de credito paga compra no PDV (vale compras,
        // credito e adiantamento do cliente, duplicata a pagar...)
        if ((float) $titulo->saldo >= 0) {
            throw new Exception("Este título não tem saldo de crédito!");
        }
        return new TituloResource($titulo);
    }
}
