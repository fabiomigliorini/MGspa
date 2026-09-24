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

        return view('vale-modelo.relatorio', [
            'modelo' => $modelo,
            'itens' => $itens,
            'logo' => static::logo(),
        ])->render();
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
