import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { useAuthStore } from 'src/stores/auth'
import { notifyError } from 'src/utils/notify'

// Painel da filial (TASK-203): tudo pendente na filial, de qualquer usuário. Só mostra; cada
// item leva à tela que resolve. Rota v1/filial/{codfilial}/painel.

export const usePainelStore = defineStore(
  'painel',
  () => {
    const codfilial = ref(useAuthStore().usuario?.codfilial ?? null)
    const dados = ref(null)
    const carregando = ref(false)

    async function buscar() {
      if (!codfilial.value) {
        dados.value = null
        return
      }
      carregando.value = true
      try {
        const { data } = await api.get(`v1/filial/${codfilial.value}/painel`)
        dados.value = data
      } catch (e) {
        notifyError(e, 'Erro ao buscar o painel')
      } finally {
        carregando.value = false
      }
    }

    return { codfilial, dados, carregando, buscar }
  },
  {
    persist: { pick: ['codfilial'] },
  },
)
