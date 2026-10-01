import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'
import { Notify } from 'quasar'
import emitter from '../utils/emitter.js'

export const pixStore = defineStore('pix', {
  state: () => ({
    pixCob: {},
    dialog: {
      detalhesPixCob: false,
    },
    portadoresPorFilial: {},
  }),

  actions: {
    async carregarPortadores(codfilial) {
      if (this.portadoresPorFilial[codfilial]) {
        return this.portadoresPorFilial[codfilial]
      }
      try {
        const { data } = await api.get('/v1/select/portador', {
          params: {
            somentePix: true,
            codfilial: codfilial,
          },
        })
        data.sort((a, b) => a.codportador - b.codportador)
        this.portadoresPorFilial[codfilial] = data
        return data
      } catch (error) {
        console.log(error)
        return []
      }
    },

    async transmitirPixCob() {
      try {
        const { data } = await api.post('/v1/pix/cob/' + this.pixCob.codpixcob + '/transmitir')
        this.pixCob = data.data
        await this.atualizarPixCobNegocio()
        Notify.create({
          type: 'positive',
          message: 'Cobrança PIX Transmitida ao Banco!',
          timeout: 1000, // 1 segundo
          actions: [{ icon: 'close', color: 'white' }],
        })
      } catch (error) {
        console.log(error)
        var message = error?.response?.data?.message
        if (!message) {
          message = error?.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    // silencioso = consulta automática (sem Notify)
    async consultarPixCob(silencioso = false) {
      try {
        const { data } = await api.post('/v1/pix/cob/' + this.pixCob.codpixcob + '/consultar')
        this.pixCob = data.data
        await this.atualizarPixCobNegocio()
        if (!silencioso) {
          Notify.create({
            type: 'positive',
            message: 'Consulta Efetuada!',
            timeout: 1000, // 1 segundo
            actions: [{ icon: 'close', color: 'white' }],
          })
        }
        return true
      } catch (error) {
        console.log(error)
        if (!silencioso) {
          var message = error?.response?.data?.message
          if (!message) {
            message = error?.message
          }
          Notify.create({
            type: 'negative',
            message: message,
            timeout: 3000, // 3 segundos
            actions: [{ icon: 'close', color: 'white' }],
          })
        }
        return false
      }
    },

    async imprimirPixCob(impressora) {
      if (!impressora) {
        Notify.create({
          type: 'negative',
          message: 'Nenhuma impressora térmica selecionada!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      try {
        await api.post('/v1/pix/cob/' + this.pixCob.codpixcob + '/imprimir-qr-code', {
          impressora,
        })
        Notify.create({
          type: 'positive',
          message: 'Impressão solicitada!',
          timeout: 1000, // 1 segundo
          actions: [{ icon: 'close', color: 'white' }],
        })
      } catch (error) {
        console.log(error)
        var message = error?.response?.data?.message
        if (!message) {
          message = error?.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    // a cobrança é de um documento (hoje o negócio): quem estiver com ele aberto recarrega
    async atualizarPixCobNegocio() {
      emitter.emit('cobrancaAtualizada', {
        codnegocio: this.pixCob.codnegocio,
        pixCob: this.pixCob,
      })
    },
  },
})
