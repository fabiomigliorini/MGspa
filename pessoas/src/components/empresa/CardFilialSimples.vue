<script setup>
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { empresaStore } from 'src/stores/empresa'
import { formataMesAno, formataPercentual } from '@components/formatters'
import { hoje, inicioMes, somaMeses } from '@components/utils/datas'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'

// Percentuais do Simples por mes da filial (TASK-280): informados uma vez por mes, a nota
// de servico puxa pela filial e pelo mes da competencia.
const props = defineProps({
  codfilial: { type: Number, required: true },
})

const $q = useQuasar()
const sEmpresa = empresaStore()

function mensagemErro(error, fallback) {
  const errors = error?.response?.data?.errors
  if (errors) return Object.values(errors).flat().join('\n')
  return error?.response?.data?.message || fallback
}

const carregar = async () => {
  sEmpresa.filialSimples = []
  try {
    await sEmpresa.simplesListar(props.codfilial)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: mensagemErro(error, 'Erro ao carregar os percentuais do Simples'),
    })
  }
}

watch(() => props.codfilial, carregar, { immediate: true })

// ==================== DIALOG ====================

const dialog = ref(false)
const salvando = ref(false)
const codfilialsimples = ref(null)
const model = ref({})

// A competencia se digita como MM/AAAA; a API recebe o 1o dia do mes ('AAAA-MM-01')
const paraMesAno = (data) => (data ? data.substring(5, 7) + '/' + data.substring(0, 4) : null)
const paraCompetencia = (mes) => mes.substring(3, 7) + '-' + mes.substring(0, 2) + '-01'
const mesValido = (v) => /^(0[1-9]|1[0-2])\/\d{4}$/.test(v || '') || 'Informe o mês (MM/AAAA)'
const positivo = (v) => Number(v) > 0 || 'Informe o percentual'
// No Simples o ISS e' uma fatia do DAS: nunca passa do % total de tributos
const issAteTributos = (v) =>
  !(
    Number(model.value.tributospercentual) > 0 && Number(v) > Number(model.value.tributospercentual)
  ) || 'Não pode passar dos Tributos'

// Novo mes: o seguinte ao ultimo cadastrado (a lista vem do mais recente); sem nenhum, o atual
const abrirNovo = () => {
  const ultimo = sEmpresa.filialSimples[0]?.competencia
  codfilialsimples.value = null
  model.value = {
    mes: paraMesAno(ultimo ? somaMeses(ultimo, 1) : inicioMes(hoje())),
    tributospercentual: null,
    issaliquota: null,
  }
  dialog.value = true
}

const abrirEditar = (item) => {
  codfilialsimples.value = item.codfilialsimples
  model.value = {
    mes: paraMesAno(item.competencia),
    tributospercentual: item.tributospercentual,
    issaliquota: item.issaliquota,
  }
  dialog.value = true
}

const salvar = async () => {
  if (salvando.value) return
  salvando.value = true
  const novo = !codfilialsimples.value
  const dados = {
    competencia: paraCompetencia(model.value.mes),
    tributospercentual: model.value.tributospercentual,
    issaliquota: model.value.issaliquota,
  }
  try {
    if (novo) {
      await sEmpresa.simplesCriar(props.codfilial, dados)
    } else {
      await sEmpresa.simplesAtualizar(props.codfilial, codfilialsimples.value, dados)
    }
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: novo ? 'Percentuais cadastrados!' : 'Percentuais alterados!',
    })
    dialog.value = false
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: mensagemErro(error, 'Erro ao salvar os percentuais'),
      multiLine: true,
    })
  } finally {
    salvando.value = false
  }
}

const excluir = (item) => {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir os percentuais do Simples de ${formataMesAno(item.competencia)}?`,
    cancel: true,
  }).onOk(async () => {
    try {
      await sEmpresa.simplesExcluir(props.codfilial, item.codfilialsimples)
      $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Excluído!' })
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: mensagemErro(error, 'Erro ao excluir os percentuais'),
      })
    }
  })
}
</script>

<template>
  <!-- Dialog novo/editar mes -->
  <q-dialog v-model="dialog">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        <template v-if="codfilialsimples">EDITAR MÊS DO SIMPLES</template>
        <template v-else>NOVO MÊS DO SIMPLES</template>
      </q-card-section>

      <q-form @submit.prevent="salvar">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgInput
                v-model="model.mes"
                label="Competência"
                mask="##/####"
                inputmode="numeric"
                hint="MM/AAAA"
                autofocus
                :rules="[mesValido]"
              />
            </div>
            <div class="col-6">
              <MgInputValor
                v-model="model.tributospercentual"
                label="Tributos"
                suffix="%"
                :min="0"
                :max="99.99"
                hint="Aprox. do Simples"
                :rules="[positivo]"
              />
            </div>
            <div class="col-6">
              <MgInputValor
                v-model="model.issaliquota"
                label="Alíquota ISS"
                suffix="%"
                :min="0"
                :max="5"
                hint="Efetiva no Simples"
                :rules="[positivo, issAteTributos]"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" :loading="salvando" :disable="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-card bordered flat>
    <q-card-section class="text-grey-9 text-overline row items-center">
      SIMPLES NACIONAL (NFS-e)
      <q-space />
      <q-btn flat round size="sm" color="primary" icon="add" @click="abrirNovo">
        <q-tooltip>Novo mês</q-tooltip>
      </q-btn>
    </q-card-section>

    <q-list>
      <template v-for="item in sEmpresa.filialSimples" :key="item.codfilialsimples">
        <q-separator inset />
        <q-item>
          <q-item-section avatar>
            <q-icon color="primary" name="percent" size="xs" />
          </q-item-section>
          <q-item-section>
            <q-item-label>
              {{ formataMesAno(item.competencia) }}
              <MgInfoCriacao :registro="item" />
            </q-item-label>
            <q-item-label caption>
              Tributos {{ formataPercentual(item.tributospercentual) }} · ISS
              {{ formataPercentual(item.issaliquota) }}
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label caption>
              <q-btn flat round size="sm" color="grey-7" icon="edit" @click="abrirEditar(item)">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluir(item)">
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
      <MgEmptyState v-if="!sEmpresa.filialSimples.length" plain icon="percent">
        Nenhum mês cadastrado: a nota de serviço fica com os percentuais em branco.
      </MgEmptyState>
    </q-list>
  </q-card>
</template>
