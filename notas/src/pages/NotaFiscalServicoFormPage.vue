<script setup>
import { computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalServicoStore } from '../stores/notaFiscalServicoStore'
import { getNfseStatusLabel } from '../constants/notaFiscalServico'
import { formataNumeroNota } from '@components/formatters'
import MgNotaFiscalServicoForm from '../components/MgNotaFiscalServicoForm.vue'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const store = useNotaFiscalServicoStore()

const isEditMode = computed(() => !!route.params.codnotafiscalservico)
const nota = computed(() => store.nota)
const bloqueada = computed(() => isEditMode.value && store.notaBloqueada)

const voltarPara = computed(() =>
  isEditMode.value ? `/nota-servico/${route.params.codnotafiscalservico}` : '/nota-servico',
)

async function carregar() {
  try {
    if (isEditMode.value) {
      await store.editarNota(route.params.codnotafiscalservico)
    } else {
      await store.novaNota()
    }
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: 'Erro ao abrir a nota de serviço',
      caption: error?.response?.data?.message || error?.message,
    })
    router.push({ name: 'notas-servico' })
  }
}

function salvar() {
  $q.dialog({
    title: 'Confirmação',
    message: 'Tem certeza que deseja salvar?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Salvar', color: 'primary', flat: true },
  }).onOk(async () => {
    try {
      const salva = await store.salvarNota()
      if (!salva) return
      router.push({
        name: 'nota-servico-view',
        params: { codnotafiscalservico: salva.codnotafiscalservico },
      })
    } catch {
      // a store ja notificou o erro
    }
  })
}

onMounted(() => carregar())
</script>

<template>
  <q-page padding>
    <q-form @submit.prevent="salvar" v-if="store.form">
      <div style="max-width: 700px; margin: 0 auto">
        <div class="row items-center q-mb-md no-wrap">
          <q-btn
            flat
            round
            icon="arrow_back"
            :to="voltarPara"
            class="q-mr-sm"
            :disable="store.salvando"
          />
          <div class="text-h5 ellipsis col">
            <template v-if="isEditMode && nota?.numero">
              DPS {{ formataNumeroNota(nota.numero) }} - Série {{ nota.serie }}
            </template>
            <template v-else-if="isEditMode">Nota de Serviço em Digitação</template>
            <template v-else>Nova Nota de Serviço</template>
          </div>
        </div>

        <q-banner v-if="bloqueada" class="bg-warning text-white q-mb-md" rounded>
          <template v-slot:avatar>
            <q-icon name="lock" />
          </template>
          Esta nota está {{ getNfseStatusLabel(nota.status) }} e não pode ser alterada.
        </q-banner>

        <!-- o form ja vem em cards (nota, tomador, servico, valores, observacoes) -->
        <MgNotaFiscalServicoForm :cad="store" :disable="bloqueada" />

        <!-- respiro para o FAB não cobrir o último campo -->
        <div class="q-py-xl" />
      </div>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn
          fab
          color="primary"
          icon="save"
          type="submit"
          :loading="store.salvando"
          :disable="bloqueada"
        >
          <q-tooltip>Salvar</q-tooltip>
        </q-btn>
      </q-page-sticky>
    </q-form>
  </q-page>
</template>
