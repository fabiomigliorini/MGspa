<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgMapaTalhoes from 'components/MgMapaTalhoes.vue'
import { PALETA_TALHAO, corTalhao, sugerirCor } from 'src/utils/coresTalhao'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const codfazenda = Number(route.params.codfazenda)

const fazendaCad = useCadastro('fazenda', 'codfazenda', 'Fazenda')
const talCad = useCadastro('talhao', 'codtalhao', 'Talhão') // inativar/ativar/excluir

const fazenda = ref(null)
const resumo = ref({
  ntalhoes: 0,
  areatalhoes: 0,
  areaplantada: 0,
  sacas: 0,
  produtividade: 0,
  safras: [],
})
const talhoes = ref([])
// Polígonos das demais fazendas — contexto cinza no editor de talhão.
const outrasFazendas = ref([])

// Talhão: novo/editar acontece numa única janela (mapa + nome + área).
const talhaoDialog = ref(false)
const isNovo = ref(true)
const form = ref({})
const salvandoTalhao = ref(false)

const talhoesComGeo = computed(() => talhoes.value.filter((t) => t.geometria))
// Vizinhos mostrados como referência no editor (todos menos o que está aberto).
const referenciaMapa = computed(() =>
  talhoesComGeo.value.filter((t) => t.codtalhao !== form.value.codtalhao),
)
const podeSalvar = computed(() => !!form.value.talhao && Number(form.value.area) > 0)

const kpis = computed(() => [
  { label: 'Talhões', valor: fmt(resumo.value.ntalhoes), icon: 'grass', cor: 'brown' },
  {
    label: 'Área total',
    valor: `${fmt(resumo.value.areatalhoes, 1)} ha`,
    icon: 'crop_landscape',
    cor: 'green',
  },
  {
    label: 'Área plantada',
    valor: `${fmt(resumo.value.areaplantada, 1)} ha`,
    icon: 'eco',
    cor: 'light-green',
  },
  {
    label: 'Produtividade',
    valor: `${fmt(resumo.value.produtividade, 1)} sc/ha`,
    icon: 'trending_up',
    cor: 'teal',
  },
])

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '0'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

function urlGoogleMaps(t) {
  return `https://www.google.com/maps/@${t.latitude},${t.longitude},800m/data=!3m1!1e3`
}

async function carregarFazenda() {
  const { data } = await api.get(`v1/fazenda/${codfazenda}`)
  fazenda.value = data
}
async function carregarResumo() {
  const { data } = await api.get(`v1/fazenda/${codfazenda}/resumo`)
  resumo.value = data
}
async function carregarTalhoes() {
  const { data } = await api.get('v1/talhao', { params: { codfazenda } })
  talhoes.value = data.data ?? data
}
async function carregarOutrasFazendas() {
  const { data } = await api.get('v1/talhao/mapa', { params: { codfazenda } })
  outrasFazendas.value = (data.data ?? data).map((t) => ({
    codfazenda: t.codfazenda,
    fazenda: t.fazenda?.fazenda,
    geometria: t.geometria,
  }))
}

// Fazenda — edição reaproveita o cadastro genérico e recarrega o cabeçalho.
function editarFazenda() {
  fazendaCad.editar(fazenda.value)
}
async function salvarFazenda() {
  await fazendaCad.salvar()
  if (!fazendaCad.dialog) await carregarFazenda()
}
async function alternarInativoFazenda() {
  await fazendaCad.alternarInativo(fazenda.value)
  await carregarFazenda()
}
function excluirFazenda() {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir a fazenda ${fazenda.value?.fazenda}?`,
    cancel: true,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/fazenda/${codfazenda}`)
      notifySuccess('Excluído!')
      router.push({ name: 'fazendas' })
    } catch (e) {
      notifyError(e)
    }
  })
}

