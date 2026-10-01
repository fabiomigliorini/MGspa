<script setup>
// Receber ou Pagar Títulos (M6.1 doc-3): a tela de baixa compartilhada com o PDV
// (MgBaixaTitulos), com as formas do financeiro (cartão com bandeira, autorização, parcelas e
// maquineta; banco; dinheiro do cofre; cheque; cartão da empresa; compensação). Gaveta de PDV não
// aparece. Maquinetas de todas as filiais (o financeiro conserta lançamento de qualquer loja).
import { useRouter } from 'vue-router'
import { api } from 'src/services/api'
import { useAuthStore } from 'src/stores/auth'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import MgBaixaTitulos from '@components/MgBaixaTitulos.vue'

// o financeiro não usa gaveta (o PDV recebe na dele)
const FORMAS = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque', 'banco', 'compensacao'],
  saida: ['banco', 'dinheiro', 'cartaoEmpresa', 'cheque', 'compensacao'],
}

const router = useRouter()
const auth = useAuthStore()
const selectCache = useSelectCacheStore()

// portadores que o usuário pode usar (filiais dele), menos gaveta
const portadores = async () => {
  const todos = await selectCache.loadList('portador', 'v1/select/portador')
  const filiais = auth.filiaisRestritas()
  return todos.filter(
    (p) => !p.gaveta && (filiais == null || filiais.map(Number).includes(Number(p.codfilial))),
  )
}

const contexto = async (codfilialTitulos) => {
  const codfilial = codfilialTitulos ?? auth.usuario?.codfilial ?? null
  return {
    pdv: null,
    codfilial,
    portadores: await portadores(),
    // todas as ativas; as de outra filial vêm marcadas
    carregarMaquinetas: async () => {
      const { data } = await api.get(`v1/cobranca/maquineta/${codfilial}`)
      return data.data
    },
  }
}

const finalizado = (pags) =>
  router.replace({ name: 'pagamento-detalhe', params: { id: pags[0].codpagamento } })
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-item class="q-pb-md q-px-none">
        <q-item-section avatar>
          <q-btn flat round icon="arrow_back" :to="{ name: 'pagamento' }" aria-label="Voltar" />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">Receber ou Pagar Títulos</div>
        </q-item-section>
      </q-item>

      <MgBaixaTitulos
        :formas="FORMAS"
        :contexto="contexto"
        :finalizar="{ url: 'v1/pagamento', extras: {} }"
        com-data
        @finalizado="finalizado"
      />
    </div>
  </q-page>
</template>
