<script setup>
// Filtros da listagem de romaneios, no molde do ValeModeloLeftDrawer (negocios):
// FilterDrawerShell + FilterGroup, sem botão "Aplicar" — o v-model aponta
// direto pra store e uma espera só para o filtro inteiro refaz a busca.
//
// Os selects daqui NÃO são os SelectUnidade/SelectContrato/SelectTalhao do
// pátio: aqueles leem o Dexie, que só é populado ao abrir /carga. Quem cai
// direto em /cargas teria selects vazios e sem erro nenhum na tela.
import { onMounted, onUnmounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import FilterDrawerShell from 'components/FilterDrawerShell.vue'
import FilterGroup from 'components/FilterGroup.vue'
import MgInput from '@components/MgInput.vue'
import MgSelect from '@components/MgSelect.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import { useCargaListagemStore } from 'src/stores/cargaListagem'
import { SENTIDOS, ETAPA_META } from 'src/utils/carga'

const store = useCargaListagemStore()
const { filtros, safras, culturas, unidades, contratos, plantios, carregandoPlantios } =
  storeToRefs(store)

const SENTIDO_OPCOES = SENTIDOS.map((s) => ({ value: s.value, label: s.label }))

const ETAPA_OPCOES = Object.entries(ETAPA_META).map(([value, m]) => ({ value, label: m.label }))

const SITUACAO_OPCOES = [
  { label: 'Ativos', value: 1 },
  { label: 'Cancelados', value: 2 },
  { label: 'Todos', value: 9 },
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

// Uma espera só para o filtro inteiro: o campo de texto emite a cada tecla, e
// sem isso seria uma requisição por letra.
let timer = null
watch(
  filtros,
  () => {
    clearTimeout(timer)
    timer = setTimeout(() => store.buscar(true), 500)
  },
  { deep: true },
)
onUnmounted(() => clearTimeout(timer))

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
  <FilterDrawerShell :active-count="store.contagemFiltros" @clear="store.limparFiltros()">
    <FilterGroup title="Período" first>
      <div class="row q-col-gutter-sm">
        <div class="col-6">
          <MgInputData v-model="filtros.data_inicio" type="date" label="De" :bottom-slots="false" />
        </div>
        <div class="col-6">
          <MgInputData v-model="filtros.data_fim" type="date" label="Até" :bottom-slots="false" />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Safra e cultura">
      <div class="column q-gutter-y-sm">
        <MgSelect
          v-model="filtros.codsafra"
          :options="safras"
          option-value="codsafra"
          option-label="safra"
          emit-value
          map-options
          outlined
          clearable
          :bottom-slots="false"
          label="Safra"
        />
        <MgSelect
          v-model="filtros.codcultura"
          :options="culturas"
          option-value="codcultura"
          option-label="cultura"
          emit-value
          map-options
          outlined
          clearable
          :bottom-slots="false"
          label="Cultura"
        />
      </div>
    </FilterGroup>

    <FilterGroup title="Tipo e etapa">
      <div class="column q-gutter-y-sm">
        <MgSelect
          v-model="filtros.sentido"
          :options="SENTIDO_OPCOES"
          emit-value
          map-options
          outlined
          clearable
          :bottom-slots="false"
          label="Tipo de romaneio"
        />
        <MgSelect
          v-model="filtros.etapa"
          :options="ETAPA_OPCOES"
          emit-value
          map-options
          outlined
          clearable
          :bottom-slots="false"
          label="Etapa"
        />
      </div>
    </FilterGroup>

    <FilterGroup title="Origem e destino">
      <div class="column q-gutter-y-sm">
        <MgSelect
          v-model="filtros.codunidadearmazenadora"
          :options="unidades"
          :option-label="rotuloUnidade"
          option-value="codunidadearmazenadora"
          emit-value
          map-options
          outlined
          clearable
          :bottom-slots="false"
          label="Unidade armazenadora"
        />
        <MgSelect
          v-model="filtros.codplantio"
          :options="plantios"
          :option-label="rotuloPlantio"
          option-value="codplantio"
          :disable="!filtros.codsafra"
          :loading="carregandoPlantios"
          :hint="filtros.codsafra ? undefined : 'Escolha a safra primeiro'"
          emit-value
          map-options
          outlined
          clearable
          label="Talhão"
        />
        <MgSelect
          v-model="filtros.codcontrato"
          :options="contratos"
          :option-label="rotuloContrato"
          option-value="codcontrato"
          emit-value
          map-options
          outlined
          clearable
          :bottom-slots="false"
          label="Contrato"
        />
        <MgSelectPessoa
          v-model="filtros.codpessoacontrato"
          label="Cliente / fornecedor"
          clearable
          :bottom-slots="false"
        />
      </div>
    </FilterGroup>

    <FilterGroup title="Caminhão">
      <div class="column q-gutter-y-sm">
        <MgInput v-model="filtros.placa" outlined clearable :bottom-slots="false" label="Placa" />
        <MgInput
          v-model="filtros.placacarreta"
          outlined
          clearable
          :bottom-slots="false"
          label="Carreta"
        />
        <MgInput
          v-model="filtros.motorista"
          outlined
          clearable
          :bottom-slots="false"
          label="Motorista"
        />
        <MgInputValor
          v-model="filtros.codcarga"
          :decimals="0"
          :grouping="false"
          align="left"
          clearable
          :bottom-slots="false"
          label="Nº do romaneio"
        />
      </div>
    </FilterGroup>

    <FilterGroup title="Situação">
      <q-btn-toggle
        v-model="filtros.inativo"
        spread
        no-caps
        flat
        toggle-color="primary"
        :options="SITUACAO_OPCOES"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
