<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import { useCargaStore } from 'src/stores/carga'
import { PALETA_TALHAO, corTalhao, sugerirCor } from 'src/utils/coresTalhao'
import MgInputValor from '@components/MgInputValor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgMapaTalhoes from 'components/MgMapaTalhoes.vue'
import MgIconeCultura from 'components/MgIconeCultura.vue'

const route = useRoute()
const codsafra = Number(route.params.codsafra)

const plantioCad = useCadastro(`safra/${codsafra}/plantio`, 'codplantio', 'Plantio')
const store = useCargaStore()
const { colhidoPorPlantio } = storeToRefs(store)

const safra = ref(null)
const fazendas = ref([])
const variedades = ref([])
const talhoesBase = ref([]) // layout base de todas as fazendas (p/ partir o desenho)
const baseSel = ref(null) // talhão base escolhido no dialog (ação, não persiste)

const pesosaca = computed(() => Number(safra.value?.cultura?.pesosaca) || 60)
const codcultura = computed(() => safra.value?.codcultura ?? safra.value?.cultura?.codcultura)

const variedadesDaCultura = computed(() =>
  variedades.value.filter((v) => v.codcultura === codcultura.value && !v.inativo),
)

// Plantios da safra (todas as fazendas) + produtividade (colhido vem das cargas).
const linhas = computed(() =>
  plantioCad.items.map((p) => {
    const kg = colhidoPorPlantio.value[p.codplantio] || 0
    const sacas = kg / pesosaca.value
    const area = Number(p.areaplantada) || 0
    return { ...p, kg, sacas, produtividade: area > 0 ? sacas / area : 0 }
  }),
)

// KPIs globais da safra (somando todas as fazendas).
const totalArea = computed(() =>
  linhas.value.reduce((s, l) => s + (Number(l.areaplantada) || 0), 0),
)
const totalKg = computed(() => linhas.value.reduce((s, l) => s + l.kg, 0))
const totalSacas = computed(() => totalKg.value / pesosaca.value)
const prodMedia = computed(() => (totalArea.value > 0 ? totalSacas.value / totalArea.value : 0))

// Um card por fazenda que tem plantio nesta safra (mapa + lista + resultado).
const porFazenda = computed(() => {
  const mapa = {}
  for (const l of linhas.value) {
    const cod = l.codfazenda
    if (!mapa[cod]) {
      const f = fazendas.value.find((x) => x.codfazenda === cod)
      mapa[cod] = {
        codfazenda: cod,
        fazenda: f?.fazenda || `Fazenda ${cod}`,
        plantios: [],
        area: 0,
        kg: 0,
      }
    }
    mapa[cod].plantios.push(l)
    mapa[cod].area += Number(l.areaplantada) || 0
    mapa[cod].kg += l.kg
  }
  return Object.values(mapa).map((g) => {
    const sacas = g.kg / pesosaca.value
    return {
      ...g,
      sacas,
      produtividade: g.area > 0 ? sacas / g.area : 0,
      comGeo: g.plantios.filter((p) => p.geometria),
      maxProd: Math.max(1, ...g.plantios.map((p) => p.produtividade)),
    }
  })
})

// No dialog: talhões base da fazenda escolhida (p/ partir o polígono).
const talhoesBaseDaFazenda = computed(() =>
  talhoesBase.value.filter((t) => t.codfazenda === plantioCad.form.codfazenda && !t.inativo),
)
// Vizinhos (mesma fazenda/safra) como referência ao desenhar — evita sobrepor.
const referenciaMapa = computed(() =>
  linhas.value.filter(
    (l) =>
      l.codfazenda === plantioCad.form.codfazenda &&
      l.geometria &&
      l.codplantio !== plantioCad.form.codplantio,
  ),
)
const podeSalvar = computed(
  () =>
    !!plantioCad.form.codfazenda &&
    !!plantioCad.form.talhao &&
    !!plantioCad.form.codvariedade &&
    Number(plantioCad.form.areaplantada) > 0,
)

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
function fmtData(d) {
  if (!d) return ''
  const [a, m, dia] = d.slice(0, 10).split('-')
  return `${dia}/${m}/${a}`
}
const periodo = computed(() => {
  if (!safra.value) return ''
  const i = fmtData(safra.value.datainicio)
  const f = fmtData(safra.value.datafim)
  return i && f ? `${i} a ${f}` : i || f || ''
})

function nomeVariedade(p) {
  return p.Variedade?.variedade || ''
}

