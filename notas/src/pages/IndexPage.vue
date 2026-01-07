<script setup>
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from 'src/composables/useAuth'
import { useDashboardStore } from 'src/stores/dashboardStore'
import { formatCurrency, formatDateTime } from 'src/utils/formatters'
import {
  STATUS_OPTIONS,
  getStatusColor,
  getStatusIcon,
  getStatusLabel,
} from 'src/constants/notaFiscal'

const router = useRouter()
const { user, validateToken } = useAuth()
const dashboardStore = useDashboardStore()

// Computed para dados do dashboard
const loading = computed(() => dashboardStore.loading)
const totais = computed(() => dashboardStore.totais)
const hoje = computed(() => dashboardStore.hoje)
const semana = computed(() => dashboardStore.semana)
const mes = computed(() => dashboardStore.mes)
const porStatus = computed(() => dashboardStore.porStatus)
const ultimasNotas = computed(() => dashboardStore.ultimasNotas)

// Status para exibir nos cards (em ordem de relevância)
const statusCards = computed(() => {
  return STATUS_OPTIONS.map((status) => ({
    ...status,
    quantidade: porStatus.value[status.value]?.quantidade || 0,
    valor: porStatus.value[status.value]?.valor || 0,
  }))
})

// Buscar dados
const fetchData = async () => {
  try {
    await dashboardStore.fetchDashboard()
  } catch (error) {
    console.error('Erro ao carregar dashboard:', error)
  }
}

// Navegar para nota
const goToNota = (codnotafiscal) => {
  router.push(`/notas/${codnotafiscal}`)
}

// Navegar para lista filtrada por status
const goToNotasByStatus = (status) => {
  router.push({ path: '/notas', query: { status } })
}

onMounted(async () => {
  await validateToken()
  await fetchData()
})
</script>

