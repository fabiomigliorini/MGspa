<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { corTalhao, sugerirCor } from 'src/utils/coresTalhao'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgMapaTalhoes from 'components/MgMapaTalhoes.vue'
import MgIconeCultura from 'components/MgIconeCultura.vue'
import MgSafraForm from 'components/MgSafraForm.vue'
import MgContratosSafra from 'components/MgContratosSafra.vue'
import PlantioWizardDialog from 'components/PlantioWizardDialog.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const codsafra = Number(route.params.codsafra)

const safraCad = useCadastro('safra', 'codsafra', 'Safra')
const plantioCad = useCadastro(`safra/${codsafra}/plantio`, 'codplantio', 'Plantio')
const store = useCargaStore()
const { colhidoPorPlantio } = storeToRefs(store)
const sinc = useSincronizacaoStore()
const { online } = storeToRefs(sinc)

const safra = ref(null)
const fazendas = ref([])
const variedades = ref([])
const talhoesBase = ref([]) // layout base de todas as fazendas (p/ partir o desenho)
const comercial = ref(null) // KPIs comerciais (contratos da safra), via backend

const pesosaca = computed(() => Number(safra.value?.Cultura?.pesosaca) || 60)
const codcultura = computed(() => safra.value?.codcultura ?? safra.value?.Cultura?.codcultura)

const variedadesDaCultura = computed(() =>
  variedades.value.filter((v) => v.codcultura === codcultura.value && !v.inativo),
)

