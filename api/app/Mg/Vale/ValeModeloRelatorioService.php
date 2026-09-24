<?php

namespace Mg\Vale;

use Mpdf\Mpdf;

/**
 * Impressao do modelo de vale compras: a lista do kit COM PRECOS, para a
 * escola conferir itens e valores antes da temporada.
 *
 * Nao leva validade de proposito -- preco de material muda entre a validacao
 * (nov/dez) e a venda (janeiro). O documento vale pela data de impressao, que
 * fica no rodape.
 *
 * Os itens saem do ValeModeloProdutoBarraResource, o mesmo que alimenta a
 * tela: e o que faz "conferir o PDF contra a tela" ser verdade, e nao uma
 * segunda montagem de descricao que diverge.
 *
 * O cabecalho leva so a marca. O modelo e catalogo da empresa inteira e nao
 * tem filial: pendurar a loja de quem imprime seria inventar um vinculo que
 * os dados nao tem.
 */
class ValeModeloRelatorioService
{
    public static function pdf(ValeModelo $modelo): string
    {
        $html = static::html($modelo);

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // Retrato: sao 5 colunas estreitas, e o documento vai para a escola
        // conferir e arquivar junto com o resto da papelada dela.
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            // O cabecalho da marca repete em toda pagina; a margem superior
            // reserva a altura dele.
            'margin_top' => 20,
            'margin_bottom' => 14,
            'margin_header' => 8,
            'margin_footer' => 5,
            'default_font' => 'helvetica',
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetTitle('Modelo de Vale Compras - ' . $modelo->modelo);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public static function html(ValeModelo $modelo): string
    {
        // Mesma arvore que o show() carrega: a descricao do item passa por
        // produto, variacao e embalagem, e sem isso sao 4 queries por item.
        $modelo->load([
            'PessoaFavorecido',
            'ValeModeloProdutoBarraS.ProdutoBarra.Produto.UnidadeMedida',
            'ValeModeloProdutoBarraS.ProdutoBarra.ProdutoVariacao.ProdutoImagem',
            'ValeModeloProdutoBarraS.ProdutoBarra.ProdutoEmbalagem.UnidadeMedida',
        ]);

        $itens = $modelo->ValeModeloProdutoBarraS
            ->map(fn ($item) => (new ValeModeloProdutoBarraResource($item))->resolve());

        // Chaveado pela propria URL: dois itens com a mesma foto baixam uma vez so.
        $urls = [];
        foreach ($itens as $item) {
            if (!empty($item['imagem'])) {
                $urls[$item['imagem']] = $item['imagem'];
            }
        }

        return view('vale-modelo.relatorio', [
            'modelo' => $modelo,
            'itens' => $itens,
            'fotos' => static::miniaturas($urls),
            'logo' => static::logo(),
        ])->render();
    }

    /** Lado da miniatura no papel, em mm. */
    private const FOTO_MM = 10.0;

    /**
     * Baixa as fotos dos itens e devolve, por URL, a miniatura pronta para
     * embutir: ['src' => data-uri, 'w' => mm, 'h' => mm].
     *
     * Baixa em PARALELO e REDUZ antes de embutir. As imagens do MGLara sao
     * 1000x1000 com ~60KB cada: num kit de 50 itens, embutir cruas dariam 3MB
     * de PDF para miniaturas de 10mm, e em serie seriam 50 idas a rede.
     *
     * Foto que nao baixar ou nao decodificar simplesmente nao sai -- o
     * documento vale pela lista de itens e precos, nao pela imagem, e nao pode
     * deixar de imprimir porque o servidor de imagens piscou.
     */
    protected static function miniaturas(array $urls): array
    {
        if (empty($urls) || !function_exists('curl_multi_init')) {
            return [];
        }

        $multi = curl_multi_init();
        $handles = [];
        foreach ($urls as $chave => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 10,
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$chave] = $ch;
        }

        do {
            $status = curl_multi_exec($multi, $ativos);
            if ($ativos) {
                curl_multi_select($multi, 1.0);
            }
        } while ($ativos && $status === CURLM_OK);

        $ret = [];
        foreach ($handles as $chave => $ch) {
            $corpo = curl_multi_getcontent($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);

            if ($http === 200 && !empty($corpo) && ($mini = static::miniatura($corpo))) {
                $ret[$chave] = $mini;
            }
        }
        curl_multi_close($multi);

        return $ret;
    }

    /**
     * Reduz uma foto para a caixa da coluna, preservando a proporcao dela.
     *
     * Devolve o tamanho ja em mm justamente para o blade nao ter que adivinhar:
     * com so uma das medidas no style, o mPDF estica a imagem.
     */
    protected static function miniatura(string $binario): ?array
    {
        $img = @imagecreatefromstring($binario);
        if (!$img) {
            return null;
        }

        $largura = imagesx($img);
        $altura = imagesy($img);
        if ($largura < 1 || $altura < 1) {
            imagedestroy($img);
            return null;
        }

        // 150px num quadro de 10mm da ~380dpi: sobra resolucao para impressao
        // e o arquivo cai de ~60KB para ~4KB.
        $lado = 150;
        $escala = $lado / max($largura, $altura);
        if ($escala < 1) {
            $reduzida = imagescale($img, (int) round($largura * $escala), (int) round($altura * $escala));
            if ($reduzida) {
                imagedestroy($img);
                $img = $reduzida;
                $largura = imagesx($img);
                $altura = imagesy($img);
            }
        }

        ob_start();
        imagejpeg($img, null, 75);
        $jpeg = ob_get_clean();
        imagedestroy($img);

        if (empty($jpeg)) {
            return null;
        }

        $mm = static::FOTO_MM / max($largura, $altura);
        return [
            'src' => 'data:image/jpeg;base64,' . base64_encode($jpeg),
            'w' => round($largura * $mm, 2),
            'h' => round($altura * $mm, 2),
        ];
    }

    /** Logo embutida em base64: o mPDF nao busca imagem por URL relativa. */
    protected static function logo(): ?string
    {
        $path = public_path('MGPapelariaLogo.jpeg');
        if (!is_readable($path)) {
            return null;
        }
        return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path));
    }
}
