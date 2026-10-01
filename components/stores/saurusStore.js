import { defineStore } from 'pinia'
import { api } from 'src/services/api'
import { Notify } from 'quasar'
import { avisar } from '../cobranca/eventos.js'

// Cobrança na maquineta SafraPay/Saurus do wizard
export const saurusStore = defineStore('saurus', {
  state: () => ({
    pedido: {},
    dialog: {
      detalhesPedido: false,
    },
  }),

  actions: {
    // silencioso = consulta automática (sem Notify)
    async consultarPedido(silencioso = false) {
      try {
        const { data } = await api.post(
          '/v1/pdv/saurus/pedido/' + this.pedido.codsauruspedido + '/consultar',
        )
        this.pedido = data.data
        await this.atualizarSaurusPedido()
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

    async reenviarPedido() {
      try {
        await api.get('/v1/pdv/saurus/pedido/' + this.pedido.codsauruspedido + '/reenviar')
        Notify.create({
          type: 'positive',
          message: 'Reenvio Efetuado!',
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

    async cancelarPedido() {
      try {
        const { data } = await api.delete('/v1/pdv/saurus/pedido/' + this.pedido.codsauruspedido)
        this.pedido = data.data
        await this.atualizarSaurusPedido()
        Notify.create({
          type: 'positive',
          message: 'Cancelamento Efetuado!',
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

    // o pedido é de um documento (hoje o negócio): quem estiver com ele aberto recarrega
    async atualizarSaurusPedido() {
      avisar('cobrancaAtualizada', { codnegocio: this.pedido.codnegocio })
    },
  },
})
