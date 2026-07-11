<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { api } from 'src/services/api'
import { useSafraStore } from 'src/stores/safra'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { corTalhao, sugerirCor } from 'src/utils/coresTalhao'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataData } from '@components/formatters'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MapaTalhoes from 'components/MapaTalhoes.vue'
import IconeCultura from 'components/IconeCultura.vue'
import SafraForm from 'components/SafraForm.vue'
import ContratosSafra from 'components/ContratosSafra.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import PlantioWizardDialog from 'components/PlantioWizardDialog.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const codsafra = Number(route.params.codsafra)

// Detalhe do domínio safra — a store é dona da safra aberta, dos KPIs comerciais
// e do CRUD de plantio. Carga (offline-first) e sincronização continuam em suas
// próprias stores; ContratosSafra é outro domínio.
const store = useSafraStore()
const { safra, comercial, plantios } = storeToRefs(store)
const carga = useCargaStore()
const { colhidoPorPlantio } = storeToRefs(carga)
const sinc = useSincronizacaoStore()
const { online } = storeToRefs(sinc)

// Listas de referência da tela (não são entidades safra): fazendas/variedades e
// o layout base de talhões — usados pelo wizard e pela agregação por fazenda.
const fazendas = ref([])
const variedades = ref([])
const talhoesBase = ref([]) // layout base de todas as fazendas

const pesosaca = computed(() => Number(safra.value?.Cultura?.pesosaca) || 60)
const codcultura = computed(() => safra.value?.codcultura ?? safra.value?.Cultura?.codcultura)

const variedadesDaCultura = computed(() =>
  variedades.value.filter((v) => v.codcultura === codcultura.value && !v.inativo),
)

// Adapter no formato useCadastro para o PlantioWizardDialog (que lê
// cad.form/cad.isNovo/cad.salvando e chama cad.salvar(transform)). O estado e o
// CRUD vivem na store; isto é só a ponte do contrato do wizard.
const plantioCad = reactive({
  get form() {
    return store.formPlantio
  },
  get isNovo() {
    return !store.formPlantio.codplantio
  },
  get salvando() {
    return store.salvandoPlantio
  },
  salvar(transform) {
    // Aplica o whitelist do wizard, mas preserva a PK — a store decide
    // POST/PUT por formPlantio.codplantio.
    if (transform) {
      const pk = store.formPlantio.codplantio
      store.formPlantio = { ...transform({ ...store.formPlantio }), codplantio: pk }
    }
    return store.salvarPlantio(codsafra)
  },
})

// Plantios da safra (todas as fazendas) + produtividade (colhido vem das cargas)
// e progresso da colheita (colhido ÷ expectativa).
const linhas = computed(() =>
  plantios.value.map((p) => {
    const kg = colhidoPorPlantio.value[p.codplantio] || 0
    const sacas = kg / pesosaca.value
    const area = Number(p.areaplantada) || 0
    const expectativa = Number(p.expectativasacas) || 0
    return {
      ...p,
      kg,
      sacas,
      expectativa,
      expectativaha: area > 0 ? expectativa / area : 0,
      produtividade: area > 0 ? sacas / area : 0,
      progresso: expectativa > 0 ? Math.min(1, sacas / expectativa) : 0,
    }
  }),
)

// KPIs da safra — TODOS prontos do backend (SafraService::resumoComercial):
// comercial (contratos VENDA) + agronômico (plantios, com produção/produtividade).
const contratado = computed(() => Number(comercial.value?.contratado) || 0)
const fixado = computed(() => Number(comercial.value?.fixado) || 0)
const afixar = computed(() => Number(comercial.value?.afixar) || 0)
const disponivel = computed(() => Number(comercial.value?.disponivel) || 0)
const areaplantada = computed(() => Number(comercial.value?.areaplantada) || 0)
// Expectativa = PRODUÇÃO viva (regra de 3): recalcula conforme o colhido/ha colhido.
// Sem colheita = expectativa plantada; colhendo = projeção pela produtividade real.
const producao = computed(() => Number(comercial.value?.producao) || 0)
const colhido = computed(() => Number(comercial.value?.colhido) || 0)
const prodColhido = computed(() => Number(comercial.value?.produtividadecolhido) || 0)
const progresso = computed(() => Number(comercial.value?.progressocolheita) || 0)

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
        expectativa: 0,
      }
    }
    mapa[cod].plantios.push(l)
    mapa[cod].area += Number(l.areaplantada) || 0
    mapa[cod].kg += l.kg
    mapa[cod].expectativa += l.expectativa
  }
  return Object.values(mapa).map((g) => {
    const sacas = g.kg / pesosaca.value
    // Ordem estável dos cards: variedade (alfabética), depois codplantio.
    const plantios = [...g.plantios].sort((a, b) => {
      const va = (a.Variedade?.variedade || '').toLowerCase()
      const vb = (b.Variedade?.variedade || '').toLowerCase()
      if (va !== vb) return va < vb ? -1 : 1
      return (a.codplantio || 0) - (b.codplantio || 0)
    })
    return {
      ...g,
      plantios,
      sacas,
      produtividade: g.area > 0 ? sacas / g.area : 0,
      comGeo: plantios.filter((p) => p.geometria),
      progresso: g.expectativa > 0 ? Math.min(1, sacas / g.expectativa) : 0,
    }
  })
})

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
function rs(v) {
  return v === null || v === undefined ? '—' : 'R$ ' + fmt(v, 2)
}