<template>
  <q-page class="q-pa-md">
    <!-- Header -->
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h5 text-weight-bold">Dashboard</div>
        <div class="text-caption text-grey">Bem-vindo, {{ user?.usuario }}</div>
      </div>
      <div class="col-auto">
        <q-btn flat round icon="refresh" :loading="loading" @click="fetchData">
          <q-tooltip>Atualizar dados</q-tooltip>
        </q-btn>
      </div>
    </div>

    <!-- Cards Principais -->
    <div class="row q-col-gutter-md q-mb-md">
      <!-- Total de Notas -->
      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-caption text-grey">Total de Notas</div>
                <div class="text-h4 text-weight-bold">
                  <q-skeleton v-if="loading" type="text" width="80px" />
                  <span v-else>{{ totais.quantidade.toLocaleString('pt-BR') }}</span>
                </div>
              </div>
              <q-avatar color="primary" text-color="white" icon="description" />
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Valor Total -->
      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-caption text-grey">Valor Total</div>
                <div class="text-h5 text-weight-bold">
                  <q-skeleton v-if="loading" type="text" width="120px" />
                  <span v-else>R$ {{ formatCurrency(totais.valor) }}</span>
                </div>
              </div>
              <q-avatar color="positive" text-color="white" icon="payments" />
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Notas Hoje -->
      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-caption text-grey">Notas Hoje</div>
                <div class="text-h4 text-weight-bold">
                  <q-skeleton v-if="loading" type="text" width="60px" />
                  <span v-else>{{ hoje.quantidade.toLocaleString('pt-BR') }}</span>
                </div>
              </div>
              <q-avatar color="info" text-color="white" icon="today" />
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Valor Hoje -->
      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-caption text-grey">Valor Hoje</div>
                <div class="text-h5 text-weight-bold">
                  <q-skeleton v-if="loading" type="text" width="100px" />
                  <span v-else>R$ {{ formatCurrency(hoje.valor) }}</span>
                </div>
              </div>
              <q-avatar color="accent" text-color="white" icon="attach_money" />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Status das Notas -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="text-subtitle1 text-weight-medium q-mb-md">Status das Notas</div>
        <div class="status-grid">
          <q-card
            v-for="status in statusCards"
            :key="status.value"
            flat
            bordered
            class="cursor-pointer status-card"
            @click="goToNotasByStatus(status.value)"
          >
            <q-card-section class="text-weight-bold q-pa-sm text-center">
              <q-icon :name="status.icon" :color="status.color" size="24px" class="q-mb-xs" />
              <div class="text-h6">{{ status.quantidade }}</div>
              <div class="text-caption text-grey-7">{{ status.label }}</div>
            </q-card-section>
          </q-card>
        </div>
      </q-card-section>
    </q-card>

    <!-- Notas por Período -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="text-subtitle1 text-weight-medium q-mb-md">Notas por Periodo</div>
        <div class="row q-col-gutter-md">
          <!-- Hoje -->
          <div class="col-12 col-md-4">
            <q-card flat bordered class="bg-blue-1">
              <q-card-section>
                <div class="row items-center">
                  <q-icon name="today" color="blue" size="32px" class="q-mr-md" />
                  <div>
                    <div class="text-subtitle2 text-grey-8">Hoje</div>
                    <div class="text-h6 text-weight-bold">
                      <q-skeleton v-if="loading" type="text" width="80px" />
                      <span v-else>{{ hoje.quantidade }} notas</span>
                    </div>
                    <div class="text-body2 text-grey-7">
                      <q-skeleton v-if="loading" type="text" width="100px" />
                      <span v-else>R$ {{ formatCurrency(hoje.valor) }}</span>
                    </div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Semana -->
          <div class="col-12 col-md-4">
            <q-card flat bordered class="bg-green-1">
              <q-card-section>
                <div class="row items-center">
                  <q-icon name="date_range" color="green" size="32px" class="q-mr-md" />
                  <div>
                    <div class="text-subtitle2 text-grey-8">Esta Semana</div>
                    <div class="text-h6 text-weight-bold">
                      <q-skeleton v-if="loading" type="text" width="80px" />
                      <span v-else>{{ semana.quantidade }} notas</span>
                    </div>
                    <div class="text-body2 text-grey-7">
                      <q-skeleton v-if="loading" type="text" width="100px" />
                      <span v-else>R$ {{ formatCurrency(semana.valor) }}</span>
                    </div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Mes -->
          <div class="col-12 col-md-4">
            <q-card flat bordered class="bg-purple-1">
              <q-card-section>
                <div class="row items-center">
                  <q-icon name="calendar_month" color="purple" size="32px" class="q-mr-md" />
                  <div>
                    <div class="text-subtitle2 text-grey-8">Este Mes</div>
                    <div class="text-h6 text-weight-bold">
                      <q-skeleton v-if="loading" type="text" width="80px" />
                      <span v-else>{{ mes.quantidade }} notas</span>
                    </div>
                    <div class="text-body2 text-grey-7">
                      <q-skeleton v-if="loading" type="text" width="100px" />
                      <span v-else>R$ {{ formatCurrency(mes.valor) }}</span>
                    </div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Ultimas Notas -->
    <q-card flat bordered>
      <q-card-section>
        <div class="row items-center q-mb-md">
          <div class="col">
            <div class="text-subtitle1 text-weight-medium">Ultimas Notas Emitidas</div>
          </div>
          <div class="col-auto">
            <q-btn flat dense color="primary" label="Ver todas" to="/notas" />
          </div>
        </div>

        <q-list separator>
          <template v-if="loading">
            <q-item v-for="i in 5" :key="i">
              <q-item-section avatar>
                <q-skeleton type="QAvatar" size="40px" />
              </q-item-section>
              <q-item-section>
                <q-skeleton type="text" width="60%" />
                <q-skeleton type="text" width="40%" />
              </q-item-section>
              <q-item-section side>
                <q-skeleton type="text" width="80px" />
              </q-item-section>
            </q-item>
          </template>

          <template v-else-if="ultimasNotas.length > 0">
            <q-item
              v-for="nota in ultimasNotas"
              :key="nota.codnotafiscal"
              clickable
              @click="goToNota(nota.codnotafiscal)"
            >
              <q-item-section avatar>
                <q-avatar :color="getStatusColor(nota.status)" text-color="white" size="40px">
                  <q-icon :name="getStatusIcon(nota.status)" size="20px" />
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  <span class="text-weight-medium">#{{ nota.numero }}</span>
                  <span class="text-grey-6 q-ml-sm">
                    {{ nota.pessoa?.pessoa || 'Sem cliente' }}
                  </span>
                </q-item-label>
                <q-item-label caption>
                  {{ formatDateTime(nota.emissao) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-item-label class="text-weight-bold">
                  R$ {{ formatCurrency(nota.valortotal) }}
                </q-item-label>
                <q-badge
                  :color="getStatusColor(nota.status)"
                  :label="getStatusLabel(nota.status)"
                />
              </q-item-section>
            </q-item>
          </template>

          <template v-else>
            <q-item>
              <q-item-section class="text-center text-grey">Nenhuma nota encontrada</q-item-section>
            </q-item>
          </template>
        </q-list>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<style scoped>
.status-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

/* Desktop: todos em uma linha */
@media (min-width: 1024px) {
  .status-grid {
    flex-wrap: nowrap;
    justify-content: space-between;
  }
  .status-grid .status-card {
    flex: 1;
    min-width: 120px;
    max-width: 150px;
  }
}

/* Tablet e Mobile: flex wrap */
@media (max-width: 1023px) {
  .status-grid .status-card {
    width: 120px;
    min-width: 120px;
  }
}

.status-card {
  transition: all 0.2s ease;
}
.status-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
