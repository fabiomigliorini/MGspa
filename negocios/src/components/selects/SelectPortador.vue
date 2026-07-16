<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from 'src/boot/axios'

const props = defineProps({
  modelValue: {
    type: Number,
  },
  somenteAtivos: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['update:modelValue'])

const alterar = (value) => {
  emit('update:modelValue', value)
}

onMounted(async () => {
  if (!props.modelValue) {
    return
  }
  buscarPeloCod(props.modelValue)
})

// Resolve pela rota dedicada. Mandar ?codportador= pro index NAO filtra nada (o index ignora
// param desconhecido em silencio): funcionava por acidente porque o LIMIT 250 cabia os 65
// portadores e o map-options achava o label. Quebraria calado ao passar de 250.
const buscarPeloCod = async () => {
  if (!props.modelValue) {
    return
  }
  try {
    const ret = await api.get('/v1/select/portador/' + props.modelValue)
    opcoes.value = ret.data ? [ret.data] : []
  } catch {
    opcoes.value = []
  }
}

const buscar = async (val, update) => {
  // if (val.length < 1) {
  //   abort();
  //   return;
  // }

  update(async () => {
    const ret = await api.get('/v1/select/portador', {
      params: {
        busca: val,
        somenteAtivos: props.somenteAtivos,
      },
    })
    opcoes.value = ret.data
  })
}

const opcoes = ref([])

watch(
  () => props.modelValue,
  (newValue) => {
    buscarPeloCod(newValue)
  },
)
</script>

<template>
  <q-select
    :options="opcoes"
    :model-value="modelValue"
    use-input
    @filter="buscar"
    emit-value
    map-options
    option-value="codportador"
    option-label="portador"
    v-bind="$attrs"
    options-cover
    @update:model-value="(value) => alterar(value)"
    input-debounce="500"
    clearable
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps" :class="scope.opt.inativo ? 'text-red' : ''">
        <q-item-section avatar>
          <q-icon name="mdi-bank" v-if="scope.opt.codbanco" />
          <q-icon name="point_of_sale" v-else />
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ scope.opt.portador }}
          </q-item-label>
          <q-item-label caption v-if="scope.opt.codbanco">
            {{ scope.opt.banco }}
            {{ scope.opt.agencia }}-{{ scope.opt.agenciadigito }} {{ scope.opt.conta }}-{{
              scope.opt.contadigito
            }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-item-label caption>
            {{ scope.opt.filial }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