// Recarrega o rollup comercial (chamado no mount e quando a grid emite changed).
// Só online — offline a store mantém o último valor.
async function recarregarComercial() {
  if (!online.value) return
  await store.carregarComercial(codsafra)
}
const periodo = computed(() => {
  const s = safra.value
  if (!s || !s.anoplantio) return ''
  return s.anocolheita && s.anocolheita !== s.anoplantio
    ? `${s.anoplantio}/${s.anocolheita}`
    : `${s.anoplantio}`
})

function nomeVariedade(p) {
  return p.Variedade?.variedade || ''
}

// Abre o wizard: sem fazenda → começa na escolha de fazenda; com fazenda
// (botão de adicionar dentro do card de uma fazenda) → pula pra escolha do talhão.
function novoPlantio(codfazenda = null) {
  const usadas = plantios.value.map((p) => p.cor).filter(Boolean)
  store.novoPlantio({
    codfazenda,
    codtalhao: null,
    talhao: '',
    codvariedade: null,
    areaplantada: 0,
    geometria: null,
    latitude: null,
    longitude: null,
    cor: sugerirCor(usadas),
  })
}
function editarPlantio(p) {
  // Garante uma cor visível mesmo p/ plantios antigos sem cor salva.
  store.editarPlantio({ ...p, cor: corTalhao(p) })
}
function selecionarPlantio(codplantio) {
  const p = plantios.value.find((x) => x.codplantio === codplantio)
  if (p) editarPlantio(p)
}

// Slider "ha colhido" no card do plantio: grava o hacolhido e refresca os KPIs
// (produtividade / produção / disponível vêm prontos do backend).
async function salvarHacolhido(l, v) {
  await store.salvarHacolhido(codsafra, l.codplantio, v)
  await recarregarComercial()
}

// Safra — edição/ativação/exclusão no cabeçalho do detalhe (a lista só navega).
// As actions da store refrescam a safra aberta.
function editarSafra() {
  store.editarSafra(safra.value)
}
function alternarInativoSafra() {
  store.inativarSafra(safra.value)
}
function excluirSafra() {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir a safra ${safra.value?.safra}?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await store.excluirSafra(codsafra)
      notifySuccess('Excluído!')
      router.push({ name: 'safras' })
    } catch (e) {
      notifyError(e)
    }
  })
}

