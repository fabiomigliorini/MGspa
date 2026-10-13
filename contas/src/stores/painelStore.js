import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { notifyError } from 'src/utils/notify'

// Painel (TASK-203): sem filial, as filiais por empresa com o total de pendências de cada uma
// (v1/painel); com filial, tudo pendente nela, de qualquer usuário (v1/filial/{codfilial}/painel).
// Só mostra; cada item leva à tela que resolve.

export const usePainelStore = defineStore('painel', () => {
  const codfilial = ref(null)
  const filiais = ref([])
  const dados = ref(null)
  const carregando = ref(false)

  async function buscar() {
    carregando.value = true
    try {
      if (codfilial.value) {
        const { data } = await api.get(`v1/filial/${codfilial.value}/painel`)
        dados.value = data
      } else {
        const { data } = await api.get('v1/painel')
        filiais.value = data
      }
    } catch (e) {
      notifyError(e, 'Erro ao buscar o painel')
    } finally {
      carregando.value = false
    }
  }

  return { codfilial, filiais, dados, carregando, buscar }
})
