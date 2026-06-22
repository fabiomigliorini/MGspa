<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useCadastro } from 'src/composables/useCadastro'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataData } from '@components/formatters'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgContratoForm from 'components/MgContratoForm.vue'
import MgContratoDados from 'components/MgContratoDados.vue'
import MgContratoFixacao from 'components/MgContratoFixacao.vue'
import MgContratoPagamentos from 'components/MgContratoPagamentos.vue'
import MgContratoNotas from 'components/MgContratoNotas.vue'
import MgContratoEntregas from 'components/MgContratoEntregas.vue'
import MgContratoAnexos from 'components/MgContratoAnexos.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const cod = Number(route.params.codcontrato)

// Store da tela: dono do contrato + KPIs + todas as operações de dados.
// A página (e os cards) leem daqui; os KPIs de reconciliação vêm dos getters.
const store = useContratoDetalheStore()
const {
  contrato,
  volumeemaberto,
  contratado,
  contratadokg,
  carregadokg,
  carregadosc,
  saldokg,
  pesosaca,
  valornf,
  valorCarregado,
  pago,
  bate,
  difNf,
  difPago,
} = storeToRefs(store)

// Form de edição = form compartilhado (também usado p/ criar na safra), então
// continua via useCadastro; ao salvar, recarrega o store da tela.
const contratoCad = useCadastro('contrato', 'codcontrato', 'Contrato')

const corTipo = { FIXO: 'green-7', FIXAR: 'orange-8', BARTER: 'deep-purple-6' }

// Destino do voltar: a safra do contrato (centro de comando); fallback início.
const voltarTo = computed(() =>
  contrato.value?.codsafra
    ? { name: 'safra-detalhe', params: { codsafra: contrato.value.codsafra } }
    : { name: 'home' },
)

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
function rs(v) {
  return 'R$ ' + fmt(v, 2)
}

// ---- Embarque · janela (início → fim) + prazo relativo a hoje ----
const embarqueInicioFmt = computed(() =>
  contrato.value?.embarqueinicio ? formataData(contrato.value.embarqueinicio) : '—',
)
const embarqueFimFmt = computed(() =>
  contrato.value?.embarquefim ? formataData(contrato.value.embarquefim) : '—',
)
function diasAteHoje(iso) {
  if (!iso) return null
  const alvo = new Date(`${String(iso).slice(0, 10)}T00:00:00`)
  if (isNaN(alvo.getTime())) return null
  const hoje = new Date()
  hoje.setHours(0, 0, 0, 0)
  return Math.round((alvo - hoje) / 86400000)
}
const embarquePrazoLabel = computed(() => {
  if (!contrato.value?.embarquefim) return 'Sem janela definida'
  const d = diasAteHoje(contrato.value.embarquefim)
  if (d === null) return 'Sem janela definida'
  if (d > 0) return `Faltam ${d} dia${d === 1 ? '' : 's'} p/ encerrar`
  if (d === 0) return 'Encerra hoje'
  return `Encerrado há ${-d} dia${d === -1 ? '' : 's'}`
})

// ---- Contrato (edição/ativação/exclusão no cabeçalho) ----
function editarContrato() {
  contratoCad.editar(contrato.value)
}
async function alternarInativoContrato() {
  try {
    await store.alternarInativo()
  } catch (e) {
    notifyError(e)
  }
}
function excluirContrato() {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir o contrato ${contrato.value?.contrato}?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      const destino = voltarTo.value
      await store.excluirContrato()
      notifySuccess('Excluído!')
      router.push(destino)
    } catch (e) {
      notifyError(e)
    }
  })
}