// Plantios da safra (todas as fazendas) + produtividade (colhido vem das cargas)
// e progresso da colheita (colhido ÷ expectativa).
const linhas = computed(() =>
  plantioCad.items.map((p) => {
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

// KPIs globais da safra (somando todas as fazendas).
const totalArea = computed(() =>
  linhas.value.reduce((s, l) => s + (Number(l.areaplantada) || 0), 0),
)
const totalKg = computed(() => linhas.value.reduce((s, l) => s + l.kg, 0))
const totalSacas = computed(() => totalKg.value / pesosaca.value)
const prodMedia = computed(() => (totalArea.value > 0 ? totalSacas.value / totalArea.value : 0))
const totalExpectativa = computed(() => linhas.value.reduce((s, l) => s + l.expectativa, 0))
const progressoSafra = computed(() =>
  totalExpectativa.value > 0 ? Math.min(1, totalSacas.value / totalExpectativa.value) : 0,
)

// KPIs comerciais (rollup dos contratos da safra, do backend). Os saldos cruzam
// o lado comercial (online) com a produção (offline): disponível p/ vender =
// expectativa − contratado; saldo no silo = colhido − entregue.
const contratado = computed(() => Number(comercial.value?.contratado) || 0)
const entregue = computed(() => Number(comercial.value?.entregue) || 0)
const fixo = computed(() => Number(comercial.value?.fixo) || 0)
const afixar = computed(() => Number(comercial.value?.afixar) || 0)
const disponivel = computed(() => totalExpectativa.value - contratado.value)
const saldoSilo = computed(() => totalSacas.value - entregue.value)
const saldoEmbarcar = computed(() => Number(comercial.value?.saldoaembarcar) || 0)

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
    return {
      ...g,
      sacas,
      produtividade: g.area > 0 ? sacas / g.area : 0,
      comGeo: g.plantios.filter((p) => p.geometria),
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
async function recarregarComercial() {
  if (!online.value) return
  try {
    const { data } = await api.get(`v1/safra/${codsafra}/comercial`)
    comercial.value = data
  } catch {
    // KPI comercial degrada em silêncio (offline / sem dado); produção segue.
  }
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
  const usadas = plantioCad.items.map((p) => p.cor).filter(Boolean)
  plantioCad.abrirNovo({
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
  plantioCad.editar({ ...p, cor: corTalhao(p) })
}
function selecionarPlantio(codplantio) {
  const p = plantioCad.items.find((x) => x.codplantio === codplantio)
  if (p) editarPlantio(p)
}

async function carregarSafra() {
  // A API embrulha objeto único em { data: {...} }; desembrulha (igual ao resto).
  const { data } = await api.get(`v1/safra/${codsafra}`)
  safra.value = data.data ?? data
}

// Safra — edição/ativação/exclusão no cabeçalho do detalhe (a lista só navega).
function editarSafra() {
  safraCad.editar(safra.value)
}
async function salvarSafra() {
  await safraCad.salvar()
  if (!safraCad.dialog) await carregarSafra()
}
async function alternarInativoSafra() {
  await safraCad.alternarInativo(safra.value)
  await carregarSafra()
}
function excluirSafra() {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir a safra ${safra.value?.safra}?`,
    cancel: true,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/safra/${codsafra}`)
      notifySuccess('Excluído!')
      router.push({ name: 'safras' })
    } catch (e) {
      notifyError(e)
    }
  })
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
            <MgIconeCultura :codcultura="codcultura" class="q-ml-sm" />
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
              :to="{ name: 'patio' }"
            />
          </div>
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
              <div class="text-caption text-grey-7">Expectativa</div>
              <div class="text-h6">{{ fmt(totalExpectativa) }} sc</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Progresso da colheita da safra (colhido ÷ expectativa) -->
      <q-card v-if="totalExpectativa > 0" flat bordered class="q-mb-md">
        <q-card-section>
          <div class="row items-center no-wrap q-mb-xs">
            <div class="col text-subtitle2 text-grey-8">Progresso da colheita</div>
            <div class="text-subtitle2 text-weight-medium">
              {{ fmt(totalSacas) }} / {{ fmt(totalExpectativa) }} sc
              <span class="text-grey-7">· {{ fmt(progressoSafra * 100) }}%</span>
            </div>
          </div>
          <q-linear-progress
            :value="progressoSafra"
            size="18px"
            color="green-6"
            track-color="grey-3"
            rounded
            stripe
          />
        </q-card-section>
      </q-card>

      <!-- KPIs comerciais (contratos da safra) -->
      <template v-if="online && comercial">
        <div class="text-subtitle1 text-weight-medium q-mb-sm">Comercial</div>
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-6 col-md-3">
            <q-card flat bordered>
              <q-card-section>
                <div class="text-caption text-grey-7">Contratado</div>
                <div class="text-h6">{{ fmt(contratado) }} sc</div>
                <div class="text-caption text-grey-6">
                  Disponível p/ vender {{ fmt(disponivel) }} sc
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered>
              <q-card-section>
                <div class="text-caption text-grey-7">Fixado</div>
                <div class="text-h6">{{ fmt(fixo) }} sc</div>
                <div class="text-caption text-grey-6">A fixar {{ fmt(afixar) }} sc</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered>
              <q-card-section>
                <div class="text-caption text-grey-7">Entregue</div>
                <div class="text-h6">{{ fmt(entregue) }} sc</div>
                <div class="text-caption text-grey-6">
                  Saldo a embarcar {{ fmt(saldoEmbarcar) }} sc
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered>
              <q-card-section>
                <div class="text-caption text-grey-7">Saldo no silo</div>
                <div class="text-h6 text-amber-9">{{ fmt(saldoSilo) }} sc</div>
                <div class="text-caption text-grey-6">colhido − entregue</div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Preço médio ponderado por moeda (barter fora) -->
        <q-card
          v-if="comercial.precomediobrl || comercial.precomediousd"
          flat
          bordered
          class="q-mb-md"
        >
          <q-card-section class="row q-col-gutter-xl items-center">
            <div v-if="comercial.precomediobrl" class="col-auto">
              <div class="text-caption text-grey-7">Preço médio R$</div>
              <div class="text-h6">{{ rs(comercial.precomediobrl) }} <small>/sc</small></div>
            </div>
            <div v-if="comercial.precomediousd" class="col-auto">
              <div class="text-caption text-grey-7">Preço médio US$</div>
              <div class="text-h6">
                US$ {{ fmt(comercial.precomediousd, 2) }} <small>/sc</small>
              </div>
              <div class="text-caption text-grey-6">dólar médio {{ rs(comercial.dolarmedio) }}</div>
            </div>
          </q-card-section>
        </q-card>
      </template>

      <!-- Título da seção + adicionar (escolhe a fazenda no dialog) -->
      <div class="row items-center q-mb-sm">
        <div class="col text-subtitle1 text-weight-medium">Plantios por fazenda</div>
        <q-btn flat round size="sm" color="primary" icon="add" @click="novoPlantio()">
          <q-tooltip>Plantar talhão</q-tooltip>
        </q-btn>
      </div>

      <!-- Um card por fazenda: mapa + lista por talhão + resultado -->
      <div class="row q-col-gutter-md">
        <template v-for="g in porFazenda" :key="g.codfazenda">
          <div class="col-md-6">
            <q-card bordered flat class="q-mb-md">
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

              <MgMapaTalhoes
                v-if="g.comGeo.length"
                :talhoes="g.comGeo"
                id-key="codplantio"
                height="300px"
                @select="selecionarPlantio"
              />
              <q-separator v-if="g.comGeo.length" />

              <q-list separator>
                <q-item
                  v-for="l in g.plantios"
                  :key="l.codplantio"
                  :class="{ 'bg-grey-2': l.inativo }"
                >
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
                      <span v-if="l.expectativa > 0"
                        >· exp {{ fmt(l.expectativaha, 1) }} sc/ha</span
                      >
                      <q-badge
                        v-if="!l.geometria"
                        color="grey-5"
                        label="sem mapa"
                        class="q-ml-xs"
                      />
                    </q-item-label>
                    <q-linear-progress
                      :value="l.progresso"
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
                    <q-item-label caption
                      >{{ fmt(l.sacas) }} / {{ fmt(l.expectativa) }} sc</q-item-label
                    >
                  </q-item-section>
                  <q-item-section side>
                    <div class="row items-center no-wrap">
                      <MgInfoCriacao :registro="l" />
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
          </div>
        </template>
      </div>

      <!-- Vazio -->
      <q-card v-if="!porFazenda.length" bordered flat>
        <q-card-section class="text-grey-6 text-center">
          Nenhum talhão plantado nesta safra ainda. Use <q-icon name="add" /> para plantar o
          primeiro.
        </q-card-section>
      </q-card>

      <!-- Contratos de venda desta safra -->
      <q-separator class="q-my-lg" />
      <MgContratosSafra
        :codsafra="codsafra"
        :codcultura="codcultura"
        :online="online"
        @changed="recarregarComercial"
      />
    </div>

    <!-- Wizard: escolher fazenda → talhão base → confirmar/ajustar polígono -->
    <PlantioWizardDialog
      v-model="plantioCad.dialog"
      :cad="plantioCad"
      :fazendas="fazendas"
      :talhoes-base="talhoesBase"
      :variedades="variedadesDaCultura"
      :plantios="linhas"
    />

    <!-- Dialog Safra (edição) — mesmo form do cadastro -->
    <q-dialog v-model="safraCad.dialog">
      <q-card flat style="width: 440px; max-width: 95vw">
        <q-form @submit="salvarSafra">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">Editar Safra</div>
          </q-card-section>
          <q-card-section class="q-pt-md">
            <MgSafraForm :cad="safraCad" />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn type="submit" flat label="Salvar" color="primary" :loading="safraCad.salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
