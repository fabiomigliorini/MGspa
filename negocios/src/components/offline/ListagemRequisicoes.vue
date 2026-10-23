<script setup>
import { ref, watch } from 'vue'
import { api } from 'src/services/api'
import { formataCodigo } from '@components/formatters'
import { negocioStore } from 'stores/negocio'
import { STATUS } from 'stores/requisicao'

// Requisições que nasceram desta venda (TASK-55): um card cada, que leva para a requisição.
// Só online; sem conexão o card simplesmente não aparece.
const sNegocio = negocioStore()
const requisicoes = ref([])

watch(
  () => sNegocio.negocio?.codnegocio,
  async (codnegocio) => {
    requisicoes.value = []
    if (!codnegocio) return
    try {
      const { data } = await api.get(`v1/requisicao/negocio/${codnegocio}`)
      requisicoes.value = data
    } catch {
      requisicoes.value = []
    }
  },
  { immediate: true },
)
</script>

<template>
  <div
    class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-2"
    v-for="r in requisicoes"
    :key="r.codrequisicao"
  >
    <q-card flat bordered class="full-height column no-wrap">
      <q-item clickable v-ripple :to="`/requisicao/${r.codrequisicao}`">
        <q-item-section avatar>
          <q-avatar
            :icon="(STATUS[r.status] || STATUS.A).icone"
            :color="(STATUS[r.status] || STATUS.A).cor"
            text-color="white"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis">Requisição</q-item-label>
          <q-item-label caption class="ellipsis"
            >#{{ formataCodigo(r.codrequisicao) }}</q-item-label
          >
          <q-item-label caption class="ellipsis">
            {{ r.siglarequisitante }} para {{ r.siglarequisitada || 'Compras' }} ·
            {{ (STATUS[r.status] || STATUS.A).label }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-card>
  </div>
</template>
