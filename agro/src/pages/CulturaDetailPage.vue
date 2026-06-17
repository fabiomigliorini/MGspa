<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const codcultura = Number(route.params.codcultura)

const culturaCad = useCadastro('cultura', 'codcultura', 'Cultura')

const emojis = ['🌽', '🫛', '🌾', '☕', '🌻', '🥜', '🍅', '🌱']

const cultura = ref(null)
const resumo = ref({
  nsafras: 0,
  area: 0,
  colhidokg: 0,
  sacas: 0,
  produtividade: 0,
  variedades: [],
})
const safras = ref([])

const kpis = computed(() => [
  { label: 'Safras', valor: fmt(resumo.value.nsafras), icon: 'eco', cor: 'light-green' },
  {
    label: 'Área plantada',
    valor: `${fmt(resumo.value.area, 1)} ha`,
    icon: 'crop_landscape',
    cor: 'brown',
  },
  { label: 'Colhido', valor: `${fmt(resumo.value.sacas)} sc`, icon: 'grain', cor: 'amber' },
  {
    label: 'Produtividade média',
    valor: `${fmt(resumo.value.produtividade, 1)} sc/ha`,
    icon: 'trending_up',
    cor: 'teal',
  },
])

const maxProd = computed(() =>
  Math.max(1, ...resumo.value.variedades.map((v) => Number(v.produtividade) || 0)),
)

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '0'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
function periodoSafra(s) {
  if (!s.anoplantio) return ''
  return s.anocolheita && s.anocolheita !== s.anoplantio
    ? `${s.anoplantio}/${s.anocolheita}`
    : `${s.anoplantio}`
}

async function carregarCultura() {
  const { data } = await api.get(`v1/cultura/${codcultura}`)
  cultura.value = data.data ?? data
}
async function carregarResumo() {
  const { data } = await api.get(`v1/cultura/${codcultura}/resumo`)
  resumo.value = data
}

// Cultura — edição reaproveita o cadastro genérico e recarrega cabeçalho/KPIs.
function editarCultura() {
  culturaCad.editar(cultura.value)
}
async function salvarCultura() {
  await culturaCad.salvar()
  if (!culturaCad.dialog) {
    await carregarCultura()
    await carregarResumo()
  }
}
async function alternarInativoCultura() {
  await culturaCad.alternarInativo(cultura.value)
  await carregarCultura()
}
function excluirCultura() {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir a cultura ${cultura.value?.cultura}?`,
    cancel: true,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/cultura/${codcultura}`)
      notifySuccess('Excluído!')
      router.push({ name: 'culturas' })
    } catch (e) {
      notifyError(e)
    }
  })
}

