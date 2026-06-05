<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import { useCargaStore } from 'src/stores/carga'
import MgInputValor from '@components/MgInputValor.vue'

const route = useRoute()
const codsafra = Number(route.params.codsafra)

const plantioCad = useCadastro(`safra/${codsafra}/plantio`, 'codplantio', 'Plantio')
const store = useCargaStore()
const { colhidoPorPlantio } = storeToRefs(store)

const safra = ref(null)
const talhoes = ref([])
const variedades = ref([])

const pesosaca = computed(() => Number(safra.value?.Cultura?.pesosaca) || 60)
const codcultura = computed(() => safra.value?.Cultura?.codcultura)

const variedadesDaCultura = computed(() =>
  variedades.value.filter((v) => v.codcultura === codcultura.value && !v.inativo),
)

// Plantios + produtividade (colhido vem das cargas, rateado pelo %).
const linhas = computed(() =>
  plantioCad.items.map((p) => {
    const kg = colhidoPorPlantio.value[p.codplantio] || 0
    const sacas = kg / pesosaca.value
    const area = Number(p.areaplantada) || 0
    return { ...p, kg, sacas, produtividade: area > 0 ? sacas / area : 0 }
  }),
)
const totalArea = computed(() =>
  linhas.value.reduce((s, l) => s + (Number(l.areaplantada) || 0), 0),
)
const totalKg = computed(() => linhas.value.reduce((s, l) => s + l.kg, 0))
const totalSacas = computed(() => totalKg.value / pesosaca.value)
const prodMedia = computed(() => (totalArea.value > 0 ? totalSacas.value / totalArea.value : 0))

// Quebra da produtividade: por talhão (plantio, com CRUD) ou agregada por variedade.
const agrupamento = ref('TALHAO')
const agrupamentos = [
  { label: 'Por talhão', value: 'TALHAO' },
  { label: 'Por variedade', value: 'VARIEDADE' },
]
const linhasPorVariedade = computed(() => {
  const mapa = {}
  for (const l of linhas.value) {
    const chave = l.codvariedade || 0
    if (!mapa[chave]) {
      mapa[chave] = {
        codvariedade: chave,
        variedade: nomeVariedade(l) || 'Sem variedade',
        area: 0,
        kg: 0,
      }
    }
    mapa[chave].area += Number(l.areaplantada) || 0
    mapa[chave].kg += l.kg
  }
  return Object.values(mapa).map((g) => {
    const sacas = g.kg / pesosaca.value
    return { ...g, sacas, produtividade: g.area > 0 ? sacas / g.area : 0 }
  })
})
const maxProd = computed(() => {
  const base = agrupamento.value === 'VARIEDADE' ? linhasPorVariedade.value : linhas.value
  return Math.max(1, ...base.map((l) => l.produtividade))
})

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

function salvarPlantio() {
  plantioCad.salvar((f) => ({
    codtalhao: f.codtalhao,
    codvariedade: f.codvariedade,
    areaplantada: f.areaplantada,
  }))
}

function nomeTalhao(p) {
  return p.Talhao?.talhao || `Talhão ${p.codtalhao}`
}
function nomeVariedade(p) {
  return p.Variedade?.variedade || ''
}

