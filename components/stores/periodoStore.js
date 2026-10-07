// Período do portador (doc-4, redefinição do dinheiro): o que entrou e saiu de um portador num
// período e quanto sobrou. Domínio da tela /portador/{cod}/{codperiodo} do contas, da tela do caixa
// do PDV (negocios /caixa: o mesmo período, só o aberto da gaveta) e dos dialogs de movimento
// (@components/caixa: Transferir, Ajuste, Item, Maquineta), que servem qualquer portador (o item e
// a maquineta, só em espécie). Ajuste, transferência, item e borderô da maquineta são movimento do
// portador, não pagamento. Toda rota que muda devolve os períodos afetados (R14); o store troca os
// que tem pelo que veio.
//   contas:   carregar(codportador, codportadorperiodo)
//   negocios: carregar(gaveta do PDV, null, { codpdv, impressora }): com o codpdv o servidor não
//             valida o papel na gaveta e só deixa o que é do caixa (abrir, contar, sangria/reforço,
//             borderô da maquineta, cancelar o que ainda pode); quem fecha é o gerente, no contas
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { abrirPdf } from '@components/abrirPdf'
import { formataNumero, formataTimestamp } from '@components/formatters'

const avisar = (ok, message) =>
  Notify.create({
    type: ok ? 'positive' : 'negative',
    message,
    timeout: ok ? 2000 : 5000,
    actions: [{ icon: 'close', color: 'white' }],
  })

const erro = (error) => error?.response?.data?.message ?? error?.message ?? String(error)

// as linhas de um item do caixa (o item conta como cédula: preço × quantidade)
export const totalLinhas = (linhas) =>
  Math.round(
    (linhas || []).reduce((s, l) => s + (Number(l.quantidade) || 0) * (Number(l.preco) || 0), 0) *
      100,
  ) / 100

// as linhas para editar: as que já passaram pela gaveta (preço fixo), com a quantidade contada
export const linhasDoItem = (item, contadas) =>
  (item?.linhas || []).map((l) => ({
    preco: l.preco,
    descricao: l.descricao,
    quantidade:
      (contadas || []).find(
        (c) =>
          Number(c.preco) === Number(l.preco) && (c.descricao || null) === (l.descricao || null),
      )?.quantidade ?? null,
    nova: false,
  }))

// o que vai para a API: só as linhas com quantidade
export const linhasParaSalvar = (linhas) =>
  (linhas || [])
    .filter((l) => Number(l.quantidade) > 0)
    .map((l) => ({ preco: l.preco, descricao: l.descricao || null, quantidade: l.quantidade }))

// a data dos lançamentos do período da tela vai do início ao fim (aberto, até agora)
export const limitePeriodo = (periodo) =>
  periodo?.fim && new Date(periodo.fim) < new Date() ? new Date(periodo.fim) : new Date()

// regra do MgInputData da data: lê o valor do form (ISO), não o texto que o MgInputData passa às
// rules; vazio fica com o !!v
export const dentroDoPeriodo = (periodo, transacao) => {
  if (!periodo || !transacao) return true
  const d = new Date(transacao)
  const ate = limitePeriodo(periodo)
  return (
    (d >= new Date(periodo.inicio) && d <= ate) ||
    `Fora do período (de ${formataTimestamp(periodo.inicio, 0)} a ${formataTimestamp(ate, 0)})`
  )
}

// cédulas e moedas da contagem (as mesmas chaves do servidor, CaixaService::CEDULAS/MOEDAS)
export const CEDULAS = ['200', '100', '50', '20', '10', '5', '2']
export const MOEDAS = ['1', '0.50', '0.25', '0.10', '0.05', '0.01']

// taxa, tarifa e rendimento: só banco (pagamento sem pessoa); o ajuste é movimento do portador
export const MOTIVOS = [
  { value: 'T', label: 'Taxa' },
  { value: 'F', label: 'Tarifa' },
  { value: 'R', label: 'Rendimento' },
]

