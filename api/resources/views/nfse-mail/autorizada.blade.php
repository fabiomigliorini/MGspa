<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>NFS-e {{ $nfs->nfsenumero }} {{ $nfs->Filial->Pessoa->fantasia }}</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#ffffff; border-radius:8px; font-family:-apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#212121;">
          <tr>
            <td style="padding:32px 32px 0;">
              <div style="font-size:13px; color:#757575;">{{ $nfs->Filial->Pessoa->fantasia }}</div>
              <div style="margin-top:4px; font-size:22px; font-weight:600;">NFS-e {{ $nfs->nfsenumero }}</div>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 32px 8px; font-size:15px; line-height:1.5;">
              <p style="margin:0 0 12px;">Olá{{ $nfs->Pessoa ? ', ' . $nfs->Pessoa->fantasia : '' }}.</p>
              <p style="margin:0;">Segue em anexo a Nota Fiscal de Serviço Eletrônica: o DANFSe (PDF) e o XML.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; line-height:1.4;">
                <tr>
                  <td style="padding:10px 0; border-top:1px solid #eeeeee; color:#757575; width:120px; vertical-align:top;">Valor</td>
                  <td style="padding:10px 0; border-top:1px solid #eeeeee;">R$ {{ number_format($nfs->valorservico, 2, ',', '.') }}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0; border-top:1px solid #eeeeee; color:#757575; vertical-align:top;">Serviço</td>
                  <td style="padding:10px 0; border-top:1px solid #eeeeee;">{{ $codigoServico }} - {{ $nfs->TributacaoNacional?->descricao }}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0; border-top:1px solid #eeeeee; color:#757575; vertical-align:top;">Autorizada em</td>
                  <td style="padding:10px 0; border-top:1px solid #eeeeee;">{{ $nfs->nfseautorizacao?->format('d/m/Y') }}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0; border-top:1px solid #eeeeee; border-bottom:1px solid #eeeeee; color:#757575; vertical-align:top;">Chave</td>
                  <td style="padding:10px 0; border-top:1px solid #eeeeee; border-bottom:1px solid #eeeeee; font-family:Menlo, Consolas, monospace; font-size:12px; word-break:break-all;">{{ $nfs->nfsechave }}</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 32px 32px; font-size:13px; line-height:1.6; color:#757575;">
              Mensagem automática, por favor não responda.<br>
              Fale com a {{ $nfs->Filial->Pessoa->fantasia }}:
              <a href="mailto:{{ $nfs->Filial->Pessoa->email }}" style="color:#1976d2; text-decoration:none;">{{ $nfs->Filial->Pessoa->email }}</a>
              @if (!empty($nfs->Filial->Pessoa->telefone1))
                · <a href="tel:{{ $nfs->Filial->Pessoa->telefone1 }}" style="color:#1976d2; text-decoration:none;">{{ $nfs->Filial->Pessoa->telefone1 }}</a>
              @endif
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
