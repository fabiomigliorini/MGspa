<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from 'src/services/api'
import { useConferenciaStore } from 'src/stores/conferenciaStore'
import { notifyError } from 'src/utils/notify'
import MgSelectEstoqueLocal from '@components/MgSelectEstoqueLocal.vue'

const router = useRouter()
const store = useConferenciaStore()

const codestoquelocal = ref(null)
const estoquelocalLabel = ref(null)
const fiscal = ref(false)
const conferenciaperiodica = ref(false)
const data = ref(new Date().toISOString().slice(0, 16)) // yyyy-mm-ddThh:mm

// Marca (autocomplete opcional)
const marcaOptions = ref([])
const marcaSelecionada = ref(null)

const filtrarMarca = async (val, update, abort) => {
  if (!val || val.length < 2) {
    abort()
    return
  }
  try {
    const { data: res } = await api.get('v1/marca/autocompletar', { params: { marca: val } })
    update(() => {
      marcaOptions.value = res
    })
  } catch {
    abort()
  }
}

const iniciar = () => {
  if (!codestoquelocal.value) {
    notifyError(null, 'Selecione o local de estoque')
    return
  }
  if (!data.value) {
    notifyError(null, 'Informe a data do ajuste')
    return
  }

  store.setup = {
    codestoquelocal: codestoquelocal.value,
    estoquelocal: estoquelocalLabel.value,
    fiscal: fiscal.value,
    conferenciaperiodica: conferenciaperiodica.value,
    codmarca: marcaSelecionada.value ? marcaSelecionada.value.id : null,
    marca: marcaSelecionada.value ? marcaSelecionada.value.label : null,
    data: data.value,
  }

  router.push({
    name: 'conferencia-listagem',
    params: {
      codestoquelocal: codestoquelocal.value,
      codmarca: marcaSelecionada.value ? marcaSelecionada.value.id : 0,
      fiscal: fiscal.value ? 1 : 0,
      conferenciaperiodica: conferenciaperiodica.value ? 1 : 0,
      data: data.value,
    },
  })
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 640px; margin: auto">
      <q-card bordered flat>
        <q-card-section class="text-grey-9 text-overline">
          <q-icon name="fact_check" class="q-mr-xs" /> Conferência de Estoque
        </q-card-section>
        <q-separator inset />
        <q-form @submit.prevent="iniciar">
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgSelectEstoqueLocal
                  v-model="codestoquelocal"
                  label="Local de estoque"
                  :rules="[(v) => !!v || 'Obrigatório']"
                  @select="(opt) => (estoquelocalLabel = opt ? opt.label : null)"
                >
                  <template #prepend><q-icon name="warehouse" /></template>
                </MgSelectEstoqueLocal>
              </div>

              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Tipo de saldo</div>
                <q-option-group
                  v-model="fiscal"
                  :options="[
                    { label: 'Físico', value: false },
                    { label: 'Fiscal', value: true },
                  ]"
                  type="radio"
                  inline
                  color="primary"
                />
              </div>

              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Conferência periódica</div>
                <q-option-group
                  v-model="conferenciaperiodica"
                  :options="[
                    { label: 'Não', value: false },
                    { label: 'Sim', value: true },
                  ]"
                  type="radio"
                  inline
                  color="primary"
                />
              </div>

              <div class="col-12">
                <q-select
                  v-model="marcaSelecionada"
                  :options="marcaOptions"
                  use-input
                  outlined
                  clearable
                  label="Marca (opcional)"
                  hint="Deixe em branco para conferir todas"
                  @filter="filtrarMarca"
                >
                  <template #prepend><q-icon name="sell" /></template>
                  <template #no-option>
                    <q-item>
                      <q-item-section class="text-grey">Digite para buscar</q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>

              <div class="col-12">
                <q-input
                  v-model="data"
                  outlined
                  type="datetime-local"
                  stack-label
                  label="Data/hora do ajuste"
                  :rules="[(v) => !!v || 'Obrigatório']"
                >
                  <template #prepend><q-icon name="event" /></template>
                </q-input>
              </div>
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn
              flat
              color="primary"
              icon="play_arrow"
              label="Iniciar conferência"
              type="submit"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </div>
  </q-page>
</template>
