<!DOCTYPE html>
<html>
<head>
  <title>NFS-e {{ $nfs->Filial->Pessoa->fantasia }}</title>
</head>
<style>
body {
  font-family: Roboto, Helvetica, Arial, sans-serif;
}
</style>
<body>
  <table>
    <tbody>
      <tr>
        <td>
          <img src="{{ $message->embed(public_path('MailNfeCabecalho.jpeg')) }}" style="max-width:100%" alt="{{ $nfs->Filial->Pessoa->fantasia }}"> <br />
        </td>
      </tr>
      <tr>
        <td>
          <h2>Olá {{ $nfs->Pessoa?->fantasia }},</h2>

          <p>
            Segue em anexo o arquivo XML da Nota Fiscal de Serviço Eletrônica
            número <b>{{ $nfs->nfsenumero }}</b>,
            serviço <b>{{ $codigoServico }} - {{ $nfs->TributacaoNacional?->descricao }}</b>,
            no valor de <b>R$ {{ number_format($nfs->valorservico, 2, ',', '.') }}</b>,
            chave <b>{{ $nfs->nfsechave }}</b>,
            autorizada em <b>{{ $nfs->nfseautorizacao?->isoFormat('DD [de] MMMM [de] YYYY (dddd)') }}</b>,
            e o seu respectivo DANFSe.
          </p>

          <p>
            Esta <b>mensagem é gerada automaticamente</b> pelo nosso sistema, por favor não responda.
          </p>

          <p>
            Caso queira entrar em contato conosco:
            <ul>
              <li>
                <b>{{ $nfs->Filial->Pessoa->fantasia }}</b><br />
                <a href="mailto:{{ $nfs->Filial->Pessoa->email }}">{{ $nfs->Filial->Pessoa->email }}</a><br />
                <a href="tel:{{ $nfs->Filial->Pessoa->telefone1 }}">{{ $nfs->Filial->Pessoa->telefone1 }}</a>
              </li>
              <li>
                <b>Departamento Administrativo:</b> <br />
                <a href="mailto:cobranca@mgpapelaria.com.br">cobranca@mgpapelaria.com.br</a> <br />
                <a href="tel:+556635327678">(66)3532-7678</a>
              </li>
            </ul>
          </p>
          <p>
            Atenciosamente,
          </p>
          @if (!empty($nfs->UsuarioCriacao?->Pessoa))
            <div><b><font face="arial, helvetica, sans-serif">
              {{ $nfs->UsuarioCriacao->Pessoa->pessoa }}
            </font></b></div>
          @endif
          <div><font face="arial, helvetica, sans-serif"><b>{{ $nfs->Filial->Pessoa->fantasia }}</b></font></div>
          <div><font face="arial, helvetica, sans-serif"><a href="mailto:{{ $nfs->Filial->Pessoa->email }}" target="_blank">{{ $nfs->Filial->Pessoa->email }}</a></font></div>
          <div><font face="arial, helvetica, sans-serif"><a href="tel:{{ $nfs->Filial->Pessoa->telefone1 }}" target="_blank">{{ $nfs->Filial->Pessoa->telefone1 }}</a></font></div>
        </td>
      </tr>
    </tbody>
  </table>
</body>
</html>