// Talhão — janela única (mapa). Novo começa perto dos vizinhos; editar abre o
// polígono existente. A área é calculada do desenho, mas continua editável.
function novoTalhao() {
  isNovo.value = true
  const usadas = talhoes.value.map((t) => t.cor).filter(Boolean)
  form.value = {
    codfazenda,
    talhao: '',
    area: 0,
    geometria: null,
    latitude: null,
    longitude: null,
    cor: sugerirCor(usadas),
  }
  talhaoDialog.value = true
}
function editarTalhao(t) {
  isNovo.value = false
  // Garante uma cor visível mesmo p/ talhões antigos sem cor salva.
  form.value = { ...t, cor: corTalhao(t) }
  talhaoDialog.value = true
}
async function salvarTalhao() {
  if (salvandoTalhao.value || !podeSalvar.value) return
  salvandoTalhao.value = true
  try {
    if (isNovo.value) {
      await api.post('v1/talhao', form.value)
    } else {
      await api.put(`v1/talhao/${form.value.codtalhao}`, form.value)
    }
    notifySuccess('Talhão salvo!')
    talhaoDialog.value = false
    await Promise.all([carregarTalhoes(), carregarResumo()])
  } catch (e) {
    notifyError(e)
  } finally {
    salvandoTalhao.value = false
  }
}
async function acaoTalhao(fn, t) {
  await fn(t)
  await Promise.all([carregarTalhoes(), carregarResumo()])
}

function onCentro(c) {
  form.value.latitude = c.lat
  form.value.longitude = c.lng
}