export const PAPEIS = [
  { value: 'D', label: 'Depositante', descricao: 'só manda dinheiro para ele' },
  { value: 'O', label: 'Operador', descricao: 'vê e movimenta' },
  { value: 'G', label: 'Gestor', descricao: 'também confirma, reabre e cuida da lista' },
]

export const periodoStore = defineStore('periodo', {
  state: () => ({
    // { codportador, portador, codfilial, tipo, ehCaixa (espécie), ehGaveta (com PDV), saldo, ... }
    portador: null,
    // o papel do usuário no portador (D, O, G) e o que ele pode
    papel: null,
    pode: {},
    // todos os períodos do portador, sem lançamentos (as abas)
    periodos: [],
    // o período da tela, com lançamentos, resumo e (espécie) as contagens
    periodo: null,
    // origem escolhida no resumo (V, T, I, M, X, J, A): filtra a lista de lançamentos
    filtroOrigem: null,
    carregando: false,
    salvando: false,
    // no caixa do PDV: o PDV (a gaveta dele) e a impressora térmica do borderô
    contexto: { codpdv: null, impressora: null },
    dialogTransferir: false,
    dialogAvulso: false,
    dialogItem: false,
    dialogMaquineta: false,
    dialogUsuarios: false,
    // codcaixaitem já escolhido ao abrir o dialog da entrada do item
    item: null,
    // a lista de usuários do portador (cadeado)
    usuarios: [],
  }),

  getters: {
    gestor: (state) => state.papel === 'G' && !state.contexto.codpdv,
    // a tela do caixa do PDV (negocios)
    pdv: (state) => !!state.contexto.codpdv,
  },

  actions: {
    async carregar(codportador, codportadorperiodo = null, contexto = null) {
      this.carregando = true
      this.contexto = { codpdv: null, impressora: null, ...contexto }
      try {
        const { data } = await api.get(
          `v1/portador/${codportador}/periodo` +
            (codportadorperiodo ? `/${codportadorperiodo}` : ''),
          { params: this.params() },
        )
        this.portador = {
          ...data.data.portador,
          ehGaveta: data.data.portador.gaveta,
          ehCaixa: data.data.portador.caixa,
        }
        this.papel = data.data.papel
        this.pode = data.data.pode
        this.periodos = data.data.periodos
        this.periodo = data.data.periodo
        this.filtroOrigem = null
      } catch (error) {
        this.portador = null
        avisar(false, erro(error))
      } finally {
        this.carregando = false
      }
    },

    // o codpdv nas rotas que o caixa do PDV usa (sem ele, nada)
    params() {
      return this.contexto.codpdv ? { codpdv: this.contexto.codpdv } : {}
    },

    // troca os períodos que vieram da rota (R14); o da tela também. `removido`: o período que
    // sumiu (unificado)
    aplicar(periodos, removido = null) {
      if (removido) {
        this.periodos = this.periodos.filter((p) => p.codportadorperiodo !== removido)
      }
      ;(periodos || []).forEach((p) => {
        if (p.codportador !== this.portador?.codportador) return
        const i = this.periodos.findIndex((x) => x.codportadorperiodo === p.codportadorperiodo)
        if (i >= 0) this.periodos.splice(i, 1, p)
        else this.periodos.push(p)
        if (p.codportadorperiodo === this.periodo?.codportadorperiodo) this.periodo = p
      })
      this.periodos.sort((a, b) =>
        a.inicio < b.inicio
          ? -1
          : a.inicio > b.inicio
            ? 1
            : a.codportadorperiodo - b.codportadorperiodo,
      )
      const ultimo = this.periodos[this.periodos.length - 1]
      // portador sem período: o primeiro movimento cria um, e a tela passa a mostrá-lo
      if (!this.periodo && ultimo?.lancamentos) this.periodo = ultimo
      if (this.portador && ultimo) this.portador.saldo = ultimo.saldofinal
    },

    async executar(fn, ok) {
      this.salvando = true
      try {
        const { data } = await fn()
        this.aplicar(data.periodos, data.removido)
        if (ok) avisar(true, typeof ok === 'function' ? ok(data.data) : ok)
        return data
      } catch (error) {
        avisar(false, erro(error))
        return null
      } finally {
        this.salvando = false
      }
    },

    // ==== transferência: sentido E sai deste portador, R chega nele ====

    transferir({ sentido, codportador, valor, observacoes, transacao }) {
      const meu = this.portador.codportador
      return this.executar(
        () =>
          api.post('v1/portador-movimento/transferencia', {
            codportadororigem: sentido === 'E' ? meu : codportador,
            codportadordestino: sentido === 'E' ? codportador : meu,
            valor,
            observacoes,
            transacao: transacao || null,
            codportadorperiodo: this.periodo?.codportadorperiodo ?? null,
            codpdv: this.contexto.codpdv,
          }),
        (mov) =>
          mov.estado === 'E'
            ? 'Transferência registrada'
            : 'Transferência registrada: a confirmar pelo gestor do destino',
      )
    },

    confirmarTransferencia(codportadormovimento) {
      return this.executar(
        () => api.post(`v1/portador-movimento/${codportadormovimento}/confirmar`),
        'Transferência confirmada',
      )
    },

    // ajuste ou transferência: só se cancela, com justificativa
    cancelarMovimento(codportadormovimento, justificativa) {
      return this.executar(
        () =>
          api.post(`v1/portador-movimento/${codportadormovimento}/cancelar`, {
            justificativa,
            codpdv: this.contexto.codpdv,
          }),
        'Lançamento cancelado',
      )
    },

    // ==== ajuste (qualquer portador) e taxa/tarifa/rendimento (banco) ====

    // valor com sinal: positivo entrou; no período da tela
    ajustar({ valor, observacoes, transacao }) {
      return this.executar(
        () =>
          api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/ajuste`, {
            valor,
            observacoes,
            transacao: transacao || null,
          }),
        'Ajuste lançado',
      )
    },

    lancarTaxa({ motivo, valor, observacoes, transacao }) {
      return this.executar(
        () =>
          api.post('v1/portador-periodo/lancamento', {
            codportador: this.portador.codportador,
            motivo,
            valor,
            transacao: transacao || null,
            observacoes,
          }),
        'Lançamento registrado',
      )
    },

    cancelarTaxa(codpagamento, justificativa) {
      return this.executar(
        () =>
          api.post(`v1/portador-periodo/lancamento/${codpagamento}/cancelar`, { justificativa }),
        'Lançamento cancelado',
      )
    },

    // ==== item do caixa (em espécie): entrada (+) ou saída (−); vender não lança ====

    abrirItem(codcaixaitem = null) {
      this.item = codcaixaitem
      this.dialogItem = true
    },

    lancarItem({ codcaixaitem, sinal, linhas, observacoes, transacao }) {
      return this.executar(
        () =>
          api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/item`, {
            codcaixaitem,
            sinal,
            linhas,
            observacoes: observacoes || null,
            transacao: transacao || null,
          }),
        sinal > 0 ? 'Entrada lançada' : 'Saída lançada',
      )
    },

    // ==== borderô da maquineta de parceiro (em espécie): o total em dinheiro do dia ====

    // valor com sinal: negativo devolveu dinheiro; a foto (base64) é opcional
    lancarMaquineta({ codcaixaitem, valor, observacoes, transacao, anexoBase64 }) {
      return this.executar(
        () =>
          api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/maquineta`, {
            codcaixaitem,
            valor,
            observacoes: observacoes || null,
            transacao: transacao || null,
            anexoBase64: anexoBase64 || null,
            codpdv: this.contexto.codpdv,
          }),
        anexoBase64 ? 'Borderô lançado' : 'Borderô lançado sem a foto: anexe depois',
      )
    },

    anexarFotoBordero(codportadormovimento, anexoBase64) {
      return this.executar(
        () =>
          api.post(`v1/portador-movimento/${codportadormovimento}/foto`, {
            anexoBase64,
            ...this.params(),
          }),
        'Foto do borderô anexada',
      )
    },

    // ==== período em espécie: abrir, contar, fechar, reabrir, datas, dividir, unificar ====

    // só abre; a contagem inicial é outro botão. Devolve o período novo (a tela vai para ele)
    // o início o servidor decide (o segundo seguinte ao fim do anterior; sem período, hoje 00:00)
    async abrir() {
      const data = await this.executar(
        () => api.post(`v1/portador/${this.portador.codportador}/periodo/abrir`, this.params()),
        'Período aberto',
      )
      return data?.data?.codportadorperiodo ?? null
    },

    // cédulas e moedas e os itens { codcaixaitem: [{ preco, descricao, quantidade }] }
    contar(momento, contagem, itens = null) {
      return this.executar(
        () =>
          api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/contagem`, {
            momento,
            contagem,
            ...(itens ? { itens } : {}),
            ...this.params(),
          }),
        'Contagem salva',
      )
    },

    // espécie: com a contagem final; dentro da tolerância fecha, acima fica pendente
    // só fecha, com a contagem final já informada. Acima da tolerância o servidor deixa pendente:
    // é erro para quem fechou
    async fechar() {
      const data = await this.executar(() =>
        api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/fechar`),
      )
      if (!data) return null
      const p = data.data
      if (p.situacao === 'fechado') {
        avisar(true, 'Período fechado')
      } else {
        avisar(
          false,
          `Diferença de R$ ${formataNumero(p.diferenca)} acima da tolerância (R$ ${formataNumero(p.tolerancia)}): o período ficou pendente até a correção`,
        )
      }
      return data
    },

    // banco: fechar com corte
    fecharCorte(corte) {
      return this.executar(
        () =>
          api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/fechar`, {
            corte: corte || null,
          }),
        'Período fechado',
      )
    },

    reabrir() {
      return this.executar(
        () => api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/reabrir`),
        'Período reaberto',
      )
    },

    editarDatas({ inicio, fim, observacoes }) {
      return this.executar(
        () =>
          api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/datas`, {
            inicio,
            fim,
            observacoes,
          }),
        'Salvo',
      )
    },

    // devolve a segunda parte
    async dividir(corte) {
      const data = await this.executar(
        () => api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/dividir`, { corte }),
        'Período dividido',
      )
      return data?.data?.codportadorperiodo ?? null
    },

    // devolve o anterior, que ficou com tudo
    async unificar() {
      const data = await this.executar(
        () => api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/unificar`),
        'Períodos unificados',
      )
      return data?.data?.codportadorperiodo ?? null
    },

    abrirBordero() {
      return abrirPdf(
        api,
        `v1/portador-periodo/${this.periodo.codportadorperiodo}/bordero`,
        this.params(),
        { title: 'Borderô', size: 'cupom' },
      )
    },

    // no PDV: na impressora térmica dele; sem impressora, o PDF
    async imprimirBordero() {
      if (!this.contexto.impressora) return this.abrirBordero()
      try {
        await api.post(
          `v1/portador-periodo/${this.periodo.codportadorperiodo}/bordero/${this.contexto.impressora}`,
          this.params(),
        )
        avisar(true, 'Borderô enviado para a impressora')
      } catch (error) {
        avisar(false, erro(error))
      }
    },

    // ==== usuários do portador e o papel de cada um (gestor) ====

    async buscarUsuarios() {
      try {
        const { data } = await api.get(`v1/portador/${this.portador.codportador}/usuario`)
        this.usuarios = data.data
      } catch (error) {
        this.usuarios = []
        avisar(false, erro(error))
      }
    },

    async salvarUsuario(codusuario, papel) {
      this.salvando = true
      try {
        const { data } = await api.post(`v1/portador/${this.portador.codportador}/usuario`, {
          codusuario,
          papel,
        })
        this.usuarios = data.data
        return true
      } catch (error) {
        avisar(false, erro(error))
        return false
      } finally {
        this.salvando = false
      }
    },

    async excluirUsuario(codportadorusuario) {
      this.salvando = true
      try {
        const { data } = await api.delete(
          `v1/portador/${this.portador.codportador}/usuario/${codportadorusuario}`,
        )
        this.usuarios = data.data
        return true
      } catch (error) {
        avisar(false, erro(error))
        return false
      } finally {
        this.salvando = false
      }
    },
  },
})
