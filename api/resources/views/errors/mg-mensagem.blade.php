{{--
    Pagina de erro legivel para rotas abertas em <a target="_blank"> (navegacao top-level).

    Autonoma de proposito: sem @extends, sem asset(), sem auth. Ela e renderizada JUSTAMENTE
    quando algo deu errado, entao nao pode depender de layout, de sessao nem de build de
    front. CSS inline pelo mesmo motivo.

    Variaveis: $titulo, $message, $sugestao (opcional).
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ?? 'Erro' }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f4f5f7;
            color: #1f2933;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                         "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
        }
        .caixa {
            width: 100%;
            max-width: 640px;
            background: #fff;
            border: 1px solid #e1e5ea;
            border-top: 4px solid #d97706;
            border-radius: 6px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }
        h1 { margin: 0 0 16px; font-size: 22px; font-weight: 600; }
        .mensagem { margin: 0; font-size: 16px; }
        .sugestao {
            margin: 24px 0 0;
            padding: 16px;
            background: #fffbeb;
            border-left: 4px solid #d97706;
            border-radius: 0 4px 4px 0;
            font-size: 15px;
        }
        .sugestao strong { display: block; margin-bottom: 4px; }
        .acoes { margin: 28px 0 0; }
        button {
            font: inherit;
            padding: 9px 18px;
            border: 1px solid #c4cad2;
            border-radius: 4px;
            background: #fff;
            color: #1f2933;
            cursor: pointer;
        }
        button:hover { background: #f0f2f5; }
        @media (prefers-color-scheme: dark) {
            body { background: #14171a; color: #e6e8eb; }
            .caixa { background: #1d2125; border-color: #2c3238; box-shadow: none; }
            .sugestao { background: #2a2415; }
            button { background: #1d2125; color: #e6e8eb; border-color: #3a4148; }
            button:hover { background: #262b30; }
        }
    </style>
</head>
<body>
    <div class="caixa">
        <h1>{{ $titulo }}</h1>
        <p class="mensagem">{{ $message }}</p>

        @if (!empty($sugestao))
            <div class="sugestao">
                <strong>O que fazer</strong>
                {{ $sugestao }}
            </div>
        @endif

        <div class="acoes">
            <button type="button" onclick="history.back()">Voltar</button>
        </div>
    </div>
</body>
</html>
