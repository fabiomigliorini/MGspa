// Receber título / Pagar vale no PDV (M7 do plano doc-3, no M6.1): busca os títulos abertos e
// os créditos da pessoa, o caixa escolhe e paga pelo wizard de cobrança de @components; a baixa
// (formas, finalização) é a mesma do contas, no baixaTitulosStore. Vale colaborador e
// adiantamentos (M8) usam o mesmo wizard e as mesmas formas lançadas.
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { negocioStore } from 'stores/negocio'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { calcularJurosMulta } from '@components/cobranca/juros.js'
import { db } from 'boot/db'

const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

// formas que o caixa usa: recebe o que se confirma na hora; paga vale em dinheiro ou
// registrando a devolução no cartão/PIX (só Gerente, o servidor confere)
const FORMAS = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque'],
  saida: ['dinheiro', 'estorno'],
}

// vale colaborador e adiantamentos (M8): título que já nasce com o dinheiro; vale e adiantamento a
// fornecedor saem da gaveta em dinheiro, adiantamento de cliente entra como no Receber título
export const TIPOS_LANCAMENTO = [
  { value: 2, label: 'Vale Colaborador', codcontacontabil: 42, entrada: false },
  { value: 120, label: 'Adiantamento a Fornecedor', codcontacontabil: 1, entrada: false },
  { value: 220, label: 'Adiantamento de Cliente', codcontacontabil: 2, entrada: true },
]
const FORMAS_LANCAMENTO = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque'],
  saida: ['dinheiro'],
}

const daquiA30Dias = () => {
  const d = new Date()
  d.setDate(d.getDate() + 30)
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const dia = String(d.getDate()).padStart(2, '0')
  return `${d.getFullYear()}-${mes}-${dia}`
}

const lancamentoVazio = () => ({
  codtipotitulo: 2,
  codpessoa: null,
  valor: null,
  codcontacontabil: 42,
  vencimento: daquiA30Dias(),
  observacao: null,
})

const avisar = (error) => {
  console.log(error)
  Notify.create({
    type: 'negative',
    message: error?.response?.data?.message ?? error?.message ?? String(error),
    timeout: 5000,
    actions: [{ icon: 'close', color: 'white' }],
  })
}

