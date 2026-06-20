<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { colaboradorStore } from 'stores/colaborador'
import { pessoaStore } from 'stores/pessoa'
import { tempoRelativo, formataDataIso, formataDataAbreviada } from '@components/formatters'
import moment from 'moment'
import 'moment/min/locales'
moment.locale('pt-br')
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectCargo from '@components/MgSelectCargo.vue'

const props = defineProps(['colaboradorCargos'])

const $q = useQuasar()
const sColaborador = colaboradorStore()
const sPessoa = pessoaStore()

async function criarCargo(nome, done) {
  $q.dialog({
    title: 'Deseja criar um novo cargo?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Criar', color: 'primary', flat: true },
  }).onOk(async () => {
    try {
      const ret = await sPessoa.novoCargo({ cargo: nome })
      modelColaboradorCargo.value.codcargo = ret.data.data.codcargo
      done()
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: error.message,
      })
    }
  })
}

const cargosOrdenados = computed(() =>
  [...(props.colaboradorCargos.ColaboradorCargo || [])].sort(
    (a, b) => new Date(b.inicio) - new Date(a.inicio),
  ),
)

const dialogColaboradorCargo = ref(false)
const modelColaboradorCargo = ref({})
const editarCargo = ref(false)

function preencheCargo(colaborador) {
  if (colaborador.ColaboradorCargo.length > 0) {
    modelColaboradorCargo.value = {
      inicio: formataDataIso(new Date()),
      codcolaborador: colaborador.codcolaborador,
    }
  } else {
    modelColaboradorCargo.value = {
      inicio: colaborador.contratacao?.substring(0, 10) ?? null,
      codcolaborador: colaborador.codcolaborador,
    }
  }
}

function novoColaboradorCargo(colaborador) {
  editarCargo.value = false
  dialogColaboradorCargo.value = true
  preencheCargo(colaborador)
}

function preparaModel() {
  return { ...modelColaboradorCargo.value }
}

async function novoCargo() {
  try {
    const model = preparaModel()
    const ret = await sColaborador.novoColaboradorCargo(model)
    if (ret.data.data) {
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'done',
        message: 'Colaborador Cargo criado!',
      })
      dialogColaboradorCargo.value = false
    }
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: error.response.data.message,
    })
  }
}

async function salvarCargo() {
  try {
    const model = preparaModel()
    const ret = await sColaborador.salvarColaboradorCargo(model)
    if (ret.data.data) {
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'done',
        message: 'Colaborador Cargo Alterado!',
      })
      dialogColaboradorCargo.value = false
    }
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: error.response.data.message,
    })
  }
}

const submit = () => {
  editarCargo.value ? salvarCargo() : novoCargo()
}

async function excluir(colaboradorCargo) {
  $q.dialog({
    title: 'Excluir Colaborador Cargo',
    message: 'Tem certeza que deseja excluir esse Cargo?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sColaborador.deleteColaboradorCargo(colaboradorCargo)
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'done',
        message: 'Colaborador Cargo excluido!',
      })
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: error.response.data.message,
      })
    }
  })
}

function editarColaboradorCargo(
  codcolaboradorcargo,
  codcolaborador,
  codcargo,
  inicio,
  fim,
  salario,
  observacoes,
) {
  modelColaboradorCargo.value = {
    codcolaboradorcargo: codcolaboradorcargo,
    codcolaborador: codcolaborador,
    codcargo: codcargo,
    inicio: inicio?.substring(0, 10) ?? null,
    fim: fim?.substring(0, 10) ?? null,
    salario: salario,
    observacoes: observacoes,
  }
  editarCargo.value = true
  dialogColaboradorCargo.value = true
}

function validaObrigatorio(value) {
  if (!value) {
    return 'Preenchimento Obrigatório!'
  }
  return true
}

function validaDataValida(value) {
  if (!value) {
    return true
  }
  const data = moment(value, 'DD/MM/YYYY', true)
  if (!data.isValid()) {
    return 'Data Inválida!'
  }
  return true
}

