// Leitura da confissão de dívida assinada (notinha da venda a prazo): foto → QR (ou OCR) → venda
// achada → anexo da venda, que marca `tblnegocio.confissao`. O mesmo no PDV e no contas (M9 doc-3,
// conferência da duplicata): rotas `v1/negocio/...`; o PDV manda `fixos: { pdv }`.
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'src/services/api'

const aviso = (ok, message) =>
  Notify.create({
    type: ok ? 'positive' : 'negative',
    message,
    timeout: 3000,
    actions: [{ icon: 'close', color: 'white' }],
  })

export const confissaoStore = defineStore('confissao', {
  state: () => ({
    fixos: {},
    imagem: null,
    ratio: null,
    codnegocio: null,
    valor: null,
    encontrados: null,
    enviando: false,
  }),

  actions: {
    configurar({ fixos = {} } = {}) {
      this.fixos = fixos
    },

    limpar() {
      this.codnegocio = null
      this.valor = null
      this.encontrados = null
    },

    async novaImagem(imagem, ratio) {
      this.imagem = imagem
      this.ratio = ratio
      this.limpar()
      await this.sugerir()
    },

    descartar() {
      this.imagem = null
      this.limpar()
    },

    resultado(data) {
      this.codnegocio = data.codnegocio
      this.valor = data.valor
      this.encontrados = data.encontrados
      aviso(
        this.encontrados == 1,
        this.encontrados == 1 ? 'Venda localizada!' : 'Venda não localizada!',
      )
    },

    async sugerir() {
      try {
        const { data } = await api.post('v1/negocio/anexo/sugerir', {
          ...this.fixos,
          anexoBase64: this.imagem,
        })
        this.resultado(data)
      } catch (error) {
        aviso(false, error.response?.data?.message || error.message)
      }
    },

    async procurar() {
      try {
        const { data } = await api.post('v1/negocio/anexo/procurar', {
          ...this.fixos,
          codnegocio: this.codnegocio,
          valor: this.valor,
        })
        this.resultado(data)
      } catch (error) {
        aviso(false, error.response?.data?.message || error.message)
      }
    },

    // anexa a imagem à venda; devolve a listagem de anexos (ou null)
    async enviar(codnegocio, pasta, ratio, anexoBase64) {
      this.enviando = true
      try {
        const { data } = await api.post(`v1/negocio/${codnegocio}/anexo`, {
          ...this.fixos,
          pasta,
          ratio,
          anexoBase64,
        })
        aviso(true, 'Anexo adicionado!')
        return data
      } catch (error) {
        aviso(false, error.response?.data?.message || error.message)
        return null
      } finally {
        this.enviando = false
      }
    },

    async enviarConfissao() {
      const codnegocio = this.codnegocio
      const ret = await this.enviar(codnegocio, 'confissao', this.ratio, this.imagem)
      if (ret) {
        this.descartar()
        return codnegocio
      }
      return null
    },
  },
})