function novoPlantio(codfazenda = null) {
  baseSel.value = null
  const usadas = plantioCad.items.map((p) => p.cor).filter(Boolean)
  plantioCad.abrirNovo({
    codfazenda: codfazenda ?? fazendas.value[0]?.codfazenda ?? null,
    talhao: '',
    codvariedade: null,
    areaplantada: 0,
    area: 0,
    geometria: null,
    latitude: null,
    longitude: null,
    cor: sugerirCor(usadas),
  })
}
function editarPlantio(p) {
  baseSel.value = null
  // Garante uma cor visível mesmo p/ plantios antigos sem cor salva.
  plantioCad.editar({ ...p, cor: corTalhao(p) })
}
function selecionarPlantio(codplantio) {
  const p = plantioCad.items.find((x) => x.codplantio === codplantio)
  if (p) editarPlantio(p)
}
// Parte do talhão base: traz o polígono como ponto de partida (editável).
function usarTalhaoBase(t) {
  if (!t) return
  plantioCad.form.talhao = t.talhao
  plantioCad.form.geometria = t.geometria || null
  plantioCad.form.area = Number(t.area) || 0
  plantioCad.form.cor = corTalhao(t)
  if (t.latitude) {
    plantioCad.form.latitude = t.latitude
    plantioCad.form.longitude = t.longitude
  }
}
function salvarPlantio() {
  plantioCad.salvar((f) => ({
    codfazenda: f.codfazenda,
    talhao: f.talhao,
    codvariedade: f.codvariedade,
    areaplantada: f.areaplantada,
    area: f.area,
    geometria: f.geometria,
    cor: f.cor,
    latitude: f.latitude,
    longitude: f.longitude,
  }))
}
function onCentro(c) {
  plantioCad.form.latitude = c.lat
  plantioCad.form.longitude = c.lng
}

