// codpessoa do "Consumidor", usado como "cliente não informado"
export const CONSUMIDOR = 1

// Só há conflito quando os dois lados têm a informação preenchida e ela diverge.
// Quando um dos lados está vazio (ou no Consumidor), o backend resolve sozinho.
export const conflitosComanda = (negocio, comanda) => {
  return {
    pessoa:
      negocio.codpessoa != CONSUMIDOR &&
      comanda.codpessoa != CONSUMIDOR &&
      negocio.codpessoa != comanda.codpessoa,
    vendedor:
      !!negocio.codpessoavendedor &&
      !!comanda.codpessoavendedor &&
      negocio.codpessoavendedor != comanda.codpessoavendedor,
  }
}