onMounted(async () => {
  try {
    await Promise.all([
      carregarFazenda(),
      carregarResumo(),
      carregarTalhoes(),
      carregarOutrasFazendas(),
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
              :to="{ name: 'fazendas' }"
            />
            <q-avatar color="green-1" text-color="green-8" icon="agriculture" class="q-ml-sm" />
            <div class="col q-ml-md">
              <div class="text-h6">{{ fazenda?.fazenda || 'Fazenda' }}</div>
              <div class="text-caption text-grey-7">
                {{ fmt(resumo.areatalhoes, 1) }} ha de área total
              </div>
            </div>
          </div>
          <div
            class="col-12 col-sm-auto row items-center justify-end no-wrap"
            :class="{ 'q-mt-sm': $q.screen.lt.sm }"
          >
            <MgInfoCriacao :registro="fazenda" />
            <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="editarFazenda">
              <q-tooltip>Editar fazenda</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              :icon="fazenda?.inativo ? 'play_arrow' : 'pause'"
              @click="alternarInativoFazenda"
            >
              <q-tooltip>{{ fazenda?.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
            <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluirFazenda">
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

      <!-- Mapa -->
      <q-card bordered flat class="q-mb-md">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="green-1" text-color="green-8" icon="map" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Mapa</q-item-label>
            <q-item-label caption>Layout base sobre imagem de satélite</q-item-label>
          </q-item-section>
        </q-item>
        <q-separator />
        <MgMapaTalhoes v-if="talhoesComGeo.length" :talhoes="talhoesComGeo" height="400px" />
        <q-card-section v-else class="text-grey-6 text-center">
          Nenhum talhão com polígono ainda. Use <q-icon name="add" /> para desenhar o primeiro.
        </q-card-section>
      </q-card>

      <!-- Talhões -->
      <q-card bordered flat class="q-mb-md">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="brown-1" text-color="brown-8" icon="grass" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Layout base</q-item-label>
            <q-item-label caption
              >Modelo de talhões da fazenda — cada safra clona e ajusta</q-item-label
            >
          </q-item-section>
          <q-item-section side>
            <q-btn flat round size="sm" color="primary" icon="add" @click="novoTalhao">
              <q-tooltip>Novo talhão</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="t in talhoes" :key="t.codtalhao" :class="{ 'bg-grey-2': t.inativo }">
            <q-item-section avatar>
              <q-avatar
                text-color="white"
                icon="grass"
                :style="{ backgroundColor: corTalhao(t) }"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ t.talhao }}</q-item-label>
              <q-item-label caption>{{ fmt(t.area, 2) }} ha</q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row items-center no-wrap">
                <q-badge v-if="!t.geometria" color="grey-5" label="sem mapa" />
                <MgInfoCriacao :registro="t" />
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click="editarTalhao(t)"
                >
                  <q-tooltip>Editar / desenhar polígono</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="t.latitude && t.longitude"
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="open_in_new"
                  type="a"
                  :href="urlGoogleMaps(t)"
                  target="_blank"
                >
                  <q-tooltip>Abrir no Google Maps</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  :icon="t.inativo ? 'play_arrow' : 'pause'"
                  @click="acaoTalhao(talCad.alternarInativo, t)"
                >
                  <q-tooltip>{{ t.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="acaoTalhao(talCad.excluir, t)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
          <q-item v-if="!talhoes.length">
            <q-item-section class="text-grey-6 text-center"
              >Nenhum talhão cadastrado.</q-item-section
            >
          </q-item>
        </q-list>
      </q-card>

      <!-- Por safra -->
      <q-card bordered flat class="overflow-hidden">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="light-green-1" text-color="light-green-9" icon="eco" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Por safra</q-item-label>
            <q-item-label caption>Plantio e produtividade nesta fazenda</q-item-label>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item
            v-for="s in resumo.safras"
            :key="s.codsafra"
            clickable
            v-ripple
            :to="{ name: 'safra-detalhe', params: { codsafra: s.codsafra } }"
          >
            <q-item-section avatar>
              <q-avatar color="light-green-7" text-color="white" icon="eco" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ s.safra }}</q-item-label>
              <q-item-label caption>{{ fmt(s.area, 1) }} ha · {{ fmt(s.sacas) }} sc</q-item-label>
            </q-item-section>
            <q-item-section side class="text-right">
              <q-item-label class="text-weight-bold text-teal-8">
                {{ fmt(s.produtividade, 1) }} sc/ha
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="!resumo.safras.length">
            <q-item-section class="text-grey-6 text-center">
              Nenhum plantio nesta fazenda ainda.
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Dialog Fazenda -->
      <q-dialog v-model="fazendaCad.dialog">
        <q-card bordered flat style="width: 420px; max-width: 90vw">
          <q-form @submit="salvarFazenda">
            <q-card-section>
              <div class="text-h6">Editar Fazenda</div>
            </q-card-section>
            <q-card-section>
              <q-input
                v-model="fazendaCad.form.fazenda"
                label="Nome da fazenda"
                outlined
                autofocus
              />
              <div class="text-caption text-grey-6 q-mt-sm">
                A área total é calculada automaticamente a partir dos talhões.
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn
                type="submit"
                flat
                label="Salvar"
                color="primary"
                :loading="fazendaCad.salvando"
              />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>

    <!-- Dialog Talhão = mapa tela cheia + bottom sheet de campos + fechar -->
    <q-dialog v-model="talhaoDialog" maximized>
      <q-card class="relative-position" style="width: 100vw; height: 100vh">
        <!-- Mapa de fundo, ocupa a tela toda -->
        <MgMapaTalhoes
          v-if="talhaoDialog"
          :key="form.codtalhao || 'novo'"
          modo="editar"
          :geometria="form.geometria"
          :cor="form.cor"
          :referencia="referenciaMapa"
          :outras="outrasFazendas"
          height="100%"
          :offset-inferior="120"
          @update:geometria="form.geometria = $event"
          @update:centro="onCentro"
          @update:area="form.area = $event"
        />

        <!-- Bottom sheet: cor + nome + área + salvar, numa barra única na base -->
        <div class="absolute-bottom q-pa-md q-mb-sm" style="z-index: 1000">
          <q-card flat bordered class="q-pa-sm" style="margin: 0 auto; max-width: 560px">
            <q-form class="row items-center no-wrap q-gutter-sm" @submit.prevent="salvarTalhao">
              <q-btn round :style="{ backgroundColor: form.cor }" text-color="white" icon="palette">
                <q-tooltip>Cor do talhão</q-tooltip>
                <q-popup-proxy>
                  <q-color
                    v-model="form.cor"
                    :palette="PALETA_TALHAO"
                    default-view="palette"
                    no-header
                    no-footer
                  />
                </q-popup-proxy>
              </q-btn>
              <q-input
                v-model="form.talhao"
                label="Nome"
                outlined
                autofocus
                bg-color="white"
                class="col"
              />
              <MgInputValor
                v-model="form.area"
                :decimals="2"
                suffix="ha"
                label="Área"
                bg-color="white"
                style="width: 120px"
              />
              <q-btn
                type="submit"
                round
                color="primary"
                icon="save"
                :disable="!podeSalvar"
                :loading="salvandoTalhao"
              >
                <q-tooltip>Salvar talhão</q-tooltip>
              </q-btn>
            </q-form>
          </q-card>
        </div>

        <!-- FAB fechar (topo-direita) -->
        <q-btn
          fab
          round
          icon="close"
          color="white"
          text-color="grey-9"
          v-close-popup
          tabindex="-1"
          class="absolute"
          style="right: 16px; top: 16px; z-index: 1000"
        >
          <q-tooltip>Fechar</q-tooltip>
        </q-btn>
      </q-card>
    </q-dialog>
  </q-page>
</template>
