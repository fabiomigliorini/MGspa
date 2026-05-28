<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <style>
    @page { margin: 0px; }
    </style>
</head>
<body style="font-family: Arial, Helvetica, sans-serif">

    <img src="{{ 'data://text/plain;base64,'. base64_encode(file_get_contents(public_path('MGPapelariaLogo.jpeg'))) }}" style="width: 100%" />

    @if (!empty($cob->codnegocio))
    <h3 style="text-align:center; margin-bottom: 5px">
        Negócio #{{ str_pad($cob->codnegocio, 8, '0', STR_PAD_LEFT) }} <br />
        {{$cob->Negocio->Pessoa->fantasia}}
    </h3>
    @endif

    <div style="font-size: 0.6em; text-align:center; margin-bottom: 0.5cm">
        {{ $cob->criacao->format('d/m/Y H:i:s') }}
    </div>

    <div style="text-align:center">
        <img src="{{$qrcode}}" style="width: 220px" />
    </div>

    <h1 style="text-align:center; margin: 10px">
        R$ {{ number_format($cob->valororiginal, 2, ',', '.') }}
    </h1>
    <hr />
    <h3 style="text-align:center; margin-bottom: 5px">Como pagar com <br />PIX QR Code?</h3>
    <div style="font-size: 0.8em">
        <ol>
            <li>
                Acesse a opção “Pix” no Menu Principal do App do seu banco;
            </li>
            <li>
                Clique na opção “Ler QR Code”;
            </li>
            <li>
                Aponte a camera do celular para a imagem impressa neste cupom;
            </li>
            <li>
                Siga as instruções de pagamento do seu banco para finalizar a operação.
            </li>
        </ol>
    </div>
    <hr />
    <div style="font-size: 0.6em; text-align:center">
        PixCob #{{ str_pad($cob->codpixcob, 8, '0', STR_PAD_LEFT) }} |
        LocationId ({{ $cob->locationid }}) |
        {{ $cob->PixCobStatus->pixcobstatus }}
    </div>
    <hr />
    <div style="font-size: 0.6em; text-align:center">
        {{ $cob->txid }}
    </div>
    <hr style="margin-bottom: 30px" />
</body>
</html>
