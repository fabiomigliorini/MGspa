<script setup>
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { rhStore } from 'src/stores/rh'
import { extrairErro } from 'src/utils/rhFormatters'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  codperiodo: { type: [Number, String], required: true },
  codsetor: { type: [Number, String], default: null },
  setorNome: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'salvo'])

const $q = useQuasar()
const sRh = rhStore()

const dialog = ref(false)
const carregando = ref(false)
const salvando = ref(false)
const disponiveis = ref([])
const selecionados = ref([])

const carregar = async () => {
  carregando.value = true
  selecionados.value = []
  try {
    disponiveis.value = await sRh.getColaboradoresDisponiveis(props.codperiodo)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao carregar colaboradores'),
    })
  } finally {
    carregando.value = false
  }
}

watch(
  () => props.modelValue,
  (v) => {
    dialog.value = v
    if (v) carregar()
  },
)
watch(dialog, (v) => emit('update:modelValue', v))

const selecionarTodos = () => {
  selecionados.value = disponiveis.value.map((c) => c.codcolaborador)
}

const limpar = () => {
  selecionados.value = []
}

const submit = async () => {
  if (salvando.value || selecionados.value.length === 0) return
  salvando.value = true
  try {
    await sRh.adicionarColaboradores(props.codperiodo, selecionados.value, props.codsetor)
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: 'Colaboradores adicionados',
    })
    dialog.value = false
    emit('salvo')
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao adicionar colaboradores'),
    })
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        ADICIONAR COLABORADORES
        <span v-if="setorNome" class="text-grey-6 q-ml-xs">— {{ setorNome }}</span>
      </q-card-section>

      <q-separator inset />

      <q-card-section class="row items-center q-mb-sm">
        <q-btn flat size="sm" color="primary" label="Selecionar todos" @click="selecionarTodos()" />
        <q-btn flat size="sm" color="grey-7" label="Limpar" @click="limpar()" />
        <q-space />
        <span class="text-caption text-grey-7">{{ selecionados.length }} selecionado(s)</span>
      </q-card-section>

      <q-card-section class="q-pt-none">
        <q-inner-loading :showing="carregando" style="min-height: 60px" />
        <q-list bordered separator v-if="!carregando && disponiveis.length > 0">
          <q-item v-for="c in disponiveis" :key="c.codcolaborador" tag="label" v-ripple clickable>
            <q-item-section avatar>
              <q-checkbox v-model="selecionados" :val="c.codcolaborador" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ c.fantasia }}</q-item-label>
              <q-item-label caption v-if="c.cargo">{{ c.cargo }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
        <div v-else-if="!carregando" class="q-pa-md text-center text-grey">
          Nenhum colaborador disponível
        </div>
      </q-card-section>

      <q-separator inset />

      <q-card-actions align="right" class="text-primary">
        <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
        <q-btn
          flat
          :label="'Adicionar (' + selecionados.length + ')'"
          :disable="selecionados.length === 0"
          :loading="salvando"
          @click="submit()"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
