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
import MgInputValor from '@components/MgInputValor.vue'
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
const sinc = useSincronizacaoStore()
const { online } = storeToRefs(sinc)

// Listas de referência da tela (não são entidades safra): fazendas/variedades e
// o layout base de talhões — usados pelo wizard e pela agregação por fazenda.
const fazendas = ref([])
const variedades = ref([])
const talhoesBase = ref([]) // layout base de todas as fazendas

// Como agrupar a tabela de talhões: por variedade (padrão) ou por talhão.
const agrupamento = ref('variedade')

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

// Rótulo primário da linha = a OUTRA dimensão do agrupamento, com prefixo
// (agrupado por variedade → linha é o talhão; e vice-versa).
function rotuloLinha(p) {
  return agrupamento.value === 'variedade'
    ? `Talhão ${p.talhao || p.codplantio}`
    : `Variedade ${p.Variedade?.variedade || 'sem variedade'}`
}
// Rótulo do grupo (a dimensão do agrupamento), com prefixo.
function rotuloGrupo(nome) {
  return agrupamento.value === 'variedade' ? `Variedade ${nome}` : `Talhão ${nome}`
}
// Médias (esperada/realizada) da linha, prontas do backend.
function mediaLinha(p) {
  return comercial.value?.plantios?.[p.codplantio] || {}
}