onMounted(async () => {
  const [{ data: s }, { data: t }, { data: v }] = await Promise.all([
    api.get(`v1/safra/${codsafra}`),
    api.get('v1/talhao'),
    api.get('v1/variedade'),
  ])
  safra.value = s
  talhoes.value = t.data ?? t
  variedades.value = v.data ?? v
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
          <q-avatar color="light-green-1" text-color="light-green-9" icon="eco" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">{{ safra?.safra || 'Safra' }}</div>
            <div class="text-caption text-grey-7">
              {{ safra?.Cultura?.cultura }}<span v-if="periodo"> · {{ periodo }}</span>
            </div>
          </div>
          <q-btn flat color="green-7" icon="local_shipping" label="Pátio" :to="{ name: 'patio' }" />
        </q-card-section>
      </q-card>

      <!-- KPIs -->
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

      <!-- Plantios + produtividade -->
      <q-card bordered flat>
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1">Plantio e produtividade</q-item-label>
            <q-item-label caption>O que está plantado em cada talhão nesta safra</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-btn flat round size="sm" color="primary" icon="add" @click="plantioCad.abrirNovo()">
              <q-tooltip>Plantar talhão</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
        <q-separator />

        <q-card-section>
          <q-btn-toggle
            v-model="agrupamento"
            :options="agrupamentos"
            no-caps
            unelevated
            toggle-color="primary"
            color="grey-3"
            text-color="grey-9"
          />
        </q-card-section>
        <q-separator />

        <q-list v-if="agrupamento === 'TALHAO'" separator>
          <q-item v-for="l in linhas" :key="l.codplantio" :class="{ 'bg-grey-2': l.inativo }">
            <q-item-section avatar>
              <q-avatar color="brown-5" text-color="white" icon="grass" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ nomeTalhao(l) }}</q-item-label>
              <q-item-label caption
                >{{ nomeVariedade(l) }} · {{ fmt(l.areaplantada, 1) }} ha</q-item-label
              >
              <q-linear-progress
                :value="l.produtividade / maxProd"
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
              <q-btn flat round size="sm" color="grey-7" icon="more_vert">
                <q-menu>
                  <q-list style="min-width: 150px">
                    <q-item clickable v-close-popup @click="plantioCad.editar(l)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section>Editar</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="plantioCad.alternarInativo(l)">
                      <q-item-section avatar>
                        <q-icon :name="l.inativo ? 'play_arrow' : 'pause'" />
                      </q-item-section>
                      <q-item-section>{{ l.inativo ? 'Ativar' : 'Inativar' }}</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="plantioCad.excluir(l)">
                      <q-item-section avatar><q-icon name="delete" /></q-item-section>
                      <q-item-section>Excluir</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-item-section>
          </q-item>

          <q-item v-if="!linhas.length">
            <q-item-section class="text-grey-6 text-center">
              Nenhum talhão plantado nesta safra ainda.
            </q-item-section>
          </q-item>
        </q-list>

        <q-list v-else separator>
          <q-item v-for="v in linhasPorVariedade" :key="v.codvariedade">
            <q-item-section avatar>
              <q-avatar color="teal-1" text-color="teal-8" icon="spa" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ v.variedade }}</q-item-label>
              <q-item-label caption>{{ fmt(v.area, 1) }} ha</q-item-label>
              <q-linear-progress
                :value="v.produtividade / maxProd"
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
              <q-item-label caption>{{ fmt(v.sacas) }} sc colhidas</q-item-label>
            </q-item-section>
          </q-item>

          <q-item v-if="!linhasPorVariedade.length">
            <q-item-section class="text-grey-6 text-center">
              Nenhum talhão plantado nesta safra ainda.
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Dialog Plantio -->
      <q-dialog v-model="plantioCad.dialog">
        <q-card bordered flat style="width: 440px; max-width: 90vw">
          <q-form @submit="salvarPlantio">
            <q-card-section>
              <div class="text-h6">
                {{ plantioCad.isNovo ? 'Plantar talhão' : 'Editar plantio' }}
              </div>
            </q-card-section>
            <q-card-section class="q-gutter-md">
              <q-select
                v-model="plantioCad.form.codtalhao"
                :options="talhoes"
                option-value="codtalhao"
                option-label="talhao"
                emit-value
                map-options
                outlined
                label="Talhão"
              />
              <q-select
                v-model="plantioCad.form.codvariedade"
                :options="variedadesDaCultura"
                option-value="codvariedade"
                option-label="variedade"
                emit-value
                map-options
                outlined
                label="Variedade"
              />
              <MgInputValor
                v-model="plantioCad.form.areaplantada"
                :decimals="2"
                suffix="ha"
                label="Área plantada"
              />
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn
                type="submit"
                unelevated
                label="Salvar"
                color="primary"
                :loading="plantioCad.salvando"
              />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
