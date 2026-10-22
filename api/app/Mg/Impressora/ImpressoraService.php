<?php

namespace Mg\Impressora;

use Illuminate\Support\Facades\Http;
use Mg\MgService;

class ImpressoraService extends MgService
{
    // todas de uma vez (sao poucas): a tela filtra e agrupa no front
    public static function listar(?bool $inativos = null)
    {
        $q = Impressora::query();
        if ($inativos === false) {
            $q->whereNull('inativo');
        } elseif ($inativos === true) {
            $q->whereNotNull('inativo');
        }
        return $q->orderBy('descricao')->orderBy('codimpressora')->get();
    }

    public static function salvar(array $dados, ?Impressora $impressora = null): Impressora
    {
        $impressora = $impressora ?? new Impressora();
        $impressora->fill($dados);
        $impressora->save();
        return $impressora;
    }

    // A impressora pelo codimpressora; na transicao (PDV com a versao antiga), tambem
    // pelo nome da fila no CUPS, que era o que as rotas recebiam
    public static function resolver($valor): Impressora
    {
        if ($valor instanceof Impressora) {
            return $valor;
        }
        $impressora = (is_int($valor) || ctype_digit((string) $valor))
            ? Impressora::find((int) $valor)
            : Impressora::where('impressora', (string) $valor)->first();
        if (!$impressora) {
            abort(422, 'Impressora não cadastrada! Escolha outra em Meu Dispositivo.');
        }
        return $impressora;
    }

    // Manda o PDF da url para a impressora: o MGprint assina o evento com o nome
    // da fila do CUPS no canal "printing" do Ably, baixa a url e roda o lp.
    public static function publicar(Impressora $impressora, string $url, array $options = [], int $copias = 1): void
    {
        if (!empty($impressora->inativo)) {
            abort(422, "A impressora {$impressora->descricao} está inativa!");
        }
        [$usuario, $senha] = array_pad(explode(':', (string) config('services.ably.key'), 2), 2, '');
        $resposta = Http::timeout(10)
            ->withBasicAuth($usuario, $senha)
            ->post('https://rest.ably.io/channels/printing/messages', [
                'name' => $impressora->impressora,
                'data' => json_encode([
                    'url' => $url,
                    'method' => 'get',
                    'options' => array_values($options),
                    'copies' => $copias,
                ]),
            ]);
        if (!$resposta->successful()) {
            abort(502, "Falha ao enviar para a impressora {$impressora->descricao}. Tente de novo.");
        }
    }

    // argox-* sao as de etiqueta; o resto do printers.json e' termica
    public static function tipoPeloNome(string $impressora): string
    {
        return str_starts_with($impressora, 'argox') ? Impressora::TIPO_ETIQUETA : Impressora::TIPO_TERMICA;
    }

    // Le o printers.json (chave => nome) e cria as que faltam. Nao mexe nas que ja
    // existem: o cadastro passa a ser a fonte, o arquivo e' so a carga inicial.
    public static function importarJson(string $arquivo): array
    {
        $lista = json_decode(file_get_contents($arquivo), true);
        if (!is_array($lista)) {
            throw new \Exception("Arquivo {$arquivo} nao e' um JSON de impressoras!");
        }
        $criadas = [];
        foreach ($lista as $nome => $descricao) {
            if (empty($nome) || Impressora::where('impressora', $nome)->exists()) {
                continue;
            }
            static::salvar([
                'impressora' => $nome,
                'descricao' => mb_substr($descricao ?: $nome, 0, 100),
                'tipo' => static::tipoPeloNome($nome),
            ]);
            $criadas[] = $nome;
        }
        return $criadas;
    }
}
