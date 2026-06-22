<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataNumero, formataData, formataDataIso } from '@components/formatters'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import SelectPessoa from '@components/MgSelectPessoa.vue'
import MgInputData from '@components/MgInputData.vue'

const router = useRouter()

const hoje = formataDataIso(new Date())
const cabecalho = ref({ codportador: null, data: hoje, observacoes: '' })

const filtro = ref({ vencimento_de: hoje, vencimento_ate: hoje, codpessoa: null })
const cheques = ref([])
const selecionados = ref([])
const buscando = ref(false)
const salvando = ref(false)
const buscou = ref(false)

const columns = [
  { name: 'codcheque', label: '#', field: 'codcheque', align: 'left' },
  { name: 'banco', label: 'Banco / Ag.', field: 'banco', align: 'left' },
  { name: 'conta', label: 'Conta / Nº', field: 'conta', align: 'left' },
  { name: 'pessoa', label: 'Cliente / Emitentes', field: 'pessoa', align: 'left' },
  { name: 'valor', label: 'Valor', field: 'valor', align: 'right' },
  { name: 'vencimento', label: 'Vencimento', field: 'vencimento', align: 'center' },
]

const totalSelecionado = computed(() =>
  selecionados.value.reduce((soma, c) => soma + Number(c.valor || 0), 0),
)

async function buscarCheques() {
  if (!filtro.value.vencimento_de && !filtro.value.vencimento_ate) {
    notifyError(null, 'Informe ao menos uma data de vencimento')
    return
  }
  buscando.value = true
  try {
    const { data } = await api.get('v1/cheque-repasse/cheques-para-repassar', {
      params: filtro.value,
    })
    cheques.value = Array.isArray(data) ? data : data.data || []
    selecionados.value = []
    buscou.value = true
  } catch (e) {
    notifyError(e, 'Erro ao buscar cheques')
  } finally {
    buscando.value = false
  }
}

async function salvar() {
  if (!cabecalho.value.codportador) {
    notifyError(null, 'Selecione o portador')
    return
  }
  if (!cabecalho.value.data) {
    notifyError(null, 'Informe a data do repasse')
    return
  }
  if (!selecionados.value.length) {
    notifyError(null, 'Selecione ao menos um cheque')
    return
  }
  salvando.value = true
  try {
    const { data } = await api.post('v1/cheque-repasse', {
      codportador: cabecalho.value.codportador,
      data: cabecalho.value.data,
      observacoes: cabecalho.value.observacoes,
      codcheques: selecionados.value.map((c) => c.codcheque),
    })
    notifySuccess('Repasse criado')
    router.push({
      name: 'cheque-repasse-detalhe',
      params: { codchequerepasse: data.codchequerepasse },
    })
  } catch (e) {
    notifyError(e, 'Erro ao criar repasse')
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1280px; margin: auto">
      <div class="row items-center q-mb-md">
        <q-btn
          flat
          dense
          round
          icon="arrow_back"
          :to="{ name: 'cheque-repasse' }"
          aria-label="Voltar"
        />
        <div class="text-h6 q-ml-sm">Novo Repasse de Cheques</div>
      </div>

      <q-card bordered flat class="q-mb-md">
        <q-card-section>
          <div class="row q-col-gutter-md items-start">
            <div class="col-12 col-sm-4">
              <MgSelectPortador
                v-model="cabecalho.codportador"
                outlined
                label="Portador"
                autofocus
              />
            </div>
            <div class="col-6 col-sm-3">
              <MgInputData v-model="cabecalho.data" label="Data do Repasse" />
            </div>
            <div class="col-12 col-sm-5">
              <q-input
                v-model="cabecalho.observacoes"
                outlined
                label="Observações"
                maxlength="200"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>

      <q-card bordered flat>
        <q-card-section class="bg-grey-1">
          <div class="row q-col-gutter-md items-center">
            <div class="col-6 col-sm-3">
              <MgInputData v-model="filtro.vencimento_de" label="Vencimento de" />
            </div>
            <div class="col-6 col-sm-3">
              <MgInputData v-model="filtro.vencimento_ate" label="Vencimento até" />
            </div>
            <div class="col-12 col-sm-4">
              <SelectPessoa
                v-model="filtro.codpessoa"
                outlined
                clearable
                :bottom-slots="false"
                label="Cliente (opcional)"
              />
            </div>
            <div class="col-12 col-sm-2">
              <q-btn
                color="primary"
                icon="filter_alt"
                label="Buscar"
                :loading="buscando"
                @click="buscarCheques"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-table
          v-model:selected="selecionados"
          :rows="cheques"
          :columns="columns"
          row-key="codcheque"
          selection="multiple"
          flat
          :loading="buscando"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          :no-data-label="
            buscou ? 'Nenhum cheque à repassar no período' : 'Busque os cheques pelo vencimento'
          "
        >
          <template #body-cell-codcheque="props">
            <q-td :props="props" class="text-grey-7">{{ props.row.codcheque }}</q-td>
          </template>
          <template #body-cell-banco="props">
            <q-td :props="props">
              <div>{{ props.row.banco?.banco ?? props.row.codbanco }}</div>
              <div class="text-grey-7 text-caption">Ag. {{ props.row.agencia }}</div>
            </q-td>
          </template>
          <template #body-cell-conta="props">
            <q-td :props="props">
              <div>{{ props.row.contacorrente }}</div>
              <div class="text-grey-7 text-caption">Nº {{ props.row.numero }}</div>
            </q-td>
          </template>
          <template #body-cell-pessoa="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.pessoa?.pessoa }}</div>
              <div
                v-for="emit in props.row.cheque_emitente_s"
                :key="emit.codchequeemitente"
                class="text-grey-7 text-caption"
              >
                {{ emit.emitente }}
              </div>
            </q-td>
          </template>
          <template #body-cell-valor="props">
            <q-td :props="props" class="text-weight-bold">{{
              formataNumero(props.row.valor)
            }}</q-td>
          </template>
          <template #body-cell-vencimento="props">
            <q-td :props="props" class="text-weight-medium">
              {{ formataData(props.row.vencimento) }}
            </q-td>
          </template>
        </q-table>

        <q-separator />

        <q-card-actions class="q-pa-md">
          <div class="text-subtitle2">
            {{ selecionados.length }} cheque(s) — Total
            <span class="text-weight-bold">R$ {{ formataNumero(totalSelecionado) }}</span>
          </div>
          <q-space />
          <q-btn flat label="Cancelar" color="grey-8" :to="{ name: 'cheque-repasse' }" />
          <q-btn
            flat
            color="primary"
            label="Salvar Repasse"
            icon="save"
            :loading="salvando"
            :disable="!selecionados.length"
            @click="salvar"
          />
        </q-card-actions>
      </q-card>
    </div>
  </q-page>
</template>
