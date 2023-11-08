<!DOCTYPE html>
<html>

<head>

  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>E-mail Confirmação MG Papelaria</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style type="text/css">
    @media screen {
      @font-face {
        font-family: 'Source Sans Pro';
        font-style: normal;
        font-weight: 400;
        src: local('Source Sans Pro Regular'), local('SourceSansPro-Regular'), url(https://fonts.gstatic.com/s/sourcesanspro/v10/ODelI1aHBYDBqgeIAH2zlBM0YzuT7MdOe03otPbuUS0.woff) format('woff');
      }

      @font-face {
        font-family: 'Source Sans Pro';
        font-style: normal;
        font-weight: 700;
        src: local('Source Sans Pro Bold'), local('SourceSansPro-Bold'), url(https://fonts.gstatic.com/s/sourcesanspro/v10/toadOcfmlt9b38dHJxOBGFkQc6VGVFSmCnC_l7QZG60.woff) format('woff');
      }
    }

    body,
    table,
    td,
    a {
      -ms-text-size-adjust: 100%;
      /* 1 */
      -webkit-text-size-adjust: 100%;
      /* 2 */
    }

    /**
   * Remove extra space added to tables and cells in Outlook.
   */
    table,
    td {
      mso-table-rspace: 0pt;
      mso-table-lspace: 0pt;
    }

    /**
   * Better fluid images in Internet Explorer.
   */
    img {
      -ms-interpolation-mode: bicubic;
    }

    /**
   * Remove blue links for iOS devices.
   */
    a[x-apple-data-detectors] {
      font-family: inherit !important;
      font-size: inherit !important;
      font-weight: inherit !important;
      line-height: inherit !important;
      color: inherit !important;
      text-decoration: none !important;
    }

    /**
   * Fix centering issues in Android 4.4.
   */
    div[style*="margin: 16px 0;"] {
      margin: 0 !important;
    }

    body {
      width: 100% !important;
      height: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    /**
   * Collapse table borders to avoid space between cells.
   */
    table {
      border-collapse: collapse !important;
    }

    a {
      color: #1a82e2;
    }

    img {
      height: auto;
      line-height: 100%;
      text-decoration: none;
      border: 0;
      outline: none;
    }
  </style>

</head>

<body style="background-color: #e9ecef;">

  <!-- start preheader -->
  <div class="preheader" style="display: none; max-width: 0; max-height: 0; overflow: hidden; font-size: 1px; line-height: 1px; color: #fff; opacity: 0;">
    Seu código de confirmação de e-mail
  </div>
  <!-- end preheader -->

  <!-- start body -->
  <table border="0" cellpadding="0" cellspacing="0" width="100%">

    <!-- start logo -->
    <tr>
      <td align="center" bgcolor="#e9ecef">

        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
          <tr>
            <td align="center" valign="top" style="padding: 36px 24px;">
              <a href="https://www.mgpapelaria.com.br" target="_blank" style="display: inline-block;">
                <img src="{{ $message->embed('/opt/www/MGspa/laravel/public/MGPapelariaLogo.jpeg') }}" alt="Logo" border="0" style="display: block;">
              </a>
            </td>
          </tr>
        </table>

      </td>
    </tr>
    <!-- end logo -->

    <!-- start hero -->
    <tr>
      <td align="center" bgcolor="#e9ecef">

        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
          <tr>
            <td align="center" bgcolor="#ffffff" style="padding: 36px 24px 0; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; border-top: 3px solid #d4dadf;">
              <h1 style="margin: 0; font-size: 32px; font-weight: 700; letter-spacing: -1px; line-height: 48px;"
              >Código de Verificação
            </h1>
            </td>
          </tr>
        </table>

      </td>
    </tr>

    <tr>
      <td align="center" bgcolor="#e9ecef">

        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">

          <tr>
            <td align="center" bgcolor="#ffffff" style="padding: 24px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
              <p style="margin: 0;">
                Para confirmar a verificação do endereço "{{ $email }}" informe o código abaixo ao atendente da MG Papelaria: 
              </p>
            </td>
          </tr>

          <tr>
            <td align="center" bgcolor="#ffffff" style="padding: 24px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 60px; font-weight: bold; letter-spacing: 0.5em; line-height: 24px;">
              <p style="margin-bottom: 60px;">{{$random}}</p>
            </td>
          </tr>
        </table>

      </td>
    </tr>


    <tr>
      <td align="center" bgcolor="#e9ecef" style="padding: 24px;">

        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">

          <!-- start permission -->
          <tr>
            <td align="center" bgcolor="#e9ecef" style="padding: 12px 24px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 20px; color: #666;">
              <p style="margin: 0;">
                Você recebeu este e-mail porque recebemos uma solicitação de verificação do endereço "{{ $email }}". 
                <br>Se você não solicitou essa verificação pode ignorar e excluir este e-mail.
              </p>
            </td>
          </tr>
          <!-- end permission -->

          <!-- start unsubscribe -->
          <tr>
            <td align="center" bgcolor="#e9ecef" style="padding: 12px 24px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 20px; color: #666;">
              <div>
                <font face="arial, helvetica, sans-serif" style="background-color:rgb(255,255,0)"><b>
                    <font color="#ff0000">&nbsp;MG</font>&nbsp;<font color="#0000ff">Papelaria&nbsp;</font>
                  </b><br></font>
              </div>
              <div>
                <font face="arial, helvetica, sans-serif"><a href="http://www.mgpapelaria.com.br/" target="_blank">www.mgpapelaria.com.br</a></font>
              </div>
              <div>
                <font face="arial, helvetica, sans-serif"><a href="https://facebook.com/MGPapelaria" target="_blank">facebook.com/MGPapelaria</a></font>
              </div>
              <div>
                <font face="arial, helvetica, sans-serif"><a href="https://instagram.com/MGPapelaria" target="_blank">instagram.com/MGPapelaria</a></font>
              </div>
            </td>
          </tr>
          <!-- end unsubscribe -->

        </table>

      </td>
    </tr>
    <!-- end footer -->

  </table>
  <!-- end body -->

</body>

</html>