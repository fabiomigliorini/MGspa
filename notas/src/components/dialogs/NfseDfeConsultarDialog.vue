<script setup>
import { ref, computed, watch } from 'vue'
import nfseDfeService from '../../services/nfseDfeService'
import { notificarSucesso, notificarErro } from '../../utils/notify'
import { formataNumero } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import { documentoLimpo } from '@components/validador'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
})
const emit = defineEmits(['update:modelValue', 'done'])

const filiais = ref([])
const carregando = ref(false)
const pesquisando = ref(false)
let cancelado = false

const algumaSelecionada = computed(() => filiais.value.some((f) => f.selecionada))

// Eventos de uma NFS-e pela chave (GET /contribuintes/NFSe/{chave}/Eventos do ADN), na
// filial marcada — serve tambem para trazer uma nota que ainda nao chegou pela caixa.
const chave = ref('')
const buscandoChave = ref(false)
// a chave pode ter letras no CNPJ do emitente (CNPJ alfanumérico)
const chaveLimpa = computed(() => documentoLimpo(chave.value))
const selecionadas = computed(() => filiais.value.filter((f) => f.selecionada))
const podeBuscarChave = computed(
  () => chaveLimpa.value.length === 50 && selecionadas.value.length === 1,
)
const buscarChave = async () => {
  buscandoChave.value = true
  try {
    const ret = await nfseDfeService.consultarEventos(
      selecionadas.value[0].codfilial,
      chaveLimpa.value,
    )
    if (ret.documentos > 0) {
      notificarSucesso(ret.mensagem)
      emit('done')
    } else {
      notificarErro(null, ret.mensagem)
    }
  } catch (error) {
    notificarErro(error, 'Falha ao buscar a chave no ADN')
  } finally {
    buscandoChave.value = false
  }
}

const carregarFiliais = async () => {
  carregando.value = true
  try {
    const lista = await nfseDfeService.filiaisHabilitadas()
    filiais.value = lista.map((f) => ({
      codfilial: f.codfilial,
      filial: f.filial,
      nsu: f.nsu,
      selecionada: false,
      buscando: false,
      indeterminado: false,
      percentual: 0,
      recebidos: 0,
      mensagem: null,
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

// Le os lotes do ADN de uma filial ate vir vazio. O ADN nao informa o ultimo NSU, entao
// a barra fica indeterminada e o contador mostra quantos documentos chegaram.
const pesquisarFilial = async (filial) => {
  filial.buscando = true
  filial.indeterminado = true
  filial.percentual = 0
  filial.recebidos = 0
  try {
    let resp = await nfseDfeService.consultar(filial.codfilial)
    filial.recebidos += resp.documentos
    filial.nsu = resp.ultNSU
    while (!cancelado && !resp.fim) {
      resp = await nfseDfeService.consultar(filial.codfilial, resp.ultNSU)
      filial.recebidos += resp.documentos
      filial.nsu = resp.ultNSU
    }
    filial.mensagem =
      filial.recebidos > 0 ? `${filial.recebidos} documento(s) recebido(s)` : resp.mensagem
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
      notificarSucesso('Consulta ao ADN concluída')
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
    <q-card flat style="width: 500px; max-width: 95vw">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">Consultar ADN</div>
        <div class="text-caption">
          Selecione as filiais para buscar novas NFS-e e eventos no Sistema Nacional
        </div>
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
                <template v-if="filial.mensagem"> — {{ filial.mensagem }}</template>
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

      <q-card-section>
        <div class="text-caption text-grey-7 q-mb-sm">
          Ou busque os eventos de uma NFS-e pela chave (marque uma filial)
        </div>
        <div class="row q-col-gutter-sm items-center">
          <div class="col">
            <MgInput
              v-model="chave"
              label="Chave da NFS-e"
              maxlength="80"
              clearable
              :bottom-slots="false"
              :disable="pesquisando || buscandoChave"
            />
          </div>
          <div class="col-auto">
            <q-btn
              flat
              label="Buscar"
              color="primary"
              icon="history"
              :loading="buscandoChave"
              :disable="!podeBuscarChave || pesquisando"
              @click="buscarChave"
            />
          </div>
        </div>
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
