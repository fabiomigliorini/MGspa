<script setup>
// Uma ocorrência do livro (TASK-205) e todas as auditorias amarradas a ela (TASK-209): o que
// mudou, quem, quando, a justificativa e antes → depois. Conferir e reabrir no cabeçalho; o
// negócio abre pelo botão da própria ocorrência. Esquecido, desconto acima e saída sem
// financeiro são estado: não têm auditoria.
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useOcorrenciaStore } from 'src/stores/ocorrenciaStore'
import MgAuditoriaItem from '@components/MgAuditoriaItem.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInput from '@components/MgInput.vue'
import MgOcorrenciaItem from '@components/MgOcorrenciaItem.vue'

const route = useRoute()
const store = useOcorrenciaStore()
const { ocorrencia, loading, salvando } = storeToRefs(store)

const codocorrencia = computed(() => Number(route.params.codocorrencia))
const oc = computed(() =>
  ocorrencia.value?.codocorrencia === codocorrencia.value ? ocorrencia.value : null,
)

// conferir: observação opcional (o que o caixa explicou)
const dialogConferir = ref(false)
const observacao = ref(null)

const abrirConferir = () => {
  observacao.value = null
  dialogConferir.value = true
}

const conferir = async () => {
  if (await store.conferir(oc.value.codocorrencia, observacao.value)) {
    dialogConferir.value = false
  }
}

onMounted(() => store.carregar(codocorrencia.value))
watch(codocorrencia, (cod) => cod && store.carregar(cod))
</script>

<template>
  <q-page>
    <div v-if="oc" class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div class="row items-center q-col-gutter-x-sm q-mb-sm">
        <div class="col-auto">
          <q-btn flat round icon="arrow_back" color="grey-7" :to="{ name: 'ocorrencia' }" />
        </div>
        <div class="col" style="min-width: 0">
          <div class="text-h5 text-grey-9 ellipsis">Ocorrência #{{ oc.codocorrencia }}</div>
          <div class="text-caption text-grey-7 ellipsis">{{ oc.tipodescricao }}</div>
        </div>
        <div class="col-auto">
          <div class="row items-center no-wrap">
            <q-btn
              v-if="oc.conferencia"
              flat
              round
              size="sm"
              color="grey-7"
              icon="undo"
              :loading="salvando"
              @click="store.reabrir(oc.codocorrencia)"
            >
              <q-tooltip>Reabrir</q-tooltip>
            </q-btn>
            <q-btn v-else flat round size="sm" color="green-7" icon="done" @click="abrirConferir">
              <q-tooltip>Conferir</q-tooltip>
            </q-btn>
          </div>
        </div>
      </div>

      <q-card flat bordered class="q-mb-md">
        <q-list>
          <MgOcorrenciaItem :ocorrencia="oc" />
        </q-list>
      </q-card>

      <q-card v-if="oc.auditorias.length" flat bordered class="q-mb-md">
        <q-list separator>
          <q-item>
            <q-item-section>
              <q-item-label class="text-grey-7">Auditoria</q-item-label>
            </q-item-section>
          </q-item>
          <MgAuditoriaItem v-for="aud in oc.auditorias" :key="aud.codauditoria" :auditoria="aud" />
        </q-list>
      </q-card>
      <MgEmptyState v-else icon="history"
        >Nenhuma auditoria amarrada a esta ocorrência.</MgEmptyState
      >
    </div>

    <q-inner-loading :showing="loading" />

    <q-dialog v-model="dialogConferir">
      <q-card flat style="width: 400px; max-width: 95vw">
        <q-form @submit.prevent="conferir">
          <q-card-section class="text-h6">Conferir ocorrência</q-card-section>
          <q-card-section class="q-pt-none">
            <div class="q-mb-md">{{ oc?.descricao }}</div>
            <MgInput
              v-model="observacao"
              label="Observação (o que o caixa explicou)"
              maxlength="500"
              autofocus
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn flat label="Conferir" color="primary" type="submit" :loading="salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
