<script setup>
// Livro de ocorrências (TASK-205): o que o caixa cancelou, removeu, diminuiu, descontou ou
// esqueceu aberto nos PDVs monitorados. O gerente lê, conversa com o caixa e marca conferida.
// As auditorias amarradas a cada uma ficam na tela da ocorrência.
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useOcorrenciaStore } from 'src/stores/ocorrenciaStore'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInput from '@components/MgInput.vue'
import MgOcorrenciaItem from '@components/MgOcorrenciaItem.vue'

const store = useOcorrenciaStore()
const route = useRoute()

const carregarMais = async (index, done) => {
  await store.fetchItems(false)
  done(!store.hasMore)
}

// conferir: observação opcional (o que o caixa explicou)
const dialogConferir = ref(false)
const ocorrencia = ref(null)
const observacao = ref(null)

const abrirConferir = (oc) => {
  ocorrencia.value = oc
  observacao.value = null
  dialogConferir.value = true
}

const conferir = async () => {
  if (await store.conferir(ocorrencia.value.codocorrencia, observacao.value)) {
    dialogConferir.value = false
  }
}

// filtro vindo na URL (ex.: o painel da filial) parte do padrão, para a lista bater com a contagem
onMounted(() => {
  const chaves = Object.keys(store.filters).filter((k) => route.query[k] !== undefined)
  if (chaves.length) {
    store.clearFilters()
    chaves.forEach((k) => {
      store.filters[k] = /^cod/.test(k) ? Number(route.query[k]) : route.query[k]
    })
  }
  store.fetchItems(true)
})
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <div class="row items-center q-mb-sm">
          <div class="col text-subtitle1 text-grey-8">
            {{ store.total }}
            {{ store.total == 1 ? 'ocorrência' : 'ocorrências' }}
            {{ store.filters.situacao == 'pendente' ? 'a conferir' : '' }}
          </div>
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="refresh"
            :loading="store.loading"
            @click="store.fetchItems(true)"
          >
            <q-tooltip>Atualizar</q-tooltip>
          </q-btn>
        </div>

        <q-card v-if="store.items.length" bordered flat class="q-mb-md">
          <q-list separator>
            <MgOcorrenciaItem
              v-for="oc in store.items"
              :key="oc.codocorrencia"
              :ocorrencia="oc"
              clickable
              :to="{ name: 'ocorrencia-detalhe', params: { codocorrencia: oc.codocorrencia } }"
            >
              <template #acoes>
                <q-btn
                  v-if="oc.conferencia"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="undo"
                  :loading="store.salvando"
                  @click.prevent.stop="store.reabrir(oc.codocorrencia)"
                >
                  <q-tooltip>Reabrir</q-tooltip>
                </q-btn>
                <q-btn
                  v-else
                  flat
                  round
                  size="sm"
                  color="green-7"
                  icon="done"
                  @click.prevent.stop="abrirConferir(oc)"
                >
                  <q-tooltip>Conferir</q-tooltip>
                </q-btn>
              </template>
            </MgOcorrenciaItem>
          </q-list>
        </q-card>

        <MgEmptyState v-if="!store.loading && !store.items.length" icon="task_alt">
          {{
            store.filters.situacao == 'pendente'
              ? 'Nenhuma ocorrência a conferir.'
              : 'Nenhuma ocorrência.'
          }}
        </MgEmptyState>
      </div>
    </q-infinite-scroll>

    <q-dialog v-model="dialogConferir">
      <q-card flat style="width: 400px; max-width: 95vw">
        <q-form @submit.prevent="conferir">
          <q-card-section class="text-h6">Conferir ocorrência</q-card-section>
          <q-card-section class="q-pt-none">
            <div class="q-mb-md">{{ ocorrencia?.descricao }}</div>
            <MgInput
              v-model="observacao"
              label="Observação (o que o caixa explicou)"
              maxlength="500"
              autofocus
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn flat label="Conferir" color="primary" type="submit" :loading="store.salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