export const pagamentoStore = defineStore('pagamento', {
  state: () => ({
    dialog: false,
    etapa: 'busca', // busca → titulos
    codpessoa: null,
    numero: null,
    pessoa: null,
    titulos: [], // abertos da pessoa, com `selecionado`, juros e multa calculados
    buscando: false,
    // Vale / Adiantamento (M8)
    dialogLancamento: false,
    lancamento: lancamentoVazio(),
  }),

  getters: {
    selecionados() {
      return this.titulos.filter((t) => t.selecionado)
    },
    tipoLancamento() {
      return TIPOS_LANCAMENTO.find((t) => t.value === this.lancamento.codtipotitulo)
    },
  },

  actions: {
    abrir(codpessoa = null) {
      this.etapa = 'busca'
      this.codpessoa = codpessoa && codpessoa != 1 ? codpessoa : null
      this.numero = null
      this.pessoa = null
      this.titulos = []
      this.dialog = true
    },

    // pela pessoa ou pelo número de um título dela; os a receber já vêm marcados
    async buscar() {
      if (!this.codpessoa && !this.numero) {
        return
      }
      this.buscando = true
      try {
        const { data } = await api.get('/v1/pdv/pagamento/titulos', {
          params: {
            pdv: sincronizacaoStore().pdv.uuid,
            codpessoa: this.codpessoa || null,
            numero: this.codpessoa ? null : this.numero,
          },
        })
        this.pessoa = data.data.pessoa
        this.codpessoa = data.data.pessoa.codpessoa
        this.titulos = data.data.titulos.map((t) => {
          const { juros, multa } = calcularJurosMulta(t)
          return {
            ...t,
            juros,
            multa,
            desconto: 0,
            total: arredonda(t.saldo + juros + multa),
            selecionado: t.operacao === 'DB',
          }
        })
        this.etapa = 'titulos'
      } catch (error) {
        avisar(error)
      } finally {
        this.buscando = false
      }
    },

    // marcados vão para a baixa; o wizard abre com o que falta
    async receber() {
      const sBaixa = baixaTitulosStore()
      if (!sBaixa.pagamentos.length) {
        sBaixa.iniciar({ pessoa: this.pessoa, titulos: this.selecionados.map((t) => ({ ...t })) })
      }
      if (sBaixa.compensacao) {
        avisar('Os títulos se anulam: faça o encontro de contas pelo financeiro.')
        return
      }
      const sNegocio = negocioStore()
      sBaixa.abrirWizard({
        formas: FORMAS,
        padrao: sNegocio.padrao,
        // filial e maquinetas do estoque local configurado no PDV
        contexto: await sNegocio.contextoCobranca(sNegocio.padrao.codestoquelocal),
      })
    },

    // grava no servidor, imprime o recibo e recomeça
    async finalizar() {
      const sSinc = sincronizacaoStore()
      const pags = await baixaTitulosStore().finalizar('/v1/pdv/pagamento', { pdv: sSinc.pdv.uuid })
      if (!pags) {
        return false
      }
      Notify.create({
        type: 'positive',
        message: 'Pagamento registrado!',
        color: 'green-5',
        icon: 'done',
        timeout: 2000,
      })
      await this.imprimirRecibo(pags.map((p) => p.codpagamento))
      this.dialog = false
      return pags
    },

    abrirLancamento() {
      this.lancamento = lancamentoVazio()
      baixaTitulosStore().iniciar({ pessoa: null, titulos: [] })
      this.dialogLancamento = true
    },

    trocarTipoLancamento(codtipotitulo) {
      this.lancamento.codtipotitulo = codtipotitulo
      this.lancamento.codcontacontabil = this.tipoLancamento.codcontacontabil
    },

    // o título ainda não existe: entra na baixa como uma linha só, no sentido do tipo, para o
    // wizard e as cobranças integradas funcionarem como no Receber título
    async cobrarLancamento() {
      const sBaixa = baixaTitulosStore()
      if (!sBaixa.pagamentos.length) {
        const pessoa = await db.pessoa.get(this.lancamento.codpessoa)
        const valor = arredonda(this.lancamento.valor)
        sBaixa.iniciar({
          pessoa: { codpessoa: this.lancamento.codpessoa, fantasia: pessoa?.fantasia },
          titulos: [
            {
              codtitulo: null,
              operacao: this.tipoLancamento.entrada ? 'DB' : 'CR',
              saldo: valor,
              juros: 0,
              multa: 0,
              desconto: 0,
              total: valor,
            },
          ],
        })
      }
      const sNegocio = negocioStore()
      sBaixa.abrirWizard({
        formas: FORMAS_LANCAMENTO,
        padrao: sNegocio.padrao,
        contexto: await sNegocio.contextoCobranca(sNegocio.padrao.codestoquelocal),
      })
    },

    // um título por forma lançada; pagou menos (cobrança integrada de parte), lança o que pagou
    async finalizarLancamento() {
      const sBaixa = baixaTitulosStore()
      if (sBaixa.finalizando || !sBaixa.pagamentos.length) {
        return false
      }
      sBaixa.finalizando = true
      try {
        const { data } = await api.post('/v1/pdv/titulo', {
          pdv: sincronizacaoStore().pdv.uuid,
          codtipotitulo: this.lancamento.codtipotitulo,
          codpessoa: this.lancamento.codpessoa,
          codcontacontabil: this.lancamento.codcontacontabil,
          vencimento: this.lancamento.vencimento,
          observacao: this.lancamento.observacao,
          pagamentos: sBaixa.pagamentos.map((p) => {
            const forma = { ...p }
            delete forma.descricao
            return forma
          }),
        })
        Notify.create({
          type: 'positive',
          message: this.tipoLancamento.label + ' lançado!',
          color: 'green-5',
          icon: 'done',
          timeout: 2000,
        })
        await this.imprimirRecibo(data.data.map((p) => p.codpagamento))
        this.dialogLancamento = false
        return data.data
      } catch (error) {
        avisar(error)
        return false
      } finally {
        sBaixa.finalizando = false
      }
    },

    async imprimirRecibo(codpagamentos) {
      const impressora = negocioStore().padrao.impressora
      if (!impressora) {
        return
      }
      try {
        await api.post('/v1/pdv/pagamento/recibo/' + impressora, {
          pdv: sincronizacaoStore().pdv.uuid,
          codpagamento: codpagamentos,
        })
      } catch (error) {
        avisar(error)
      }
    },
  },
})
