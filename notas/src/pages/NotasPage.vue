<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'

const $q = useQuasar()

const loading = ref(false)
const notas = ref([])

const columns = [
  {
    name: 'numero',
    label: 'Número',
    field: 'numero',
    align: 'left',
    sortable: true,
  },
  {
    name: 'serie',
    label: 'Série',
    field: 'serie',
    align: 'center',
  },
  {
    name: 'cliente',
    label: 'Cliente',
    field: 'cliente',
    align: 'left',
  },
  {
    name: 'data',
    label: 'Data Emissão',
    field: 'data',
    align: 'center',
    sortable: true,
  },
  {
    name: 'valor',
    label: 'Valor',
    field: 'valor',
    align: 'right',
    format: (val) => `R$ ${val.toFixed(2)}`,
  },
  {
    name: 'status',
    label: 'Status',
    field: 'status',
    align: 'center',
  },
  {
    name: 'actions',
    label: 'Ações',
    field: 'actions',
    align: 'center',
  },
]

async function buscarNotas() {
  loading.value = true

  try {
    // Aqui você faria a chamada para a API
    // const response = await api.get('notas')
    // notas.value = response.data

    // Dados mockados para teste
    await new Promise((resolve) => setTimeout(resolve, 1000))
    notas.value = [
      {
        id: 1,
        numero: '000123',
        serie: '1',
        cliente: 'Empresa Exemplo LTDA',
        data: '27/12/2024',
        valor: 1500.0,
        status: 'Autorizada',
      },
      {
        id: 2,
        numero: '000124',
        serie: '1',
        cliente: 'Cliente Teste SA',
        data: '26/12/2024',
        valor: 2300.5,
        status: 'Pendente',
      },
      {
        id: 3,
        numero: '000125',
        serie: '1',
        cliente: 'Fornecedor ABC',
        data: '25/12/2024',
        valor: 850.0,
        status: 'Cancelada',
      },
    ]
  } catch (error) {
    console.log(error)
    $q.notify({
      type: 'negative',
      message: 'Erro ao buscar notas fiscais',
      position: 'top',
    })
  } finally {
    loading.value = false
  }
}

function getStatusColor(status) {
  const colors = {
    Autorizada: 'positive',
    Cancelada: 'negative',
    Pendente: 'warning',
    Rejeitada: 'negative',
  }
  return colors[status] || 'grey'
}

function visualizar(nota) {
  $q.notify({
    type: 'info',
    message: `Visualizando nota ${nota.numero}`,
    position: 'top',
  })
}

function download(nota) {
  $q.notify({
    type: 'positive',
    message: `Download da nota ${nota.numero} iniciado`,
    position: 'top',
  })
}

onMounted(() => {
  buscarNotas()
})
</script>
<template>
  <q-page class="q-pa-md">
    <div class="row q-col-gutter-md">
      <!-- Título -->
      <div class="col-12">
        <div class="text-h4 q-mb-md">Notas Fiscais</div>
      </div>

      <!-- Tabela de Notas -->
      <div class="col-12">
        <q-card flat bordered>
          <q-table
            :rows="notas"
            :columns="columns"
            row-key="id"
            :loading="loading"
            flat
            :pagination="{ rowsPerPage: 10 }"
          >
            <template v-slot:body-cell-status="props">
              <q-td :props="props">
                <q-badge :color="getStatusColor(props.value)">
                  {{ props.value }}
                </q-badge>
              </q-td>
            </template>

            <template v-slot:body-cell-actions="props">
              <q-td :props="props">
                <q-btn
                  flat
                  dense
                  round
                  icon="visibility"
                  color="primary"
                  @click="visualizar(props.row)"
                >
                  <q-tooltip>Visualizar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  icon="download"
                  color="positive"
                  @click="download(props.row)"
                >
                  <q-tooltip>Download</q-tooltip>
                </q-btn>
              </q-td>
            </template>

            <template v-slot:no-data>
              <div class="full-width row flex-center q-gutter-sm text-grey-7">
                <q-icon size="2em" name="description" />
                <span>Nenhuma nota fiscal encontrada</span>
              </div>
            </template>
          </q-table>
        </q-card>
      </div>
    </div>
  </q-page>
</template>
