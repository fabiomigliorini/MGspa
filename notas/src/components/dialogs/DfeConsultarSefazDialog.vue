<script setup>
import { ref, computed, watch } from 'vue'
import dfeDistribuicaoService from '../../services/dfeDistribuicaoService'
import { notificarSucesso, notificarErro } from '../../utils/notify'
import { formataNumero } from '@components/formatters'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
})
const emit = defineEmits(['update:modelValue', 'done'])

const filiais = ref([])
const carregando = ref(false)
const pesquisando = ref(false)
let cancelado = false

const algumaSelecionada = computed(() => filiais.value.some((f) => f.selecionada))

const carregarFiliais = async () => {
  carregando.value = true
  try {
    const lista = await dfeDistribuicaoService.filiaisHabilitadas()
    filiais.value = lista.map((f) => ({
      codfilial: f.codfilial,
      filial: f.filial,
      nsu: f.nsu,
      selecionada: false,
      buscando: false,
      indeterminado: false,
      percentual: 0,
    }))
  } catch (error) {
    notificarErro(error, 'Falha ao carregar filiais')
  } finally {
    carregando.value = false
  }
}

watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto) {
      cancelado = false
      carregarFiliais()
    } else {
      cancelado = true
    }
  },
)

// Pagina a consulta de uma filial até alcançar o maxNSU da SEFAZ.
const pesquisarFilial = async (filial) => {
  filial.buscando = true
  filial.indeterminado = true
  filial.percentual = 0
  try {
    let resp = await dfeDistribuicaoService.distDfe(filial.codfilial)
    const nsuInicial = resp.ultNSU
    filial.indeterminado = false
    while (!cancelado && resp.ultNSU < resp.maxNSU) {
      resp = await dfeDistribuicaoService.distDfe(filial.codfilial, resp.ultNSU)
      const total = resp.maxNSU - nsuInicial
      filial.percentual = total > 0 ? (resp.ultNSU - nsuInicial) / total : 1
    }
    filial.percentual = 1
  } catch (error) {
    notificarErro(error, `Falha ao consultar ${filial.filial}`)
  } finally {
    filial.buscando = false
    filial.indeterminado = false
  }
}

const pesquisar = async () => {
  pesquisando.value = true
  try {
    await Promise.all(filiais.value.filter((f) => f.selecionada).map((f) => pesquisarFilial(f)))
    if (!cancelado) {
      notificarSucesso('Consulta à SEFAZ concluída')
      emit('done')
    }
  } finally {
    pesquisando.value = false
  }
}

const close = () => emit('update:modelValue', false)
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="width: 500px; max-width: 95vw">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">Consultar SEFAZ</div>
        <div class="text-caption">Selecione as filiais para buscar novos documentos</div>
      </q-card-section>

      <q-separator />

      <q-card-section style="max-height: 60vh" class="scroll q-pa-none">
        <div v-if="carregando" class="row justify-center q-pa-lg">
          <q-spinner color="primary" size="2em" />
        </div>

        <q-list v-else separator>
          <q-item
            v-for="filial in filiais"
            :key="filial.codfilial"
            tag="label"
            v-ripple
            :disable="pesquisando"
          >
            <q-item-section avatar>
              <q-checkbox v-model="filial.selecionada" :disable="pesquisando" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ filial.filial }}</q-item-label>
              <q-item-label caption>
                NSU atual: {{ formataNumero(filial.nsu || 0, 0) }}
              </q-item-label>
              <q-linear-progress
                v-if="filial.buscando || filial.percentual > 0"
                :value="filial.percentual"
                :indeterminate="filial.indeterminado"
                color="primary"
                class="q-mt-xs"
                size="6px"
                rounded
              />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="q-pa-md">
        <q-btn flat label="Cancelar" color="grey-8" @click="close" :disable="pesquisando" />
        <q-btn
          flat
          label="Pesquisar"
          color="primary"
          icon="cloud_download"
          :loading="pesquisando"
          :disable="!algumaSelecionada"
          @click="pesquisar"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