onMounted(async () => {
  const [, { data: f }, { data: v }, { data: t }] = await Promise.all([
    store.carregarSafra(codsafra),
    api.get('v1/fazenda'),
    api.get('v1/variedade'),
    api.get('v1/talhao'),
  ])
  fazendas.value = f.data ?? f
  variedades.value = v.data ?? v
  talhoesBase.value = t.data ?? t
  await store.carregarPlantios(codsafra)
  await carga.definirSafra(codsafra)
  await carga.carregarReferencias()
  await carga.carregarCargas()
  await recarregarComercial()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center">
          <div class="col-12 col-sm row items-center no-wrap">
            <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'safras' }" />
            <IconeCultura :codcultura="codcultura" class="q-ml-sm" />
            <div class="col q-ml-md">
              <div class="text-h6">{{ safra?.safra || 'Safra' }}</div>
              <div class="text-caption text-grey-7">
                {{ safra?.Cultura?.cultura }}<span v-if="periodo"> · {{ periodo }}</span>
              </div>
            </div>
          </div>
          <div
            class="col-12 col-sm-auto row items-center justify-end no-wrap"
            :class="{ 'q-mt-sm': $q.screen.lt.sm }"
          >
            <MgInfoCriacao :registro="safra" />
            <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="editarSafra">
              <q-tooltip>Editar safra</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              :icon="safra?.inativo ? 'play_arrow' : 'pause'"
              @click="alternarInativoSafra"
            >
              <q-tooltip>{{ safra?.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
            <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluirSafra">
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

      <!-- ===== Comercial: KPIs (prontos do backend) + lista de contratos ===== -->
      <template v-if="online && comercial">
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-6 col-md-3">
            <q-card flat bordered class="full-height">
              <q-card-section>
                <div class="text-caption text-grey-7">Contratado</div>
                <div class="text-h6">{{ fmt(contratado) }} sc</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered class="full-height">
              <q-card-section>
                <div class="text-caption text-grey-7">Fixado</div>
                <div class="text-h6">{{ fmt(fixado) }} sc</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered class="full-height">
              <q-card-section>
                <div class="text-caption text-grey-7">A fixar</div>
                <div class="text-h6" :class="afixar > 0 ? 'text-orange-8' : 'text-grey-6'">
                  {{ fmt(afixar) }} sc
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered class="full-height">
              <q-card-section>
                <div class="text-caption text-grey-7">Disponível p/ vender</div>
                <div class="text-h6 text-green-8">{{ fmt(disponivel) }} sc</div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Preço médio: R$ pelo LÍQUIDO; moeda estrangeira pelo bruto ainda não travado -->
        <q-card
          v-if="comercial.precomediobrl != null || comercial.precomediousd != null"
          flat
          bordered
          class="q-mb-md"
        >
          <q-card-section class="row q-col-gutter-xl items-center">
            <div v-if="comercial.precomediobrl != null" class="col-auto">
              <div class="text-caption text-grey-7">Preço médio R$ (líquido)</div>
              <div class="text-h6">{{ rs(comercial.precomediobrl) }} <small>/sc</small></div>
            </div>
            <div v-if="comercial.precomediousd != null" class="col-auto">
              <div class="text-caption text-grey-7">Preço médio US$ (a travar)</div>
              <div class="text-h6">
                US$ {{ fmt(comercial.precomediousd, 2) }} <small>/sc</small>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </template>

      <ContratosSafra
        :codsafra="codsafra"
        :codcultura="codcultura"
        :online="online"
        class="q-mb-md"
        @changed="recarregarComercial"
      />

      <!-- ===== Agronômico: KPIs (prontos do backend) + plantios ===== -->
      <div v-if="comercial" class="row q-col-gutter-md q-mb-md">
        <div class="col-6 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="text-caption text-grey-7">Área plantada</div>
              <div class="text-h6">{{ fmt(areaplantada, 1) }} ha</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="text-caption text-grey-7">Expectativa</div>
              <div class="text-h6">{{ fmt(producao) }} sc</div>
              <div class="text-caption text-grey-6">
                {{ fmt(areaplantada > 0 ? producao / areaplantada : 0, 1) }} sc/ha
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="text-caption text-grey-7">Colhido</div>
              <div class="text-h6">{{ fmt(colhido) }} sc</div>
              <div v-if="prodColhido > 0" class="text-caption text-green-8">
                {{ fmt(prodColhido, 1) }} sc/ha
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section class="row items-center no-wrap">
              <div class="col">
                <div class="text-caption text-grey-7">Colheita</div>
                <div class="text-caption text-grey-6">{{ fmt(progresso * 100) }}% da área</div>
              </div>
              <q-circular-progress
                :value="progresso * 100"
                size="52px"
                :thickness="0.18"
                color="green-6"
                track-color="grey-3"
                show-value
                class="text-caption text-grey-8"
              >
                {{ fmt(progresso * 100) }}%
              </q-circular-progress>
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
      <div v-if="porFazenda.length" class="row q-col-gutter-md">
        <template v-for="g in porFazenda" :key="g.codfazenda">
          <div class="col-12">
            <q-card bordered flat>
              <q-item>
                <q-item-section avatar>
                  <q-avatar color="green-1" text-color="green-8" icon="agriculture" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-subtitle1">{{ g.fazenda }}</q-item-label>
                  <q-item-label caption>
                    {{ fmt(g.area, 1) }} ha · {{ fmt(g.sacas) }} / {{ fmt(g.expectativa) }} sc ·
                    <span class="text-green-8 text-weight-medium"
                      >{{ fmt(g.produtividade, 1) }} sc/ha</span
                    >
                  </q-item-label>
                  <q-linear-progress
                    v-if="g.expectativa > 0"
                    :value="g.progresso"
                    color="green-6"
                    track-color="grey-3"
                    size="6px"
                    rounded
                    class="q-mt-xs"
                  />
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

              <MapaTalhoes
                v-if="g.comGeo.length"
                :talhoes="g.comGeo"
                id-key="codplantio"
                height="300px"
                estatico
                @select="selecionarPlantio"
              />
              <q-separator v-if="g.comGeo.length" />

              <!-- Talhões em 2 colunas (col-6) -->
              <div class="row q-col-gutter-sm q-pa-sm">
                <div v-for="l in g.plantios" :key="l.codplantio" class="col-12 col-sm-6">
                  <q-card flat bordered class="full-height" :class="{ 'bg-grey-2': l.inativo }">
                    <q-item>
                      <q-item-section avatar>
                        <q-avatar
                          text-color="white"
                          icon="place"
                          :style="{ backgroundColor: corTalhao(l) }"
                        />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label class="text-weight-medium">
                          {{ l.talhao || `Talhão ${l.codplantio}` }}
                          ·
                          {{ nomeVariedade(l) || 'sem variedade' }}
                        </q-item-label>
                        <q-item-label caption>
                          Plantado {{ fmt(l.areaplantada, 1) }} ha
                          <span v-if="l.dataplantio"> em {{ formataData(l.dataplantio) }} </span>
                        </q-item-label>
                        <q-item-label caption>
                          <span v-if="l.expectativa > 0">
                            Expectativa {{ fmt(l.expectativa) }} sc ({{
                              fmt(l.expectativaha, 1)
                            }}
                            sc/ha)</span
                          >
                          <q-badge
                            v-if="!l.geometria"
                            color="grey-5"
                            label="sem mapa"
                            class="q-ml-xs"
                          />
                        </q-item-label>
                        <!-- <q-linear-progress
                          :value="l.progresso"
                          color="green-6"
                          track-color="grey-3"
                          size="6px"
                          rounded
                          class="q-mt-xs"
                        /> -->
                      </q-item-section>
                      <!-- <q-item-section side class="text-right">
                        <q-item-label class="text-weight-bold text-green-8">
                          {{ fmt(l.produtividade, 1) }} sc/ha
                        </q-item-label>
                        <q-item-label caption
                          >{{ fmt(l.sacas) }} / {{ fmt(l.expectativa) }} sc</q-item-label
                        >
                      </q-item-section> -->
                      <q-item-section side>
                        <div class="row items-center no-wrap">
                          <MgInfoCriacao :registro="l" />
                          <q-btn
                            flat
                            dense
                            round
                            size="sm"
                            color="grey-7"
                            icon="edit"
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
                            @click="store.inativarPlantio(codsafra, l)"
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
                            @click="store.excluirPlantio(codsafra, l)"
                          >
                            <q-tooltip>Excluir</q-tooltip>
                          </q-btn>
                        </div>
                      </q-item-section>
                    </q-item>

                    <!-- Ha colhido (slider): dirige produtividade real / produção / disponível -->
                    <q-card-section class="q-pt-none q-pb-sm">
                      <div class="row items-center no-wrap text-caption q-mb-xs">
                        <span class="col text-grey-7">Colhido</span>
                        <span
                          :class="
                            Number(l.hacolhido) >= Number(l.areaplantada) &&
                            Number(l.areaplantada) > 0
                              ? 'text-green-8 text-weight-medium'
                              : 'text-grey-8'
                          "
                        >
                          {{ fmt(l.hacolhido, 1) }} / {{ fmt(l.areaplantada, 1) }} ha<span
                            v-if="
                              Number(l.hacolhido) >= Number(l.areaplantada) &&
                              Number(l.areaplantada) > 0
                            "
                          >
                            · finalizado</span
                          >
                        </span>
                      </div>
                      <q-slider
                        :model-value="Number(l.hacolhido) || 0"
                        :min="0"
                        :max="Number(l.areaplantada) || 1"
                        :step="0.01"
                        color="green-6"
                        track-color="grey-3"
                        @change="(v) => salvarHacolhido(l, v)"
                      />
                    </q-card-section>
                  </q-card>
                </div>
              </div>
            </q-card>
          </div>
        </template>
      </div>

      <!-- Vazio -->
      <MgEmptyState v-else icon="agriculture">
        Nenhum talhão plantado nesta safra ainda. Use <q-icon name="add" /> para plantar o primeiro.
      </MgEmptyState>
    </div>

    <!-- Wizard: escolher fazenda → talhão base → confirmar/ajustar polígono -->
    <PlantioWizardDialog
      v-model="store.dialogPlantio"
      :cad="plantioCad"
      :safra="safra"
      :fazendas="fazendas"
      :talhoes-base="talhoesBase"
      :variedades="variedadesDaCultura"
      :plantios="linhas"
    />

    <!-- Dialog Safra (edição) — mesmo form do cadastro -->
    <q-dialog v-model="store.dialogSafra">
      <q-card flat style="width: 440px; max-width: 95vw">
        <q-form @submit.prevent="store.salvarSafra()">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">Editar Safra</div>
          </q-card-section>
          <q-card-section class="q-pt-md">
            <SafraForm :form="store.formSafra" />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn
              type="submit"
              flat
              label="Salvar"
              color="primary"
              :loading="store.salvandoSafra"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