function validaInicio(value) {
  if (!value) {
    return true
  }
  const inicio = moment(value, 'DD/MM/YYYY', true)
  if (!inicio.isValid()) {
    return true
  }
  const contratacao = props.colaboradorCargos.contratacao?.substring(0, 10)
  if (contratacao && inicio.format('YYYY-MM-DD') < contratacao) {
    return 'Inicio não pode ser anterior á Contratação!'
  }
  return true
}

function validaFim(value) {
  if (!value) {
    return true
  }
  const fim = moment(value, 'DD/MM/YYYY', true)
  if (!fim.isValid()) {
    return true
  }
  const inicio = modelColaboradorCargo.value.inicio
  if (inicio && fim.format('YYYY-MM-DD') < inicio) {
    return 'Fim não pode ser anterior ao inicio!'
  }
  return true
}

defineExpose({ novoColaboradorCargo })
</script>

<template>
  <q-list v-if="colaboradorCargos.ColaboradorCargo?.length > 0">
    <template
      v-for="colaboradorCargo in cargosOrdenados"
      v-bind:key="colaboradorCargo.codcolaboradorcargo"
    >
      <q-separator inset />
      <q-item>
        <q-item-section avatar>
          <q-btn round flat icon="work" color="primary" />
        </q-item-section>

        <q-item-section>
          <q-item-label class="text-weight-bold">
            {{ colaboradorCargo.Cargo }}
          </q-item-label>

          <q-item-label caption v-if="!colaboradorCargo.fim">
            {{ formataDataAbreviada(colaboradorCargo.inicio, 4) }} a ({{
              tempoRelativo(colaboradorCargo.inicio)
            }})
          </q-item-label>
          <q-item-label caption v-else>
            {{ formataDataAbreviada(colaboradorCargo.inicio, 0) }} a
            {{ formataDataAbreviada(colaboradorCargo.fim, 4) }}
          </q-item-label>

          <q-item-label caption v-if="colaboradorCargo.salario">
            Salário: R$ {{ colaboradorCargo.salario }}
          </q-item-label>

          <q-item-label caption v-if="colaboradorCargo.observacoes">
            {{ colaboradorCargo.observacoes }}
          </q-item-label>
        </q-item-section>

        <q-item-section side>
          <q-item-label caption>
            <q-btn
              flat
              dense
              round
              icon="edit"
              size="sm"
              color="grey-7"
              @click="
                editarColaboradorCargo(
                  colaboradorCargo.codcolaboradorcargo,
                  colaboradorCargo.codcolaborador,
                  colaboradorCargo.codcargo,
                  colaboradorCargo.inicio,
                  colaboradorCargo.fim,
                  colaboradorCargo.salario,
                  colaboradorCargo.observacoes,
                )
              "
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              icon="delete"
              size="sm"
              color="grey-7"
              @click="excluir(colaboradorCargo)"
            >
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-list>
  <div v-else class="q-pa-md text-center text-grey">Nenhum cargo cadastrado</div>

  <!-- Dialog Colaborador Cargo -->
  <q-dialog v-model="dialogColaboradorCargo">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-form @submit="submit()">
        <q-card-section class="text-grey-9 text-overline row items-center">
          <template v-if="editarCargo">EDITAR CARGO</template>
          <template v-else>NOVO CARGO</template>
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <MgSelectCargo
            v-model="modelColaboradorCargo.codcargo"
            permite-adicionar
            clearable
            reactive-rules
            :rules="[
              (val) => (val !== null && val !== '' && val !== undefined) || 'Cargo Obrigatório',
            ]"
            @adicionar="criarCargo"
          />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <MgInputData
                type="date"
                v-model="modelColaboradorCargo.inicio"
                label="Início"
                :rules="[validaObrigatorio, validaDataValida, validaInicio]"
              />
            </div>
            <div class="col-6">
              <MgInputData
                type="date"
                v-model="modelColaboradorCargo.fim"
                label="Fim"
                :rules="[validaDataValida, validaFim]"
              />
            </div>
            <div class="col-12">
              <MgInputValor
                prefix="R$"
                :min="0.01"
                v-model="modelColaboradorCargo.salario"
                label="Salário"
              />
            </div>

            <div class="col-12">
              <q-input
                outlined
                autogrow
                bordeless
                v-model="modelColaboradorCargo.observacoes"
                label="Observações"
                type="textarea"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<style scoped></style>