onMounted(async () => {
  const [{ data: s }, { data: f }, { data: v }, { data: t }] = await Promise.all([
    api.get(`v1/safra/${codsafra}`),
    api.get('v1/fazenda'),
    api.get('v1/variedade'),
    api.get('v1/talhao'),
  ])
  safra.value = s
  fazendas.value = f.data ?? f
  variedades.value = v.data ?? v
  talhoesBase.value = t.data ?? t
  await plantioCad.carregar()
  await store.definirSafra(codsafra)
  await store.carregarReferencias()
  await store.carregarCargas()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'safras' }" />
          <MgIconeCultura :codcultura="codcultura" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">{{ safra?.safra || 'Safra' }}</div>
            <div class="text-caption text-grey-7">
              {{ safra?.cultura?.cultura }}<span v-if="periodo"> · {{ periodo }}</span>
            </div>
          </div>
          <MgInfoCriacao
            :usuariocriacao="safra?.usuariocriacao"
            :criacao="safra?.criacao"
            :usuarioalteracao="safra?.usuarioalteracao"
            :alteracao="safra?.alteracao"
          />
          <q-btn flat color="green-7" icon="local_shipping" label="Pátio" :to="{ name: 'patio' }" />
        </q-card-section>
      </q-card>

      <!-- KPIs globais (todas as fazendas desta safra) -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-caption text-grey-7">Área plantada</div>
              <div class="text-h6">{{ fmt(totalArea, 1) }} ha</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-caption text-grey-7">Colhido</div>
              <div class="text-h6">{{ fmt(totalSacas) }} sc</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-caption text-grey-7">Produtividade média</div>
              <div class="text-h6 text-green-8">{{ fmt(prodMedia, 1) }} sc/ha</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-caption text-grey-7">Talhões plantados</div>
              <div class="text-h6">{{ linhas.length }}</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Título da seção + adicionar (escolhe a fazenda no dialog) -->
      <div class="row items-center q-mb-sm">
        <div class="col text-subtitle1 text-weight-medium">Plantios por fazenda</div>
        <q-btn flat round size="sm" color="primary" icon="add" @click="novoPlantio()">
          <q-tooltip>Plantar talhão</q-tooltip>
        </q-btn>
      </div>

      <!-- Um card por fazenda: mapa + lista por talhão + resultado -->
      <q-card v-for="g in porFazenda" :key="g.codfazenda" bordered flat class="q-mb-md">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="green-1" text-color="green-8" icon="agriculture" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">{{ g.fazenda }}</q-item-label>
            <q-item-label caption>
              {{ fmt(g.area, 1) }} ha · {{ fmt(g.sacas) }} sc ·
              <span class="text-green-8 text-weight-medium"
                >{{ fmt(g.produtividade, 1) }} sc/ha</span
              >
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-btn
              flat
              round
              size="sm"
              color="primary"
              icon="add"
              @click="novoPlantio(g.codfazenda)"
            >
              <q-tooltip>Plantar talhão nesta fazenda</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
        <q-separator />

        <MgMapaTalhoes
          v-if="g.comGeo.length"
          :talhoes="g.comGeo"
          id-key="codplantio"
          height="300px"
          @select="selecionarPlantio"
        />
        <q-separator v-if="g.comGeo.length" />

        <q-list separator>
          <q-item v-for="l in g.plantios" :key="l.codplantio" :class="{ 'bg-grey-2': l.inativo }">
            <q-item-section avatar>
              <q-avatar
                text-color="white"
                icon="grass"
                :style="{ backgroundColor: corTalhao(l) }"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">
                {{ l.talhao || `Talhão ${l.codplantio}` }}
              </q-item-label>
              <q-item-label caption>
                {{ nomeVariedade(l) || 'sem variedade' }} · {{ fmt(l.areaplantada, 1) }} ha
                <q-badge v-if="!l.geometria" color="grey-5" label="sem mapa" class="q-ml-xs" />
              </q-item-label>
              <q-linear-progress
                :value="l.produtividade / g.maxProd"
                color="green-6"
                track-color="grey-3"
                size="6px"
                rounded
                class="q-mt-xs"
              />
            </q-item-section>
            <q-item-section side class="text-right">
              <q-item-label class="text-weight-bold text-green-8">
                {{ fmt(l.produtividade, 1) }} sc/ha
              </q-item-label>
              <q-item-label caption>{{ fmt(l.sacas) }} sc colhidas</q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row items-center no-wrap">
                <MgInfoCriacao
                  :usuariocriacao="l.usuariocriacao"
                  :criacao="l.criacao"
                  :usuarioalteracao="l.usuarioalteracao"
                  :alteracao="l.alteracao"
                />
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit_location_alt"
                  @click="editarPlantio(l)"
                >
                  <q-tooltip>Editar / desenhar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  :icon="l.inativo ? 'play_arrow' : 'pause'"
                  @click="plantioCad.alternarInativo(l)"
                >
                  <q-tooltip>{{ l.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="plantioCad.excluir(l)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Vazio -->
      <q-card v-if="!porFazenda.length" bordered flat>
        <q-card-section class="text-grey-6 text-center">
          Nenhum talhão plantado nesta safra ainda. Use <q-icon name="add" /> para plantar o
          primeiro.
        </q-card-section>
      </q-card>
    </div>

    <!-- Dialog Plantio = mapa tela cheia + campos flutuando + FABs -->
    <q-dialog v-model="plantioCad.dialog" maximized>
      <q-card class="relative-position" style="width: 100vw; height: 100vh">
        <MgMapaTalhoes
          v-if="plantioCad.dialog"
          :key="plantioCad.form.codplantio || 'novo'"
          modo="editar"
          :geometria="plantioCad.form.geometria"
          :cor="plantioCad.form.cor"
          :referencia="referenciaMapa"
          height="100%"
          @update:geometria="plantioCad.form.geometria = $event"
          @update:centro="onCentro"
          @update:area="plantioCad.form.area = $event"
        />

        <!-- Campos flutuando no topo-esquerda (fundo transparente) -->
        <div
          class="absolute column q-gutter-sm q-pa-sm"
          style="top: 12px; left: 12px; z-index: 1000; width: 290px"
        >
          <q-select
            v-model="plantioCad.form.codfazenda"
            :options="fazendas"
            option-value="codfazenda"
            option-label="fazenda"
            emit-value
            map-options
            outlined
            bg-color="white"
            label="Fazenda"
          />
          <q-select
            v-model="baseSel"
            :options="talhoesBaseDaFazenda"
            option-label="talhao"
            outlined
            clearable
            bg-color="white"
            label="Partir do talhão base (opcional)"
            @update:model-value="usarTalhaoBase"
          />
          <div class="row items-center no-wrap q-gutter-sm">
            <q-btn
              round
              :style="{ backgroundColor: plantioCad.form.cor }"
              text-color="white"
              icon="palette"
            >
              <q-tooltip>Cor do talhão</q-tooltip>
              <q-popup-proxy>
                <q-color
                  v-model="plantioCad.form.cor"
                  :palette="PALETA_TALHAO"
                  default-view="palette"
                  no-header
                  no-footer
                />
              </q-popup-proxy>
            </q-btn>
            <q-input
              v-model="plantioCad.form.talhao"
              label="Talhão (nome / número)"
              outlined
              bg-color="white"
              class="col"
            />
          </div>
          <q-select
            v-model="plantioCad.form.codvariedade"
            :options="variedadesDaCultura"
            option-value="codvariedade"
            option-label="variedade"
            emit-value
            map-options
            outlined
            bg-color="white"
            label="Variedade"
          />
          <MgInputValor
            v-model="plantioCad.form.areaplantada"
            :decimals="2"
            suffix="ha"
            label="Área plantada"
            bg-color="white"
          />
          <MgInputValor
            v-model="plantioCad.form.area"
            :decimals="2"
            suffix="ha"
            label="Área do desenho"
            bg-color="white"
          />
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
          style="right: 24px; top: 24px; z-index: 1000"
        >
          <q-tooltip>Fechar</q-tooltip>
        </q-btn>

        <!-- FAB salvar (baixo-direita) -->
        <q-btn
          fab
          icon="save"
          color="primary"
          :disable="!podeSalvar"
          :loading="plantioCad.salvando"
          class="absolute"
          style="right: 24px; bottom: 24px; z-index: 1000"
          @click="salvarPlantio"
        >
          <q-tooltip>Salvar plantio</q-tooltip>
        </q-btn>
      </q-card>
    </q-dialog>
  </q-page>
</template>