// Um card por fazenda: agrupa os plantios pela dimensão escolhida só p/ LAYOUT;
// os totais/médias de cada grupo e da fazenda vêm prontos do backend (comercial).
const fazendasView = computed(() => {
  const modo = agrupamento.value
  const comer = comercial.value
  const porFaz = {}
  for (const p of plantios.value) (porFaz[p.codfazenda] ??= []).push(p)

  const cards = Object.entries(porFaz).map(([cod, ps]) => {
    const codfazenda = Number(cod)
    const cFaz = comer?.fazendas?.find((f) => f.codfazenda === codfazenda) || null
    const totais = modo === 'variedade' ? cFaz?.porVariedade : cFaz?.porTalhao

    const grupos = {}
    for (const p of ps) {
      const key = modo === 'variedade' ? p.codvariedade : p.talhao || `Talhão ${p.codplantio}`
      const nome =
        modo === 'variedade'
          ? p.Variedade?.variedade || 'sem variedade'
          : p.talhao || `Talhão ${p.codplantio}`
      ;(grupos[key] ??= { key, nome, linhas: [] }).linhas.push(p)
    }

    const listaGrupos = Object.values(grupos)
      .map((g) => ({
        ...g,
        linhas: [...g.linhas].sort((a, b) => rotuloLinha(a).localeCompare(rotuloLinha(b), 'pt-BR')),
        total: totais?.[g.key] || null,
      }))
      .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'))

    return {
      codfazenda,
      fazenda: fazendas.value.find((x) => x.codfazenda === codfazenda)?.fazenda || `Fazenda ${cod}`,
      comGeo: ps.filter((p) => p.geometria),
      grupos: listaGrupos,
      total: cFaz,
    }
  })
  return cards.sort((a, b) => a.fazenda.localeCompare(b.fazenda, 'pt-BR'))
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

// Colhido (popup na célula): grava o hacolhido (clampado em [0, área]) e refresca
// os KPIs/médias (produtividade / produção / disponível vêm prontos do backend).
async function salvarHacolhido(l, v) {
  const area = Number(l.areaplantada) || 0
  let valor = Math.max(0, Number(v) || 0)
  if (area > 0) valor = Math.min(valor, area)
  await store.salvarHacolhido(codsafra, l.codplantio, valor)
  await recarregarComercial()
}
// Botão "Finalizar" do popup: colhido = área plantada (talhão 100% colhido).
function finalizarColhido(scope, l) {
  scope.value = Number(l.areaplantada) || 0
  scope.set()
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

      <!-- Título da seção + agrupamento + adicionar (escolhe a fazenda no dialog) -->
      <div class="row items-center q-mb-sm">
        <div class="col text-subtitle1 text-weight-medium">Plantios por fazenda</div>
        <q-btn-toggle
          v-model="agrupamento"
          flat
          no-caps
          toggle-color="primary"
          color="grey-7"
          :options="[
            { label: 'Por variedade', value: 'variedade' },
            { label: 'Por talhão', value: 'talhao' },
          ]"
        />
        <q-btn
          flat
          round
          size="sm"
          color="primary"
          icon="add"
          class="q-ml-sm"
          @click="novoPlantio()"
        >
          <q-tooltip>Plantar talhão</q-tooltip>
        </q-btn>
      </div>

      <!-- Um card por fazenda: cabeçalho c/ totais + mapa + tabela agrupada -->
      <div v-if="fazendasView.length" class="row q-col-gutter-md">
        <div v-for="g in fazendasView" :key="g.codfazenda" class="col-12">
          <q-card bordered flat>
            <q-item>
              <q-item-section avatar>
                <q-avatar color="green-1" text-color="green-8" icon="agriculture" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle1">{{ g.fazenda }}</q-item-label>
                <q-item-label v-if="g.total" caption>
                  {{ fmt(g.total.area, 1) }} ha · {{ fmt(g.total.expectativa) }} sc prev. ·
                  {{ fmt(g.total.colhido) }} sc colh. ·
                  <span class="text-grey-8">{{ fmt(g.total.esperada, 1) }}</span>
                  <template v-if="g.total.realizada != null">
                    /
                    <span class="text-green-8 text-weight-medium">{{
                      fmt(g.total.realizada, 1)
                    }}</span> </template
                  >sc/ha
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

            <MapaTalhoes
              v-if="g.comGeo.length"
              :talhoes="g.comGeo"
              id-key="codplantio"
              height="300px"
              estatico
              @select="selecionarPlantio"
            />
            <q-separator v-if="g.comGeo.length" />

            <!-- Tabela agrupada por variedade OU talhão (faixa + linhas + total) -->
            <div class="q-pa-md" style="overflow-x: auto">
              <q-markup-table flat wrap-cells>
                <thead>
                  <tr>
                    <th class="text-left"></th>
                    <th class="text-right">Plantado</th>
                    <th class="text-right">Previsão</th>
                    <th class="text-right">sc/ha (esp. / real.)</th>
                    <th class="text-left" style="min-width: 150px">Colhido</th>
                    <th class="text-right">Ações</th>
                  </tr>
                </thead>
                <tbody v-for="(grp, gi) in g.grupos" :key="grp.key">
                  <!-- espaço entre grupos (inerte: sem hover, sem borda) -->
                  <tr v-if="gi > 0">
                    <td
                      colspan="6"
                      style="height: 18px; padding: 0; border: none; pointer-events: none"
                    ></td>
                  </tr>
                  <!-- faixa: nome do grupo -->
                  <tr class="bg-grey-2">
                    <td colspan="6" class="text-weight-medium q-py-sm">
                      {{ rotuloGrupo(grp.nome) }}
                    </td>
                  </tr>
                  <!-- linhas do grupo -->
                  <tr
                    v-for="l in grp.linhas"
                    :key="l.codplantio"
                    :class="{ 'text-grey': l.inativo }"
                  >
                    <td class="text-left">
                      <div class="row items-center no-wrap">
                        <q-avatar
                          size="14px"
                          class="q-mr-sm"
                          :style="{ backgroundColor: corTalhao(l) }"
                        />
                        {{ rotuloLinha(l) }}
                        <q-badge
                          v-if="!l.geometria"
                          color="grey-5"
                          label="sem mapa"
                          class="q-ml-xs"
                        />
                      </div>
                    </td>
                    <td class="text-right">
                      {{ fmt(l.areaplantada, 1) }} ha
                      <div v-if="l.dataplantio" class="text-caption text-grey-6">
                        {{ formataData(l.dataplantio) }}
                      </div>
                    </td>
                    <td class="text-right">{{ fmt(l.expectativasacas) }} sc</td>
                    <td class="text-right">
                      <span class="text-grey-8">{{ fmt(mediaLinha(l).esperada, 1) }}</span>
                      <span v-if="mediaLinha(l).realizada != null">
                        /
                        <span class="text-green-8 text-weight-medium">{{
                          fmt(mediaLinha(l).realizada, 1)
                        }}</span>
                      </span>
                      <span v-else class="text-grey-5"> / —</span>
                    </td>
                    <td class="cursor-pointer">
                      <q-linear-progress
                        :value="
                          Number(l.areaplantada) > 0
                            ? Math.min(1, (Number(l.hacolhido) || 0) / Number(l.areaplantada))
                            : 0
                        "
                        color="green-6"
                        track-color="grey-3"
                        size="8px"
                        rounded
                      />
                      <div class="text-caption text-grey-7">
                        {{ fmt(l.hacolhido, 1) }} / {{ fmt(l.areaplantada, 1) }} ha
                      </div>
                      <q-popup-edit
                        v-slot="scope"
                        :model-value="Number(l.hacolhido) || 0"
                        @save="(val) => salvarHacolhido(l, val)"
                      >
                        <div style="min-width: 260px">
                          <div class="text-caption text-grey-7 q-mb-sm">
                            Colhido (ha) — {{ rotuloLinha(l) }}
                          </div>
                          <MgInputValor
                            v-model="scope.value"
                            :decimals="2"
                            :min="0"
                            :max="Number(l.areaplantada) || null"
                            :suffix="`/ ${fmt(l.areaplantada, 1)} ha`"
                            autofocus
                            @keyup.enter="scope.set"
                          />
                          <q-slider
                            v-model="scope.value"
                            :min="0"
                            :max="Number(l.areaplantada) || 1"
                            :step="0.01"
                            color="green-6"
                            track-color="grey-3"
                            label
                            :label-value="`${fmt(scope.value, 1)} ha`"
                            class="q-mt-md q-px-xs"
                          />
                          <div class="row items-center justify-between q-mt-sm">
                            <q-btn
                              flat
                              label="Finalizar"
                              color="green-7"
                              @click="finalizarColhido(scope, l)"
                            />
                            <div>
                              <q-btn flat label="Cancelar" color="grey-8" @click="scope.cancel" />
                              <q-btn flat label="Salvar" color="primary" @click="scope.set" />
                            </div>
                          </div>
                        </div>
                      </q-popup-edit>
                    </td>
                    <td class="text-right">
                      <div class="row items-center no-wrap justify-end">
                        <MgInfoCriacao :registro="l" />
                        <q-btn
                          flat
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
                          round
                          size="sm"
                          color="grey-7"
                          icon="delete"
                          @click="store.excluirPlantio(codsafra, l)"
                        >
                          <q-tooltip>Excluir</q-tooltip>
                        </q-btn>
                      </div>
                    </td>
                  </tr>
                  <!-- total do grupo (pronto do backend); dispensável com 1 linha só -->
                  <tr v-if="grp.total && grp.linhas.length > 1" class="text-weight-medium">
                    <td class="text-left text-grey-7">Total</td>
                    <td class="text-right">{{ fmt(grp.total.area, 1) }} ha</td>
                    <td class="text-right">{{ fmt(grp.total.expectativa) }} sc</td>
                    <td class="text-right">
                      <span class="text-grey-8">{{ fmt(grp.total.esperada, 1) }}</span>
                      <span v-if="grp.total.realizada != null">
                        / <span class="text-green-8">{{ fmt(grp.total.realizada, 1) }}</span>
                      </span>
                      <span v-else class="text-grey-5"> / —</span>
                    </td>
                    <td class="text-left text-caption text-grey-7">
                      {{ fmt(grp.total.colhido) }} sc colh.
                    </td>
                    <td></td>
                  </tr>
                </tbody>
              </q-markup-table>
            </div>
          </q-card>
        </div>
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
      :plantios="plantios"
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
