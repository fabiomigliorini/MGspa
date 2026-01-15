<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useCfopStore } from '../stores/cfopStore'

const router = useRouter()
const $q = useQuasar()
const cfopStore = useCfopStore()

const loading = computed(() => cfopStore.pagination.loading)
const cfops = computed(() => cfopStore.cfops)
const hasActiveFilters = computed(() => cfopStore.hasActiveFilters)

const onLoad = async (index, done) => {
  try {
    await cfopStore.fetchCfops()
    done(!cfopStore.pagination.hasMore)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar CFOPs',
      caption: error.message,
    })
    done(true)
  }
}

const handleCreateCfop = () => {
  router.push({ name: 'cfop-create' })
}

const handleEditCfop = (codcfop) => {
  router.push({ name: 'cfop-edit', params: { codcfop } })
}

const handleDeleteCfop = (cfop) => {
  $q.dialog({
    title: 'Confirmar exclusao',
    message: `Deseja realmente excluir o CFOP ${cfop.codcfop}?`,
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
      await cfopStore.deleteCfop(cfop.codcfop)
      $q.notify({
        type: 'positive',
        message: 'CFOP excluido com sucesso',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir CFOP',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

onMounted(async () => {
  if (!cfopStore.initialLoadDone) {
    try {
      await cfopStore.fetchCfops(true)
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar CFOPs',
        caption: error.response?.data?.message || error.message,
      })
    }
  }
})
</script>

<template>
  <q-page class="q-pa-md">
    <!-- Header -->
    <div class="row items-center q-mb-md">
      <q-btn flat dense round icon="arrow_back" to="/" class="q-mr-sm" />
      <div class="text-h5">CFOPs</div>
    </div>

    <!-- Loading inicial -->
    <div v-if="loading && cfops.length === 0" class="row justify-center q-mt-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Empty State -->
    <div v-else-if="cfops.length === 0" class="row justify-center q-mt-xl">
      <q-card flat bordered class="text-center q-pa-lg">
        <q-icon name="category" size="4em" color="grey-5" />
        <div class="text-h6 q-mt-md">Nenhum CFOP encontrado</div>
        <div class="text-body2 text-grey">
          <template v-if="hasActiveFilters">Tente ajustar os filtros no menu lateral</template>
          <template v-else>Clique em "Novo CFOP" para criar seu primeiro CFOP</template>
        </div>
      </q-card>
    </div>

    <!-- Lista de CFOPs com Scroll Infinito -->
    <q-infinite-scroll v-else @load="onLoad" :offset="250">
      <div class="row q-col-gutter-sm">
        <div v-for="cfop in cfops" :key="cfop.codcfop" class="col-6 col-sm-4 col-md-3">
          <q-card class="q-pa-none bg-grey-2" flat bordered>
            <div class="text-weight-bold text-primary text-h6 q-pa-sm">
              CFOP: {{ cfop.codcfop }}
            </div>
            <q-separator />
            <div class="q-pa-sm text-body1 ellipsis">{{ cfop.descricao }}</div>

            <q-separator />
            <q-card-section class="q-pa-none" align="right">
              <q-btn
                flat
                dense
                icon="edit"
                class="text-grey-7 q-mr-sm"
                @click="handleEditCfop(cfop.codcfop)"
              />
              <q-btn
                flat
                dense
                icon="delete"
                color="negative"
                class="q-mr-sm"
                @click="handleDeleteCfop(cfop)"
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

    <!-- FAB para Novo CFOP -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="handleCreateCfop" :disable="loading">
        <q-tooltip>Novo CFOP</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>

<style scoped>
.cfop-card {
  height: 140px;
}
</style>
