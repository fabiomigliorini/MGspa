<!DOCTYPE html>
<html>
<head>
  <title>NFe MG Papelaria</title>
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
          <!-- <img src="{{ asset('MailNfeCabecalho.jpeg') }}" style="max-width:100%"> -->
          <img src="{{ $message->embed('/var/www/MGspa/mg-laravel-5.5-a/public/MailNfeCabecalho.jpeg') }}" style="max-width:100%" alt="{{ $nf->Filial->Pessoa->fantasia }}"> <br />
        </td>
      </tr>
      <tr>
        <td>
          <h2>Olá {{ $nf->Pessoa->fantasia }},</h2>

          <p>
            Segue em anexo, o arquivo XML da Nota Fiscal Eletrônica
            de <b>{{ $nf->NaturezaOperacao->naturezaoperacao }}</b>,
            número <b>{{ $nf->numero }}</b>,
            série <b>{{ $nf->serie }}</b>,
            chave <b>{{ formataChaveNfe($nf->nfechave) }}</b>,
            autorizada em <b>{{ $nf->nfedataautorizacao->formatLocalized('%d de %B de %Y (%A)') }}</b>,
            e a sua respectiva DANFe.
          </p>

          <p>
            Esta <b>mensagem é gerada automaticamente</b> pelo nosso sistema, por favor não responda.
          </p>

          <p>
            Caso querira entrar em contato conosco, você pode optar por:
            <ul>
              <li>
                <b>{{ $nf->Filial->Pessoa->fantasia }}</b><br />
                <a href="mailto:{{ $nf->Filial->Pessoa->email }}">{{ $nf->Filial->Pessoa->email }}</a><br />
                <a href="tel:{{ $nf->Filial->Pessoa->telefone1 }}">{{ $nf->Filial->Pessoa->telefone1 }}</a>
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
            @if (!empty($nf->codusuariocriacao))
              @if (!empty($nf->UsuarioCriacao->codpessoa))
                <strong>{{ $nf->UsuarioCriacao->Pessoa->pessoa }}</strong>
              @endif
            @endif
            <br />
            <small>
              <a href="tel:{{ $nf->Filial->Pessoa->telefone1 }}">{{ $nf->Filial->Pessoa->telefone1 }}</a>
              <br />
              <a href="mailto:{{ $nf->Filial->Pessoa->email }}">{{ $nf->Filial->Pessoa->email }}</a>
              <br />
              <a href="http://www.mgpapelaria.com.br">
                www.mgpapelaria.com.br
              </a>
              <br />
              <a href="https://www.facebook.com/MGPapelaria/">facebook.com/MGPapelaria</a>
              <br />
              <a href="https://maps.google.com/?q={{$nf->Filial->Pessoa->endereco }} {{$nf->Filial->Pessoa->numero }} {{$nf->Filial->Pessoa->Cidade->cidade }} {{$nf->Filial->Pessoa->Cidade->Estado->sigla }} {{$nf->Filial->Pessoa->Cidade->Estado->Pais->pais }}">
                {{ $nf->Filial->Pessoa->endereco }}, {{ $nf->Filial->Pessoa->numero }} -
                @if (!empty($nf->Filial->Pessoa->complemento))
                {{ $nf->Filial->Pessoa->complemento }} -
                @endif
                {{ $nf->Filial->Pessoa->Cidade->cidade }} / {{ $nf->Filial->Pessoa->Cidade->Estado->sigla }} -
                {{ mascarar($nf->Filial->Pessoa->cep, '##.###-###') }}<br />
              </a>
            </small>
          <p>
        </td>
      </p>
      </tr>
    </tbody>
  </table>
</body>
</html>
