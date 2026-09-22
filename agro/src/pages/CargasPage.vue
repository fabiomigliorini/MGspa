<script setup>
// Listagem do HISTÓRICO de romaneios (consulta). A operação continua no pátio
// (/carga/:uuid) — aqui nada é editado; a linha abre a ficha só-leitura.
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCargaListagemStore } from 'src/stores/cargaListagem'
import { fmtNumero } from 'src/utils/carga'
import CargaListItem from 'components/carga/CargaListItem.vue'

const store = useCargaListagemStore()
const { cargas, totais, paginacao, carregadoUmaVez, sacasTotais, culturaUnica } =
  storeToRefs(store)

function rota(carga) {
  return { name: 'carga-detalhe', params: { codcarga: carga.codcarga } }
}

// pesosaca da própria safra da carga: uma listagem sem filtro mistura culturas,
// e usar o peso de uma delas erraria as sacas das outras.
// `Safra.cultura` em minúsculo: relação aninhada num model é serializada pelo
// Eloquent em snake_case (só o `Safra` do topo é PascalCase, posto pelo Resource).
function pesosacaDa(carga) {
  return Number(carga.Safra?.cultura?.pesosaca) || 60
}

async function onLoad(index, done) {
  await store.buscar()
  done(!paginacao.value.hasMore)
}

onMounted(() => {
  if (!carregadoUmaVez.value) store.buscar(true)
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Totais do RECORTE INTEIRO, não da página carregada. -->
      <q-card v-if="carregadoUmaVez && totais.qtd" flat bordered class="q-mb-md bg-green-1">
        <q-card-section class="q-py-sm row items-center q-col-gutter-md">
          <div class="col-auto">
            <div class="text-caption text-grey-7">Romaneios</div>
            <div class="text-h6 text-green-9">{{ fmtNumero(totais.qtd) }}</div>
          </div>
          <q-separator vertical />
          <div class="col-auto">
            <div class="text-caption text-grey-7">Bruto</div>
            <div class="text-subtitle1">{{ fmtNumero(totais.bruto) }} kg</div>
          </div>
          <div class="col-auto">
            <div class="text-caption text-grey-7">Desconto</div>
            <div class="text-subtitle1 text-orange-9">{{ fmtNumero(totais.desconto) }} kg</div>
          </div>
          <q-separator vertical />
          <div class="col-auto">
            <div class="text-caption text-grey-7">Líquido</div>
            <div class="text-h6 text-green-9">{{ fmtNumero(totais.liquido) }} kg</div>
          </div>
          <div v-if="sacasTotais !== null" class="col-auto">
            <div class="text-caption text-grey-7">Sacas de {{ culturaUnica?.cultura }}</div>
            <div class="text-subtitle1">{{ fmtNumero(sacasTotais, 1) }} sc</div>
          </div>
        </q-card-section>
      </q-card>

      <!-- 1) primeira carga -->
      <div v-if="paginacao.loading && !cargas.length" class="text-center q-pa-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <!-- 2) nada encontrado -->
      <q-card v-else-if="!cargas.length" flat bordered>
        <q-card-section class="text-center q-pa-xl text-grey-6">
          <q-icon name="local_shipping" size="4em" color="grey-4" />
          <div class="text-subtitle1 q-mt-md">Nenhum romaneio encontrado</div>
          <div class="text-caption">
            {{
              store.contagemFiltros
                ? 'Tente ampliar o período ou limpar algum filtro.'
                : 'Os romaneios aparecem aqui assim que forem registrados no pátio.'
            }}
          </div>
        </q-card-section>
      </q-card>

      <!-- 3) lista -->
      <q-card v-else flat bordered>
        <q-infinite-scroll :offset="250" @load="onLoad">
          <q-list separator>
            <CargaListItem
              v-for="c in cargas"
              :key="c.codcarga"
              :carga="c"
              :pesosaca="pesosacaDa(c)"
              :to="rota(c)"
              :sync="false"
              ambos-lados
            />
          </q-list>
          <template #loading>
            <div class="row justify-center q-my-md">
              <q-spinner-dots color="primary" size="32px" />
            </div>
          </template>
        </q-infinite-scroll>

        <q-card-section v-if="!paginacao.hasMore" class="text-center text-caption text-grey-6">
          {{ cargas.length }} de {{ fmtNumero(paginacao.total) }} romaneios
        </q-card-section>
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="print" color="primary" @click="store.imprimirRelatorio()">
        <q-tooltip>Imprimir relatório</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
