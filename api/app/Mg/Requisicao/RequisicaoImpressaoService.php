<?php

namespace Mg\Requisicao;

use Dompdf\Dompdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\Common\EccLevel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Mg\Estoque\EstoqueLocalProdutoVariacao;
use Mg\Impressora\Impressora;
use Mg\Impressora\ImpressoraService;
use Mg\Vale\ValeModeloRelatorioService;

// As duas vias da requisicao (TASK-55, doc-6): a da loja que pede (autorizacao de
// retirada com o codigo) sai na impressora do PDV que registrou; a de separacao
// (com QR, sem o codigo) sai na impressora do local que atende.
class RequisicaoImpressaoService
{
    const VIA_REQUISITANTE = 'requisitante';
    const VIA_REQUISITADA = 'requisitada';
    const VIAS = [self::VIA_REQUISITANTE, self::VIA_REQUISITADA];

    const FORMATO_TERMICA = 'termica';
    const FORMATO_A5 = 'a5';
    const FORMATOS = [self::FORMATO_TERMICA, self::FORMATO_A5];

    // URL do detalhe com a loja destino: a camera abre a requisicao, o leitor do PDV
    // reconhece o endereco e liga o negocio offline (etapa 9)
    public static function urlQr(Requisicao $req): string
    {
        return rtrim(config('mg.apps.negocios'), '/')
            . "/requisicao/{$req->codrequisicao}?destino={$req->codestoquelocalrequisitante}";
    }

