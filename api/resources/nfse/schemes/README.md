# Schemas da NFS-e Nacional

`1.01/` = `nfse-esquemas_xsd-v1-01-20260209.zip` do portal nfse.gov.br
(Documentação técnica → Documentação atual), usados pelo
`Mg\NotaFiscalServico\NfseSefinService::validar` antes de cada envio (TASK-279).

## Alteração local

- `tiposSimples_v1.01.xsd`, `TSSerieDPS`: o oficial traz `^0{0,4}\d{1,5}$`. Em
  expressão regular de XSD `^` e `$` não são âncoras, são caracteres literais, e o
  libxml recusa qualquer série. Ficou `0{0,4}\d{1,5}` (o padrão já casa a string
  inteira). Ao trocar os schemas por uma versão nova, conferir se o portal corrigiu.

## Dois leiautes

- `1.01/`: o "atual" do portal (produção).
- `restrita/`: `esquemas_prod_restrita_20261020.zip` (Documentação técnica → Produção
  restrita), o leiaute da reforma tributária que a homologação já exige: `finNFSe`
  obrigatório depois do `tpEmit`, `xDescServ` no fim do `cServ`, `vAjusteBC` no lugar
  do `vDedRed`.

A homologação usa sempre `restrita/`. A produção usa `1.01/` até o `.env` ligar
`NFSE_LEIAUTE_RTC=true` (quando o portal anunciar a troca em produção). Ver
`NfseSefinService::leiauteRtc` e `NfseXmlService::dps`.