onMounted(async () => {
  try {
    await carregarCultura()
    await Promise.all([
      carregarResumo(),
      api.get('v1/safra', { params: { codcultura, sort: '-anoplantio' } }).then(({ data }) => {
        safras.value = data.data ?? data
      }),
    ])
  } catch (e) {
    notifyError(e)
  }
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center">
          <div class="col-12 col-sm row items-center no-wrap">
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="arrow_back"
              :to="{ name: 'culturas' }"
            />
            <q-avatar v-if="cultura?.icone" color="light-green-1" class="q-ml-sm">
              <span style="font-size: 26px">{{ cultura.icone }}</span>
            </q-avatar>
            <q-avatar
              v-else
              color="light-green-7"
              text-color="white"
              icon="grain"
              class="q-ml-sm"
            />
            <div class="col q-ml-md">
              <div class="text-h6">{{ cultura?.cultura || 'Cultura' }}</div>
              <div class="text-caption text-grey-7">
                {{ Number(cultura?.pesosaca) || 60 }} kg por saca
              </div>
            </div>
          </div>
          <div
            class="col-12 col-sm-auto row items-center justify-end no-wrap"
            :class="{ 'q-mt-sm': $q.screen.lt.sm }"
          >
            <MgInfoCriacao :registro="cultura" />
            <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="editarCultura">
              <q-tooltip>Editar cultura</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              :icon="cultura?.inativo ? 'play_arrow' : 'pause'"
              @click="alternarInativoCultura"
            >
              <q-tooltip>{{ cultura?.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
            <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluirCultura">
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </div>
        </q-card-section>
      </q-card>

      <!-- KPIs -->
      <div class="row q-col-gutter-md q-mb-md">
        <div v-for="k in kpis" :key="k.label" class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section class="row items-center no-wrap">
              <q-avatar :color="`${k.cor}-1`" :text-color="`${k.cor}-8`" :icon="k.icon" />
              <div class="q-ml-md">
                <div class="text-h6 text-weight-bold">{{ k.valor }}</div>
                <div class="text-caption text-grey-7">{{ k.label }}</div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Variedades (desempenho) -->
      <q-card bordered flat class="q-mb-md">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="teal-1" text-color="teal-8" icon="spa" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Variedades</q-item-label>
            <q-item-label caption>Desempenho por variedade (todas as safras)</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="settings"
              :to="{ name: 'cultura-variedades', params: { codcultura } }"
            >
              <q-tooltip>Gerenciar variedades</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="v in resumo.variedades" :key="v.codvariedade">
            <q-item-section avatar>
              <q-avatar color="teal-1" text-color="teal-8" icon="spa" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ v.variedade }}</q-item-label>
              <q-item-label caption>{{ fmt(v.area, 1) }} ha · {{ fmt(v.sacas) }} sc</q-item-label>
              <q-linear-progress
                :value="(Number(v.produtividade) || 0) / maxProd"
                color="teal-6"
                track-color="grey-3"
                size="6px"
                rounded
                class="q-mt-xs"
              />
            </q-item-section>
            <q-item-section side class="text-right">
              <q-item-label class="text-weight-bold text-teal-8">
                {{ fmt(v.produtividade, 1) }} sc/ha
              </q-item-label>
            </q-item-section>
          </q-item>

          <q-item v-if="!resumo.variedades.length">
            <q-item-section class="text-grey-6 text-center">
              Nenhuma variedade cadastrada.
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Safras desta cultura -->
      <q-card bordered flat class="q-mb-md overflow-hidden">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="light-green-1" text-color="light-green-9" icon="eco" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Safras</q-item-label>
            <q-item-label caption>Todas as safras desta cultura</q-item-label>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item
            v-for="s in safras"
            :key="s.codsafra"
            clickable
            v-ripple
            :to="{ name: 'safra-detalhe', params: { codsafra: s.codsafra } }"
            :class="{ 'bg-grey-2': s.inativo }"
          >
            <q-item-section avatar>
              <q-avatar color="light-green-7" text-color="white" icon="eco" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ s.safra }}</q-item-label>
              <q-item-label caption>{{ periodoSafra(s) }}</q-item-label>
            </q-item-section>
            <q-item-section side><q-icon name="chevron_right" /></q-item-section>
          </q-item>
          <q-item v-if="!safras.length">
            <q-item-section class="text-grey-6 text-center">
              Nenhuma safra desta cultura ainda.
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Tabela de desconto (referência) -->
      <q-card bordered flat class="overflow-hidden">
        <q-item clickable v-ripple :to="{ name: 'cultura-desconto', params: { codcultura } }">
          <q-item-section avatar>
            <q-avatar color="deep-orange-1" text-color="deep-orange-8" icon="percent" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Tabela de desconto</q-item-label>
            <q-item-label caption>Faixas de umidade, impureza e avariados</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-icon name="settings" color="grey-7" />
          </q-item-section>
        </q-item>
      </q-card>

      <!-- Dialog Cultura -->
      <q-dialog v-model="culturaCad.dialog">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit="salvarCultura">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">Editar Cultura</div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-8">
                  <q-input v-model="culturaCad.form.cultura" label="Cultura" outlined autofocus />
                </div>
                <div class="col-12 col-sm-4">
                  <q-input
                    v-model="culturaCad.form.icone"
                    label="Emoji"
                    outlined
                    maxlength="4"
                    hint="Opcional"
                  >
                    <template #prepend>
                      <span style="font-size: 20px">{{ culturaCad.form.icone || '🌱' }}</span>
                    </template>
                  </q-input>
                </div>
                <div class="col-12">
                  <div class="row q-gutter-xs">
                    <q-chip
                      v-for="e in emojis"
                      :key="e"
                      clickable
                      :label="e"
                      @click="culturaCad.form.icone = e"
                    />
                  </div>
                </div>
                <div class="col-12">
                  <MgInputValor
                    v-model="culturaCad.form.pesosaca"
                    :decimals="0"
                    suffix="kg/saca"
                    label="Peso da saca"
                  />
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn
                type="submit"
                flat
                label="Salvar"
                color="primary"
                :loading="culturaCad.salvando"
              />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
