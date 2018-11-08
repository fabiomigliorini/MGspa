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
          <img src="{{ $message->embed('/opt/www/MGspa/laravel/public/MailNfeCabecalho.jpeg') }}" style="max-width:100%" alt="{{ $nf->Filial->Pessoa->fantasia }}"> <br />
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
              <div><b><font face="arial, helvetica, sans-serif">
                {{ $nf->UsuarioCriacao->Pessoa->pessoa }}
              </b></font></div>
            @endif
          @endif
	  <div><font face="arial, helvetica, sans-serif" style="background-color:rgb(255,255,0)"><b><font color="#ff0000">&nbsp;MG</font>&nbsp;<font color="#0000ff">Papelaria&nbsp;</font></b><br></font></div>
	  <div><font face="arial, helvetica, sans-serif"><a href="mailto:{{ $nf->Filial->Pessoa->email }}" target="_blank">{{ $nf->Filial->Pessoa->email }}</a></font></div>
	  <div><font face="arial, helvetica, sans-serif"><a href="tel:{{ $nf->Filial->Pessoa->telefone1 }}" target="_blank">{{ $nf->Filial->Pessoa->telefone1 }}</a></font></div>
	  <div><font face="arial, helvetica, sans-serif"><a href="http://www.mgpapelaria.com.br/" target="_blank">www.mgpapelaria.com.br</a></font></div>
          <div><font face="arial, helvetica, sans-serif"><a href="https://facebook.com/MGPapelaria" target="_blank">facebook.com/MGPapelaria</a></font></div>
          <div><font face="arial, helvetica, sans-serif"><a href="https://instagram.com/MGPapelaria" target="_blank">instagram.com/MGPapelaria</a></font></div>
          <div>
            <font face="arial, helvetica, sans-serif">
              <a href="https://maps.google.com/?q={{$nf->Filial->Pessoa->endereco }} {{$nf->Filial->Pessoa->numero }} {{$nf->Filial->Pessoa->Cidade->cidade }} {{$nf->Filial->Pessoa->Cidade->Estado->sigla }} {{$nf->Filial->Pessoa->Cidade->Estado->Pais->pais }}">
                {{ $nf->Filial->Pessoa->endereco }}, {{ $nf->Filial->Pessoa->numero }} -
                @if (!empty($nf->Filial->Pessoa->complemento))
                  {{ $nf->Filial->Pessoa->complemento }} -
                @endif
                {{ $nf->Filial->Pessoa->Cidade->cidade }} / {{ $nf->Filial->Pessoa->Cidade->Estado->sigla }} -
                {{ mascarar($nf->Filial->Pessoa->cep, '##.###-###') }}<br />
              </a>
            </font>
          </div>
        </td>
      </p>
      </tr>
    </tbody>
  </table>
</body>
</html>
