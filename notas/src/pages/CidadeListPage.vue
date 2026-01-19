<script setup>
import { computed, onMounted, watch, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useCidadeStore } from '../stores/cidadeStore'

const $q = useQuasar()
const cidadeStore = useCidadeStore()

// Dialog de estado (novo/editar)
const dialogEstado = ref(false)
const estadoForm = ref({ estado: '', sigla: '' })
const estadoDialogMode = ref('create') // 'create' ou 'edit'

// Dialog de novo pais
const dialogPais = ref(false)
const novoPais = ref({ pais: '', sigla: '' })

// Dialog de cidade (nova/editar)
const dialogCidade = ref(false)
const cidadeForm = ref({ cidade: '', codigooficial: '' })
const cidadeDialogMode = ref('create') // 'create' ou 'edit'

const loading = computed(() => cidadeStore.pagination.loading)
const cidades = computed(() => cidadeStore.cidades)
const hasActiveFilters = computed(() => cidadeStore.hasActiveFilters)

// Paises e Estados
const paises = computed(() => cidadeStore.paises)
const estados = computed(() => cidadeStore.estadosOrdenados)
const selectedPais = computed({
  get: () => cidadeStore.selectedPais,
  set: (val) => cidadeStore.selectPais(val),
})
const selectedEstado = computed({
  get: () => cidadeStore.selectedEstado,
  set: (val) => cidadeStore.selectEstado(val),
})

// Estado selecionado (objeto completo)
const currentEstado = computed(() => cidadeStore.currentEstado)

// Pais selecionado (objeto completo)
const currentPais = computed(() => cidadeStore.currentPais)

const onLoad = async (index, done) => {
  try {
    await cidadeStore.fetchCidades()
    done(!cidadeStore.pagination.hasMore)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar cidades',
      caption: error.message,
    })
    done(true)
  }
}

const handleSelectPais = async (codpais) => {
  selectedPais.value = codpais
  try {
    await cidadeStore.fetchEstados()
    if (cidadeStore.selectedEstado) {
      await cidadeStore.fetchCidades(true)
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar estados',
      caption: error.message,
    })
  }
}

const handleSelectEstado = async (codestado) => {
  selectedEstado.value = codestado
  try {
    await cidadeStore.fetchCidades(true)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar cidades',
      caption: error.message,
    })
  }
}

// Carrega dados iniciais
onMounted(async () => {
  try {
    // Carrega paises
    await cidadeStore.fetchPaises()

    // Se tem pais selecionado, carrega estados
    if (cidadeStore.selectedPais) {
      await cidadeStore.fetchEstados()

      // Se tem estado selecionado, carrega cidades
      if (cidadeStore.selectedEstado) {
        await cidadeStore.fetchCidades(true)
      }
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar dados',
      caption: error.response?.data?.message || error.message,
    })
  }
})

// Watch para recarregar cidades quando estado mudar
watch(
  () => cidadeStore.selectedEstado,
  async (newVal, oldVal) => {
    if (newVal && newVal !== oldVal && cidadeStore.estadosLoaded) {
      try {
        await cidadeStore.fetchCidades(true)
      } catch (error) {
        console.error('Erro ao carregar cidades:', error)
      }
    }
  }
)

const handleEditCidade = (cidade) => {
  cidadeDialogMode.value = 'edit'
  cidadeForm.value = {
    codcidade: cidade.codcidade,
    cidade: cidade.cidade,
    codigooficial: cidade.codigooficial,
  }
  dialogCidade.value = true
}