onMounted(() => store.carregar(cod))
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center">
          <div class="col-12 col-sm row items-center no-wrap">
            <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="voltarTo" />
            <q-avatar
              :color="corTipo[contrato?.tipo] || 'indigo-7'"
              text-color="white"
              icon="description"
              class="q-ml-sm"
            />
            <div class="col q-ml-md">
              <!-- Nº Nosso (CULTURA-AA/AA-NNNN) -->
              <div v-if="contrato?.contrato" class="text-overline text-grey-7 q-mb-none">
                {{ contrato.contrato }}
              </div>
              <!-- Título: com quem o contrato foi feito (comprador) -->
              <div class="text-h6">
                {{ contrato?.Pessoa?.fantasia || contrato?.Pessoa?.pessoa || 'Contrato' }}
              </div>
              <div class="text-caption text-grey-7">
                {{ contrato?.Cultura?.cultura }}
                <span v-if="contrato?.Safra"> · {{ contrato.Safra.safra }}</span>
              </div>
            </div>
          </div>
          <div
            class="col-12 col-sm-auto row items-center justify-end no-wrap"
            :class="{ 'q-mt-sm': $q.screen.lt.sm }"
          >
            <MgInfoCriacao :registro="contrato" />
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              :icon="contrato?.inativo ? 'play_arrow' : 'pause'"
              @click="alternarInativoContrato"
            >
              <q-tooltip>{{ contrato?.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
            <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluirContrato">
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
            <q-btn
              flat
              color="green-7"
              icon="local_shipping"
              label="Pátio"
              :to="{ name: 'carga' }"
            />
          </div>
        </q-card-section>
      </q-card>

      <!-- Reconciliação físico / fiscal / financeiro -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-blue-grey-8">
                <q-icon name="local_shipping" class="q-mr-sm" /><span class="text-subtitle2"
                  >Físico</span
                >
              </div>
              <div class="text-h5 q-mt-sm row items-center">
                <div>
                  {{ fmt(carregadokg) }}
                  <span class="text-caption"
                    >/ {{ volumeemaberto ? '∞' : fmt(contratadokg) }} kg</span
                  >
                </div>
                <!-- Modo do contrato ao lado da quantidade -->
                <q-chip
                  v-if="contrato"
                  dense
                  square
                  :color="corTipo[contrato.tipo] || 'grey-7'"
                  text-color="white"
                  :label="contrato.tipo"
                  class="q-ml-sm q-my-none"
                />
              </div>
              <!-- Sacas derivadas (unidade comercial) -->
              <div class="text-caption text-grey-6">
                ≈ {{ fmt(carregadosc, 1) }} / {{ volumeemaberto ? '∞' : fmt(contratado) }} sc
              </div>
              <q-linear-progress
                :value="
                  !volumeemaberto && contratadokg ? Math.min(1, carregadokg / contratadokg) : 0
                "
                :indeterminate="volumeemaberto"
                color="green-6"
                track-color="grey-3"
                size="8px"
                rounded
                class="q-my-sm"
              />
              <div v-if="volumeemaberto" class="text-caption text-deep-purple-7">
                <q-icon name="all_inclusive" /> Sem limite — leva o saldo do silo
              </div>
              <div v-else class="text-caption text-grey-7">
                Saldo a embarcar: <b>{{ fmt(saldokg) }} kg</b>
                <span class="text-grey-6">(≈ {{ fmt(saldokg / pesosaca, 0) }} sc)</span>
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-deep-orange-8">
                <q-icon name="receipt_long" class="q-mr-sm" /><span class="text-subtitle2"
                  >Fiscal (NFs)</span
                >
              </div>
              <div class="text-h5 q-mt-sm">{{ rs(valornf) }}</div>
              <div class="text-caption text-grey-7 q-mt-sm">
                Valor carregado: {{ rs(valorCarregado) }}
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-teal-8">
                <q-icon name="payments" class="q-mr-sm" /><span class="text-subtitle2"
                  >Financeiro</span
                >
              </div>
              <div class="text-h5 q-mt-sm">{{ rs(pago) }}</div>
              <div class="text-caption text-grey-7 q-mt-sm">Pago pelo comprador</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-indigo-8">
                <q-icon name="date_range" class="q-mr-sm" /><span class="text-subtitle2"
                  >Embarque - Período</span
                >
              </div>
              <div class="text-h5 q-mt-sm">
                {{ embarqueInicioFmt }}
                <span class="text-subtitle2">a {{ embarqueFimFmt }}</span>
              </div>
              <div class="text-caption text-grey-7 q-mt-sm">{{ embarquePrazoLabel }}</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Bate? -->
      <q-banner v-if="bate" rounded class="bg-green-1 text-green-9 q-mb-md">
        <template #avatar><q-icon name="verified" color="green-7" /></template>
        Tudo confere: valor carregado, NFs emitidas e pagamentos batem.
      </q-banner>
      <q-banner v-else rounded class="bg-amber-1 text-amber-9 q-mb-md">
        <template #avatar><q-icon name="balance" color="amber-8" /></template>
        NFs − carregado: <b>{{ rs(difNf) }}</b> · Pago − NFs: <b>{{ rs(difPago) }}</b>
      </q-banner>

      <!-- Cards especialistas (cada um lê do store da tela) -->
      <template v-if="contrato">
        <MgContratoDados @editar="editarContrato" />
        <MgContratoFixacao />
        <MgContratoPagamentos />
        <MgContratoNotas />
        <MgContratoEntregas />
        <MgContratoAnexos />
      </template>

      <!-- Dialog Contrato (edição) — mesmo form do cadastro -->
      <MgContratoForm :cad="contratoCad" @saved="store.carregar()" />
    </div>
  </q-page>
</template>
