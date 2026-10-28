<script setup>
import { onUnmounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataCodigo, formataTimestamp } from '@components/formatters'
import { requisicaoStore, statusDe, DESTINO, trajeto, legenda } from 'stores/requisicao'

// Listagem de requisições (TASK-55): todas, da mais nova para a mais antiga, nos filtros do
// painel lateral (nenhum por padrão). O painel por loja mostra só as abertas.
const sRequisicao = requisicaoStore()
const { filtros, requisicoes, carregandoListagem } = storeToRefs(sRequisicao)
const scrollRef = ref(null)

const carregarMais = async (pagina, done) => done(!(await sRequisicao.carregarListagem(pagina)))

// Filtro mudou: volta o scroll para a página 1. Uma espera só para o filtro inteiro, porque o
// campo de número emite a cada tecla.
let timer = null
watch(
  filtros,
  () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
      requisicoes.value = []
      scrollRef.value.reset()
      scrollRef.value.resume()
      scrollRef.value.trigger()
    }, 500)
  },
  { deep: true },
)
onUnmounted(() => clearTimeout(timer))
</script>

<template>
  <q-page>
    <q-infinite-scroll ref="scrollRef" @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <MgEmptyState v-if="!carregandoListagem && !requisicoes.length" icon="move_down">
          Nenhuma requisição nos filtros escolhidos.
        </MgEmptyState>

        <q-card v-else flat bordered>
          <q-list separator>
            <q-item
              v-for="r in requisicoes"
              :key="r.codrequisicao"
              :to="`/requisicao/${r.codrequisicao}`"
            >
              <q-item-section avatar>
                <q-avatar
                  :icon="DESTINO[r.destino].icone"
                  :color="DESTINO[r.destino].cor"
                  text-color="white"
                />
              </q-item-section>

              <q-item-section style="min-width: 0">
                <q-item-label class="text-weight-medium ellipsis">
                  {{ formataCodigo(r.codrequisicao) }} · {{ trajeto(r) }}
                </q-item-label>
                <q-item-label caption class="ellipsis">{{ legenda(r) }}</q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-item-label caption>{{ formataTimestamp(r.criacao, 2) }}</q-item-label>
                <q-item-label caption>
                  <q-badge :color="statusDe(r).cor" :label="statusDe(r).label" />
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>
  </q-page>
</template>
