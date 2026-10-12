import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'
import { Notify } from 'quasar'

export const pdvStore = defineStore('pdv', {
  state: () => ({
    dispositivos: [],
  }),

  actions: {
    async getDispositivos(filtro = {}) {
      try {
        const params = Object.fromEntries(
          Object.entries(filtro).filter(([, v]) => v != null && v !== ''),
        )
        const { data } = await api.get('/v1/pdv/dispositivo', { params })
        this.dispositivos = data.data
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

    async findByUuid(uuid) {
      if (this.dispositivos.length == 0) {
        await this.getDispositivos()
      }
      return this.dispositivos.find((el) => {
        return el.uuid == uuid
      })
    },
  },
})
