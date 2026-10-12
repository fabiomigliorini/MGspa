// Portadores que o contas oferece nas formas do wizard (vêm do contexto, já restritos às filiais
// do usuário): os ativos dos tipos pedidos, sem gaveta de PDV (no contas não se baixa em gaveta),
// os da filial do documento primeiro.
import { logo } from './logos.js'

export const opcoesPortador = (sCobranca, tipos) => {
  const codfilial = sCobranca.contexto?.codfilial
  const lista = (sCobranca.contexto?.portadores ?? [])
    .filter((p) => tipos.includes(p.tipo) && !p.gaveta && !p.inativo)
    .sort((a, b) => {
      const fa = a.codfilial == codfilial ? 0 : 1
      const fb = b.codfilial == codfilial ? 0 : 1
      return fa - fb || String(a.portador).localeCompare(String(b.portador))
    })
  const agrupar =
    lista.some((p) => p.codfilial == codfilial) && lista.some((p) => p.codfilial != codfilial)
  return lista.map((p, i) => ({
    tecla: i < 9 ? i + 1 : null,
    valor: p.codportador,
    label: p.portador,
    caption: [p.filial, p.banco, p.conta ? `${p.conta}-${p.contadigito ?? ''}` : null]
      .filter(Boolean)
      .join(' · '),
    logo: p.codbanco ? logo(`/bancos/${p.codbanco}.svg`) : null,
    icone: p.tipo === 'E' ? 'savings' : p.tipo === 'C' ? 'credit_card' : 'account_balance',
    cor: 'blue-8',
    grupo: agrupar ? (p.codfilial == codfilial ? 'Desta filial' : 'Outras filiais') : null,
  }))
}
