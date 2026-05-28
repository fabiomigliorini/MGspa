<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comanda Vendedor</title>
    <style>
        @page { margin: 4mm; size: 80mm 60mm; }
        body { margin: 0; font-family: sans-serif; }
        .wrap { width: 72mm; text-align: center; }
        .logo { width: 60mm; margin: 0 auto 2mm; display: block; }
        .codigo { font-size: 14pt; font-weight: bold; margin: 1mm 0; }
        .nome { font-size: 11pt; font-weight: bold; margin: 1mm 0 3mm; }
        .barras { margin: 2mm 0; }
        .barras img { width: 64mm; height: 12mm; }
        .barras .legenda { font-size: 8pt; font-family: monospace; margin-top: 1mm; }
    </style>
</head>
<body>
    <div class="wrap">
        @if(!empty($logo))
            <img class="logo" src="{{ $logo }}" alt="MG Papelaria">
        @endif
        <div class="codigo">{{ $codigo }}</div>
        <div class="nome">{{ $fantasia }}</div>
        <div class="barras">
            <img src="data:image/png;base64,{{ $barcodePng }}" alt="{{ $codigoBarras }}">
            <div class="legenda">{{ $codigoBarras }}</div>
        </div>
    </div>
</body>
</html>
