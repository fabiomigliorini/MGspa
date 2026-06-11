import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'

// Cache de culturas por código, em memória (vive enquanto o app está aberto).
// Consumido SÓ pelo MgIconeCultura: a tela passa o codcultura, o componente
// pede aqui, busca uma vez (GET v1/cultura/{cod}) e nas próximas serve do cache.
// `emVoo` evita requisições duplicadas quando várias linhas pedem a mesma
// cultura ao mesmo tempo (fica fora do state, é só controle interno).
const emVoo = new Map()

export const useCulturaStore = defineStore('cultura', () => {
  const cache = ref({})

  async function buscar(codcultura) {
    if (!codcultura) return null
    if (cache.value[codcultura]) return cache.value[codcultura]
    if (emVoo.has(codcultura)) return emVoo.get(codcultura)

    const promessa = api
      .get(`v1/cultura/${codcultura}`)
      .then(({ data }) => {
        cache.value[codcultura] = data
        return data
      })
      .finally(() => emVoo.delete(codcultura))

    emVoo.set(codcultura, promessa)
    return promessa
  }

  return { cache, buscar }
})
