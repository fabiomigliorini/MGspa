<script setup>
// Filtros da listagem de romaneios. Sem botão "Aplicar": o v-model aponta
// direto pra store e um watch debounced refaz a busca — mesmo padrão do
// NotasFiltrosDrawer do app notas.
//
// Os selects daqui NÃO são os SelectUnidade/SelectContrato/SelectTalhao do
// pátio: aqueles leem o Dexie, que só é populado ao abrir /carga. Quem cai
// direto em /cargas teria selects vazios e sem erro nenhum na tela.
import { onMounted, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { storeToRefs } from 'pinia'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import { useCargaListagemStore } from 'src/stores/cargaListagem'
import { SENTIDOS, ETAPA_META } from 'src/utils/carga'

const store = useCargaListagemStore()
const { filtros, safras, culturas, unidades, contratos, plantios, carregandoPlantios } =
  storeToRefs(store)

const SENTIDO_OPCOES = SENTIDOS.map((s) => ({ value: s.value, label: s.label, icon: s.icon }))

const ETAPA_OPCOES = Object.entries(ETAPA_META).map(([value, m]) => ({
  value,
  label: m.label,
  icon: m.icon,
}))

const PAPEL_OPCOES = [
  { value: null, label: 'Qualquer lado' },
  { value: 'ORIGEM', label: 'Só como origem' },
  { value: 'DESTINO', label: 'Só como destino' },
]

// Espelha CargaRelatorioService::AGRUPAMENTOS — mudou lá, muda aqui.
const AGRUPAMENTOS = [
  { value: 'nenhum', label: 'Sem agrupamento' },
  { value: 'dia', label: 'Dia' },
  { value: 'mes', label: 'Mês' },
  { value: 'sentido', label: 'Tipo de romaneio' },
  { value: 'etapa', label: 'Etapa' },
  { value: 'safra', label: 'Safra' },
  { value: 'cultura', label: 'Cultura' },
  { value: 'unidade', label: 'Unidade armazenadora' },
  { value: 'plantio', label: 'Talhão' },
  { value: 'contrato', label: 'Contrato' },
  { value: 'pessoa', label: 'Cliente / fornecedor' },
  { value: 'motorista', label: 'Motorista' },
  { value: 'placa', label: 'Placa' },
]

// A mesma unidade pode se chamar "Silo 1" em dois tipos diferentes; o tipo no
// rótulo evita escolher o armazém do terceiro achando que é o próprio.
const TIPO_UNIDADE = { PROPRIO: 'Próprio', TERCEIRO: 'Terceiro', SILOBAG: 'Silo bag' }

function rotuloUnidade(u) {
  const tipo = TIPO_UNIDADE[u.tipo]
  return tipo ? `${u.unidadearmazenadora} (${tipo})` : u.unidadearmazenadora
}

function rotuloContrato(c) {
  const pessoa = c.Pessoa?.fantasia || c.Pessoa?.pessoa
  return pessoa ? `${c.contrato} — ${pessoa}` : c.contrato
}

function rotuloPlantio(p) {
  const variedade = p.Variedade?.variedade
  const nome = p.talhao || `Talhão ${p.codplantio}`
  return variedade ? `${nome} — ${variedade}` : nome
}

const buscarDebounced = useDebounceFn(() => store.buscar(true), 800)
watch(filtros, () => buscarDebounced(), { deep: true })

// Talhão depende da safra: trocar a safra invalida a escolha anterior.
watch(
  () => filtros.value.codsafra,
  (codsafra) => {
    filtros.value.codplantio = null
    store.carregarPlantios(codsafra)
  },
)

onMounted(() => {
  store.carregarCadastros()
  if (filtros.value.codsafra) store.carregarPlantios(filtros.value.codsafra)
})
</script>

<template>
  <div class="q-pa-md">
    <div class="row items-center no-wrap">
      <div class="text-subtitle1 text-grey-8">
        Filtros
        <q-badge v-if="store.contagemFiltros" color="primary" class="q-ml-xs">
          {{ store.contagemFiltros }}
        </q-badge>
      </div>
      <q-space />
      <q-btn
        v-if="store.contagemFiltros"
        flat
        round
        icon="close"
        color="grey-7"
        @click="store.limparFiltros()"
      >
        <q-tooltip>Limpar filtros</q-tooltip>
      </q-btn>
    </div>
  </div>

  <q-separator />

  <div class="q-pa-md q-gutter-y-md">
    <div class="text-caption text-grey-7">Período e safra</div>
    <MgInputData v-model="filtros.data_inicio" type="date" label="De" />
    <MgInputData v-model="filtros.data_fim" type="date" label="Até" />
    <q-select
      v-model="filtros.codsafra"
      :options="safras"
      option-value="codsafra"
      option-label="safra"
      emit-value
      map-options
      outlined
      clearable
      label="Safra"
    />
    <q-select
      v-model="filtros.codcultura"
      :options="culturas"
      option-value="codcultura"
      option-label="cultura"
      emit-value
      map-options
      outlined
      clearable
      label="Cultura"
    />
  </div>

  <q-separator />

  <div class="q-pa-md q-gutter-y-md">
    <div class="text-caption text-grey-7">Tipo e situação</div>
    <q-select
      v-model="filtros.sentido"
      :options="SENTIDO_OPCOES"
      emit-value
      map-options
      outlined
      clearable
      label="Tipo de romaneio"
    />
    <q-select
      v-model="filtros.etapa"
      :options="ETAPA_OPCOES"
      emit-value
      map-options
      outlined
      clearable
      label="Etapa"
    />
    <q-toggle v-model="filtros.canceladas" label="Incluir canceladas" />
  </div>

  <q-separator />

  <div class="q-pa-md q-gutter-y-md">
    <div class="text-caption text-grey-7">Origem e destino</div>
    <q-select
      v-model="filtros.codunidadearmazenadora"
      :options="unidades"
      :option-label="rotuloUnidade"
      option-value="codunidadearmazenadora"
      emit-value
      map-options
      outlined
      clearable
      label="Unidade armazenadora"
    />
    <q-select
      v-model="filtros.codplantio"
      :options="plantios"
      :option-label="rotuloPlantio"
      option-value="codplantio"
      :disable="!filtros.codsafra"
      :loading="carregandoPlantios"
      :hint="filtros.codsafra ? '' : 'Escolha a safra primeiro'"
      emit-value
      map-options
      outlined
      clearable
      label="Talhão"
    />
    <q-select
      v-model="filtros.codcontrato"
      :options="contratos"
      :option-label="rotuloContrato"
      option-value="codcontrato"
      emit-value
      map-options
      outlined
      clearable
      label="Contrato"
    />
    <MgSelectPessoa
      v-model="filtros.codpessoacontrato"
      label="Cliente / fornecedor"
      clearable
      :bottom-slots="false"
    />
    <q-select
      v-model="filtros.papel"
      :options="PAPEL_OPCOES"
      emit-value
      map-options
      outlined
      label="Lado"
      hint="Aplica-se aos campos acima"
    />
  </div>

  <q-separator />

  <div class="q-pa-md q-gutter-y-md">
    <div class="text-caption text-grey-7">Caminhão</div>
    <q-input v-model="filtros.placa" outlined clearable label="Placa" />
    <q-input v-model="filtros.placacarreta" outlined clearable label="Carreta" />
    <q-input v-model="filtros.motorista" outlined clearable label="Motorista" />
    <q-input
      v-model.number="filtros.codcarga"
      type="number"
      outlined
      clearable
      label="Nº do romaneio"
    />
  </div>

  <q-separator />

  <div class="q-pa-md q-gutter-y-md">
    <div class="text-caption text-grey-7">Relatório</div>
    <q-select
      v-model="store.agrupar"
      :options="AGRUPAMENTOS"
      emit-value
      map-options
      outlined
      label="Agrupar por"
      hint="Vale só para o PDF"
    />
  </div>
</template>
