<script setup>
import { onMounted } from 'vue'
import { useChequeStore } from 'src/stores/chequeStore'
import { formataNumero, formataData, formataCodigo } from '@components/formatters'
import { chequeStatusLabel, chequeStatusColor } from 'src/constants/chequeStatus'

const store = useChequeStore()

const columns = [
  { name: 'codcheque', label: '#', field: 'codcheque', align: 'left' },
  {
    name: 'banco',
    label: 'Banco / Ag.',
    field: 'banco',
    align: 'left',
    style: 'max-width: 140px',
  },
  { name: 'conta', label: 'Conta / Nº', field: 'conta', align: 'left' },
  {
    name: 'pessoa',
    label: 'Cliente / Emitentes',
    field: 'pessoa',
    align: 'left',
    style: 'max-width: 260px',
  },
  { name: 'valor', label: 'Valor', field: 'valor', align: 'right' },
  { name: 'emissao', label: 'Emissão', field: 'emissao', align: 'center' },
  { name: 'vencimento', label: 'Vencimento', field: 'vencimento', align: 'center' },
  { name: 'indstatus', label: 'Status', field: 'indstatus', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const carregarMais = async (index, done) => {
  await store.fetchItems(false)
  done(!store.hasMore)
}

onMounted(() => store.fetchItems(true))
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="margin: auto; max-width: 1280px">
        <q-table
          :rows="store.items"
          :columns="columns"
          row-key="codcheque"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhum cheque encontrado"
        >
          <template #body-cell-codcheque="props">
            <q-td :props="props" class="text-grey-7">
              <router-link
                :to="{ name: 'cheque-detalhe', params: { codcheque: props.row.codcheque } }"
                class="text-primary"
              >
                {{ formataCodigo(props.row.codcheque) }}
              </router-link>
            </q-td>
          </template>

          <template #body-cell-banco="props">
            <q-td :props="props">
              <div class="text-weight-medium ellipsis">
                {{ props.row.banco?.banco ?? props.row.codbanco }}
              </div>
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
              <div class="text-weight-medium text-primary ellipsis">
                {{ props.row.pessoa?.pessoa }}
              </div>
              <div
                v-for="emit in props.row.cheque_emitente_s"
                :key="emit.codchequeemitente"
                class="text-grey-7 text-caption ellipsis"
              >
                {{ emit.emitente }}
              </div>
            </q-td>
          </template>

          <template #body-cell-valor="props">
            <q-td :props="props" class="text-weight-bold">
              {{ formataNumero(props.row.valor) }}
            </q-td>
          </template>

          <template #body-cell-emissao="props">
            <q-td :props="props" class="text-grey-8">
              {{ formataData(props.row.emissao) }}
            </q-td>
          </template>

          <template #body-cell-vencimento="props">
            <q-td :props="props" class="text-weight-medium">
              {{ formataData(props.row.vencimento) }}
            </q-td>
          </template>

          <template #body-cell-indstatus="props">
            <q-td :props="props">
              <q-badge :color="chequeStatusColor(props.row.indstatus)">
                {{ chequeStatusLabel(props.row.indstatus) }}
              </q-badge>
            </q-td>
          </template>

          <template #body-cell-acoes="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                round
                size="sm"
                color="grey-7"
                icon="visibility"
                :to="{ name: 'cheque-detalhe', params: { codcheque: props.row.codcheque } }"
              >
                <q-tooltip>Ver</q-tooltip>
              </q-btn>
              <q-btn
                flat
                dense
                round
                size="sm"
                color="grey-7"
                icon="edit"
                :to="{ name: 'cheque-editar', params: { codcheque: props.row.codcheque } }"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" :to="{ name: 'cheque-novo' }">
        <q-tooltip anchor="center left" self="center right">Novo Cheque</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
