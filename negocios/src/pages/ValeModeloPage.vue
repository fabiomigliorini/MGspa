<script setup>
import { ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useQuasar } from 'quasar'
import { api } from 'boot/axios'
import { valeModeloStore } from 'stores/valeModelo'
import { abrirPdf } from '@components/abrirPdf'
import { formataNumero } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const $q = useQuasar()
const sVale = valeModeloStore()
const { modelos, carregando } = storeToRefs(sVale)

const colunas = [
  { name: 'favorecido', label: 'Favorecido', field: 'favorecido', align: 'left' },
  { name: 'modelo', label: 'Descrição', field: 'modelo', align: 'left' },
  { name: 'valorvale', label: 'Valor', field: 'valorvale', align: 'right' },
  { name: 'inativo', label: 'Situação', field: 'inativo', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

// Só o lápis leva à edição: a linha não é link.
const linkEditar = (modelo) => `/vale-modelo/${modelo.codvalemodelo}`

// Vales emitidos só deste modelo (e da escola dele)
const linkEmitidos = (modelo) => ({
  path: '/vale-modelo/emitidos',
  query: {
    codvalemodelo: modelo.codvalemodelo,
    codpessoafavorecido: modelo.codpessoafavorecido || undefined,
  },
})

// Impressao do modelo com precos, para a escola conferir antes da temporada.
const imprimir = (modelo) =>
  abrirPdf(
    api,
    `v1/vale-modelo/${modelo.codvalemodelo}/relatorio`,
    {},
    { title: `Modelo de Vale Compras — ${modelo.modelo}` },
  )

const confirmarExclusao = (modelo) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o modelo "${modelo.modelo}"?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(() => sVale.excluir(modelo))
}

// Quem carrega é o q-infinite-scroll, inclusive a primeira página.
sVale.reiniciar()
const scrollRef = ref(null)

const carregarMais = async (indice, done) => {
  const temMais = await sVale.carregarMais()
  done(!temMais)
}

// Filtro, gravação ou exclusão recarregam da página 1: se o scroll já tinha
// parado no fim da lista anterior, ele volta a funcionar.
watch(
  () => sVale.paginacao,
  (meta) => {
    if (meta?.current_page === 1) scrollRef.value?.resume()
  },
)
</script>

<template>
  <q-page class="bg-grey-2">
    <q-infinite-scroll ref="scrollRef" @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <div class="row justify-end q-mb-sm">
          <q-btn
            flat
            color="primary"
            icon="receipt_long"
            label="Vales emitidos"
            to="/vale-modelo/emitidos"
          />
        </div>
        <MgEmptyState v-if="!carregando && !modelos.length" icon="card_giftcard">
          Nenhum modelo de vale com esse filtro.
        </MgEmptyState>

        <q-table
          v-else
          :rows="modelos"
          :columns="colunas"
          row-key="codvalemodelo"
          flat
          bordered
          :loading="carregando"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
        >
          <template #body="props">
            <q-tr :props="props">
              <q-td key="favorecido" :props="props">
                <span v-if="props.row.favorecido">{{ props.row.favorecido }}</span>
                <span v-else class="text-grey-6">Ao portador</span>
              </q-td>

              <q-td key="modelo" :props="props" class="text-weight-medium">
                {{ props.row.modelo }}
              </q-td>

              <q-td key="valorvale" :props="props">
                {{ formataNumero(props.row.valorvale) }}
              </q-td>

              <q-td key="inativo" :props="props">
                <q-badge v-if="props.row.inativo" color="orange-7">Inativo</q-badge>
                <q-badge v-else color="green-6">Ativo</q-badge>
              </q-td>

              <q-td key="acoes" :props="props">
                <MgInfoCriacao :registro="props.row" />
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="receipt_long"
                  :to="linkEmitidos(props.row)"
                >
                  <q-tooltip>Ver vales emitidos deste modelo</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="print"
                  @click="imprimir(props.row)"
                >
                  <q-tooltip>Imprimir</q-tooltip>
                </q-btn>
                <q-btn flat round size="sm" color="grey-7" icon="edit" :to="linkEditar(props.row)">
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  :icon="props.row.inativo ? 'play_arrow' : 'pause'"
                  @click="sVale.alternarInativo(props.row)"
                >
                  <q-tooltip>{{ props.row.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="confirmarExclusao(props.row)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </q-td>
            </q-tr>
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
      <q-btn fab icon="add" color="primary" to="/vale-modelo/novo">
        <q-tooltip anchor="center left" self="center right">Novo Modelo</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