    public static function qrcode(Requisicao $req): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => EccLevel::M,
            'scale' => 4,
            'outputBase64' => true,
        ]);
        return (new QRCode($options))->render(static::urlQr($req));
    }

    // corredor/prateleira/coluna/bloco do produto no local que atende
    protected static function localizacao(?int $codestoquelocal, int $codprodutovariacao): ?string
    {
        if (empty($codestoquelocal)) {
            return null;
        }
        $elpv = EstoqueLocalProdutoVariacao::where('codestoquelocal', $codestoquelocal)
            ->where('codprodutovariacao', $codprodutovariacao)
            ->first();
        if (!$elpv) {
            return null;
        }
        $partes = array_filter([
            $elpv->corredor ? "Corredor {$elpv->corredor}" : null,
            $elpv->prateleira ? "Prateleira {$elpv->prateleira}" : null,
            $elpv->coluna ? "Coluna {$elpv->coluna}" : null,
            $elpv->bloco ? "Bloco {$elpv->bloco}" : null,
        ]);
        return $partes ? implode(' ', $partes) : null;
    }

    // As tres vias saem com os itens agrupados por marca, que e' como o deposito
    // guarda a mercadoria; dentro da marca, pela descricao. No A5 vai a foto.
    public static function pdf(Requisicao $req, string $via, string $formato): string
    {
        $req->loadMissing(array_merge(RequisicaoResource::RELACOES, [
            'RequisicaoItemS.ProdutoBarra.Produto.Marca',
            'RequisicaoItemS.ProdutoBarra.ProdutoVariacao.Marca',
        ]));
        $a5 = $formato === static::FORMATO_A5;
        $itens = $req->RequisicaoItemS->where('quantidade', '>', 0)
            ->map(function ($i) use ($req, $a5) {
                $pb = $i->ProdutoBarra;
                $pe = $pb->ProdutoEmbalagem;
                $um = $pe ? $pe->UnidadeMedida : $pb->Produto->UnidadeMedida;
                $codimagem = $pb->ProdutoVariacao->ProdutoImagem?->codimagem;
                // a unidade (com a quantidade da embalagem) vai embaixo da quantidade, nao na descricao
                return [
                    'descricao' => trim($pb->Produto->produto . ' ' . $pb->ProdutoVariacao->variacao),
                    'barras' => $pb->barras,
                    'unidade' => $um->sigla . ($pe ? ' C/' . intval($pe->quantidade) : ''),
                    'quantidade' => rtrim(rtrim(number_format($i->quantidade, 3, ',', '.'), '0'), ','),
                    'localizacao' => static::localizacao($req->codestoquelocalrequisitada, $pb->codprodutovariacao),
                    // a variacao pode ter marca propria; senao, a do produto
                    'marca' => ($pb->ProdutoVariacao->Marca ?? $pb->Produto->Marca)?->marca ?? 'Sem marca',
                    'imagem' => ($a5 && $codimagem)
                        ? config('services.mglara.imagens_url') . "/{$codimagem}.jpg"
                        : null,
                ];
            })
            ->sortBy([['marca', 'asc'], ['descricao', 'asc']])
            ->groupBy('marca');
        // a foto que nao baixar simplesmente nao sai (ver ValeModeloRelatorioService)
        $fotos = $a5
            ? ValeModeloRelatorioService::miniaturas(
                $itens->flatten(1)->pluck('imagem')->filter()->unique()->mapWithKeys(fn ($u) => [$u => $u])->all()
            )
            : [];
        $qrcode = $via === static::VIA_REQUISITADA ? static::qrcode($req) : null;

        $dompdf = new Dompdf();
        $dompdf->loadHtml(view('requisicao.requisicao', compact('req', 'via', 'formato', 'itens', 'fotos', 'qrcode'))->render(), 'UTF-8');
        if ($a5) {
            $dompdf->setPaper('a5', 'portrait');
        } else {
            // bobina 80 mm x 297 (altura A4), igual ao romaneio
            $dompdf->setPaper([0.0, 0.0, 226.77, 841.89], 'portrait');
        }
        $dompdf->render();
        return $dompdf->output();
    }

    public static function formato(Impressora $impressora): string
    {
        return $impressora->tipo === Impressora::TIPO_A5 ? static::FORMATO_A5 : static::FORMATO_TERMICA;
    }

    // via requisitante: impressora do PDV que registrou; via requisitada: a do local que atende
    public static function impressoraPadrao(Requisicao $req, string $via): ?Impressora
    {
        if ($via === static::VIA_REQUISITANTE) {
            $impressora = $req->Pdv?->Impressora;
        } else {
            $impressora = $req->EstoqueLocalRequisitada?->Impressora;
        }
        return ($impressora && empty($impressora->inativo)) ? $impressora : null;
    }

    public static function imprimir(Requisicao $req, string $via, Impressora $impressora): void
    {
        $url = URL::temporarySignedRoute('requisicao.pdf', now()->addMinutes(10), [
            'codrequisicao' => $req->codrequisicao,
            'via' => $via,
            'formato' => static::formato($impressora),
        ]);
        ImpressoraService::publicar($impressora, $url);
    }

    // o que nao vai sair sozinho (sem impressora configurada), para a tela avisar
    public static function avisosImpressora(Requisicao $req): array
    {
        $avisos = [];
        if (!static::impressoraPadrao($req, static::VIA_REQUISITANTE)) {
            $avisos[] = 'Este dispositivo não tem impressora: imprima a via '
                . (RequisicaoEntregaService::comCodigo($req) ? 'do cliente' : 'da loja que pede')
                . ' pelo botão.';
        }
        if (!static::impressoraPadrao($req, static::VIA_REQUISITADA)) {
            $avisos[] = ($req->EstoqueLocalRequisitada?->estoquelocal ?? 'A loja que atende')
                . ' não tem impressora da via de separação: imprima pelo botão.';
        }
        return $avisos;
    }

    // as duas vias nas impressoras padrao (ImprimirRequisicaoJob, depois do commit do criar)
    public static function imprimirAutomatico(Requisicao $req): void
    {
        foreach (static::VIAS as $via) {
            $impressora = static::impressoraPadrao($req, $via);
            if (!$impressora) {
                continue;
            }
            try {
                static::imprimir($req, $via, $impressora);
            } catch (\Throwable $e) {
                Log::warning("Requisicao {$req->codrequisicao}: falha ao imprimir a via {$via}: {$e->getMessage()}");
            }
        }
    }
}