const handleDeleteCidade = (cidade) => {
  $q.dialog({
    title: 'Confirmar exclusao',
    message: `Deseja realmente excluir a cidade "${cidade.cidade}"?`,
    cancel: {
      label: 'Cancelar',
      flat: true,
    },
    ok: {
      label: 'Excluir',
      color: 'negative',
    },
    persistent: true,
  }).onOk(async () => {
    try {
      await cidadeStore.deleteCidade(cidade.codcidade)
      $q.notify({
        type: 'positive',
        message: 'Cidade excluida com sucesso',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir cidade',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

const handleCreatePais = () => {
  novoPais.value = { pais: '', sigla: '' }
  dialogPais.value = true
}

const handleSavePais = async () => {
  if (novoPais.value.pais && novoPais.value.sigla) {
    try {
      await cidadeStore.createPais({
        pais: novoPais.value.pais,
        sigla: novoPais.value.sigla.toUpperCase(),
      })
      $q.notify({
        type: 'positive',
        message: 'Pais criado com sucesso',
      })
      dialogPais.value = false
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao criar pais',
        caption: error.response?.data?.message || error.message,
      })
    }
  }
}

// const handleDeletePais = () => {
//   if (!currentPais.value) return

//   $q.dialog({
//     title: 'Confirmar exclusao',
//     message: `Deseja realmente excluir o pais "${currentPais.value.pais}" (${currentPais.value.sigla})?`,
//     cancel: {
//       label: 'Cancelar',
//       flat: true,
//     },
//     ok: {
//       label: 'Excluir',
//       color: 'negative',
//     },
//     persistent: true,
//   }).onOk(async () => {
//     try {
//       await cidadeStore.deletePais(currentPais.value.codpais)
//       $q.notify({
//         type: 'positive',
//         message: 'Pais excluido com sucesso',
//       })
//       // Recarrega estados do novo pais selecionado
//       if (cidadeStore.selectedPais) {
//         await cidadeStore.fetchEstados()
//         if (cidadeStore.selectedEstado) {
//           await cidadeStore.fetchCidades(true)
//         }
//       }
//     } catch (error) {
//       $q.notify({
//         type: 'negative',
//         message: 'Erro ao excluir pais',
//         caption: error.response?.data?.message || error.message,
//       })
//     }
//   })
// }

const handleCreateEstado = () => {
  estadoDialogMode.value = 'create'
  estadoForm.value = { estado: '', sigla: '' }
  dialogEstado.value = true
}

const handleEditEstado = () => {
  if (!currentEstado.value) return
  estadoDialogMode.value = 'edit'
  estadoForm.value = {
    codestado: currentEstado.value.codestado,
    estado: currentEstado.value.estado,
    sigla: currentEstado.value.sigla,
  }
  dialogEstado.value = true
}

const handleSaveEstado = async () => {
  if (estadoForm.value.estado && estadoForm.value.sigla) {
    try {
      if (estadoDialogMode.value === 'create') {
        await cidadeStore.createEstado({
          estado: estadoForm.value.estado,
          sigla: estadoForm.value.sigla.toUpperCase(),
        })
        $q.notify({
          type: 'positive',
          message: 'Estado criado com sucesso',
        })
      } else {
        await cidadeStore.updateEstado(estadoForm.value.codestado, {
          estado: estadoForm.value.estado,
          sigla: estadoForm.value.sigla.toUpperCase(),
        })
        $q.notify({
          type: 'positive',
          message: 'Estado atualizado com sucesso',
        })
      }
      dialogEstado.value = false
    } catch (error) {
      $q.notify({
        type: 'negative',
        message:
          estadoDialogMode.value === 'create' ? 'Erro ao criar estado' : 'Erro ao atualizar estado',
        caption: error.response?.data?.message || error.message,
      })
    }
  }
}

const handleDeleteEstado = () => {
  if (!currentEstado.value) return

  $q.dialog({
    title: 'Confirmar exclusao',
    message: `Deseja realmente excluir o estado "${currentEstado.value.estado}" (${currentEstado.value.sigla})?`,
    cancel: {
      label: 'Cancelar',
      flat: true,
    },
    ok: {
      label: 'Excluir',
      color: 'negative',
    },
    persistent: true,
  }).onOk(async () => {
    try {
      await cidadeStore.deleteEstado(currentEstado.value.codestado)
      $q.notify({
        type: 'positive',
        message: 'Estado excluido com sucesso',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir estado',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

const handleCreateCidade = () => {
  cidadeDialogMode.value = 'create'
  cidadeForm.value = { cidade: '', codigooficial: '' }
  dialogCidade.value = true
}

const handleSaveCidade = async () => {
  if (cidadeForm.value.cidade && cidadeForm.value.codigooficial) {
    try {
      if (cidadeDialogMode.value === 'create') {
        await cidadeStore.createCidade({
          cidade: cidadeForm.value.cidade,
          codigooficial: cidadeForm.value.codigooficial,
        })
        $q.notify({
          type: 'positive',
          message: 'Cidade criada com sucesso',
        })
      } else {
        await cidadeStore.updateCidade(cidadeForm.value.codcidade, {
          cidade: cidadeForm.value.cidade,
          codigooficial: cidadeForm.value.codigooficial,
        })
        $q.notify({
          type: 'positive',
          message: 'Cidade atualizada com sucesso',
        })
      }
      dialogCidade.value = false
    } catch (error) {
      $q.notify({
        type: 'negative',
        message:
          cidadeDialogMode.value === 'create' ? 'Erro ao criar cidade' : 'Erro ao atualizar cidade',
        caption: error.response?.data?.message || error.message,
      })
    }
  }
}
</script>

<template>
  <q-page class="q-pa-md">
    <!-- Tabs de Paises -->
    <div class="q-mb-sm">
      <q-tabs
        v-model="selectedPais"
        class="text-grey-7 bg-grey-2"
        active-color="white"
        active-bg-color="primary"
        indicator-color="transparent"
        align="left"
        inline-label
        no-caps
      >
        <q-tab
          v-for="pais in paises"
          :key="pais.codpais"
          :name="pais.codpais"
          :label="pais.pais"
          @click="handleSelectPais(pais.codpais)"
          class="q-px-lg"
        />
        <!-- Botao para adicionar novo pais -->
        <q-btn flat icon="add" label="Novo Pais" unelevated @click="handleCreatePais" />
        <!-- Botao para excluir pais selecionado -->
        <!-- <q-btn
          v-if="selectedPais"
          flat
          icon="delete"
          label="Excluir Pais"
          unelevated
          color="negative"
          @click="handleDeletePais"
        /> -->
      </q-tabs>
      <q-separator />
    </div>

    <!-- Tabs de Estados (apenas para Brasil/Argentina) -->
    <div v-if="estados.length > 0" class="q-mb-md">
      <q-tabs
        v-model="selectedEstado"
        class="text-grey-7 bg-grey-2"
        active-color="white"
        active-bg-color="primary"
        indicator-color="transparent"
        align="left"
        inline-label
        no-caps
        outside-arrows
        mobile-arrows
      >
        <q-tab
          v-for="estado in estados"
          :key="estado.codestado"
          :name="estado.codestado"
          :label="estado.sigla"
          @click="handleSelectEstado(estado.codestado)"
        >
          <q-tooltip>{{ estado.estado }}</q-tooltip>
        </q-tab>
        <!-- Botao para adicionar novo estado -->
        <q-btn flat icon="add" label="Novo Estado" unelevated @click="handleCreateEstado" />
      </q-tabs>
      <q-separator />
    </div>

    <!-- Info do Pais e Estado Selecionados -->
    <div v-if="currentPais" class="q-mb-md">
      <q-card flat bordered class="bg-grey-1">
        <q-card-section class="q-py-sm">
          <template v-if="currentEstado">
            <q-icon name="place" color="primary" size="sm" class="q-mr-sm" />
            <span class="text-subtitle1 text-weight-medium">{{ currentEstado.estado }}</span>
            <q-chip dense outline color="grey-7" class="q-ml-sm">
              IBGE: {{ currentEstado.codigooficial }}
            </q-chip>
            <q-btn
              flat
              dense
              round
              size="sm"
              icon="edit"
              color="primary"
              class="q-ml-sm"
              @click="handleEditEstado"
            >
              <q-tooltip>Editar Estado</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              size="sm"
              icon="delete"
              color="negative"
              @click="handleDeleteEstado"
            >
              <q-tooltip>Excluir Estado</q-tooltip>
            </q-btn>
          </template>
        </q-card-section>
      </q-card>
    </div>

    <!-- Loading Paises/Estados -->
    <div
      v-if="cidadeStore.loading.paises || cidadeStore.loading.estados"
      class="row justify-center q-mt-xl"
    >
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Loading inicial das Cidades -->
    <div
      v-else-if="loading && cidades.length === 0 && selectedEstado"
      class="row justify-center q-mt-xl"
    >
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Sem Estado Selecionado -->
    <div v-else-if="!selectedEstado && estados.length > 0" class="row justify-center q-mt-xl">
      <q-card flat bordered class="text-center q-pa-lg">
        <q-icon name="touch_app" size="4em" color="grey-5" />
        <div class="text-h6 q-mt-md">Selecione um Estado</div>
        <div class="text-body2 text-grey">Clique em uma das siglas acima para ver as cidades</div>
      </q-card>
    </div>

    <!-- Empty State -->
    <div v-else-if="cidades.length === 0 && selectedEstado" class="row justify-center q-mt-xl">
      <q-card flat bordered class="text-center q-pa-lg">
        <q-icon name="location_city" size="4em" color="grey-5" />
        <div class="text-h6 q-mt-md">Nenhuma cidade encontrada</div>
        <div class="text-body2 text-grey">
          <template v-if="hasActiveFilters">Tente ajustar os filtros no menu lateral</template>
          <template v-else>Nao ha cidades cadastradas para este estado</template>
        </div>
      </q-card>
    </div>

    <!-- Lista de Cidades com Scroll Infinito -->
    <q-infinite-scroll v-else-if="selectedEstado" @load="onLoad" :offset="250">
      <div class="row q-col-gutter-sm">
        <div v-for="cidade in cidades" :key="cidade.codcidade" class="col-6 col-sm-4 col-md-3">
          <q-card class="q-pa-none bg-grey-2" flat bordered>
            <div class="text-weight-bold text-primary q-pa-sm">
              <q-icon name="location_city" class="q-mr-xs" />
              {{ cidade.cidade }}
            </div>
            <q-separator />
            <div class="q-pa-sm">
              <div class="row items-center text-body2 text-grey-8">
                IBGE: {{ cidade.codigooficial }}
              </div>
            </div>

            <q-separator />
            <q-card-section class="q-pa-none" align="right">
              <q-btn
                flat
                dense
                round
                icon="edit"
                color="primary"
                size="sm"
                @click="handleEditCidade(cidade)"
              />
              <q-btn
                flat
                dense
                size="sm"
                round
                icon="delete"
                color="negative"
                class="q-mr-sm"
                @click="handleDeleteCidade(cidade)"
              />
            </q-card-section>
          </q-card>
        </div>
      </div>

      <template v-slot:loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>

    <!-- FAB para Nova Cidade -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        icon="add"
        color="primary"
        @click="handleCreateCidade"
        :disable="loading || !selectedEstado"
      >
        <q-tooltip>Nova Cidade</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- Dialog Estado (Novo/Editar) -->
    <q-dialog v-model="dialogEstado" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">
            {{ estadoDialogMode === 'create' ? 'Novo Estado' : 'Editar Estado' }}
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="estadoForm.estado"
            label="Nome do Estado"
            outlined
            dense
            autofocus
            class="q-mb-md"
          />
          <q-input
            v-model="estadoForm.sigla"
            label="Sigla (ex: MT, SP, RJ)"
            outlined
            dense
            maxlength="2"
            class="text-uppercase"
            @update:model-value="(val) => (estadoForm.sigla = val?.toUpperCase())"
          />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn
            flat
            label="Salvar"
            @click="handleSaveEstado"
            :disable="!estadoForm.estado || !estadoForm.sigla"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog Novo Pais -->
    <q-dialog v-model="dialogPais" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Novo Pais</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="novoPais.pais"
            label="Nome do Pais"
            outlined
            dense
            autofocus
            class="q-mb-md"
          />
          <q-input
            v-model="novoPais.sigla"
            label="Sigla (ex: BR, AR, US)"
            outlined
            dense
            maxlength="10"
            class="text-uppercase"
            @update:model-value="(val) => (novoPais.sigla = val?.toUpperCase())"
          />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn
            flat
            label="Salvar"
            @click="handleSavePais"
            :disable="!novoPais.pais || !novoPais.sigla"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog Cidade (Nova/Editar) -->
    <q-dialog v-model="dialogCidade" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">
            {{ cidadeDialogMode === 'create' ? 'Nova Cidade' : 'Editar Cidade' }}
          </div>
          <div class="text-caption text-grey">{{ currentEstado?.estado }}</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="cidadeForm.cidade"
            label="Nome da Cidade"
            outlined
            dense
            autofocus
            class="q-mb-md"
          />
          <q-input
            v-model="cidadeForm.codigooficial"
            label="Codigo IBGE"
            outlined
            dense
            type="number"
          />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn
            flat
            label="Salvar"
            @click="handleSaveCidade"
            :disable="!cidadeForm.cidade || !cidadeForm.codigooficial"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<style scoped>
.q-tab {
  min-width: 50px;
}
</style>
