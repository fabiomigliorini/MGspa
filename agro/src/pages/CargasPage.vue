<script setup>
// Listagem do HISTÓRICO de romaneios (consulta). A operação continua no pátio
// (/carga/:uuid) — aqui nada é editado; a linha abre a ficha só-leitura.
//
// Estrutura espelhada da NotasPage (app notas): página inteira sem card nem
// max-width, q-item denso e um grid de colunas `col-md-*` em text-caption. NÃO
// reusar o CargaListItem aqui — aquele é item de drawer de 300px e, esticado
// numa página larga, vira um bloco solto sem colunas.
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { formataTimestamp } from '@components/formatters'
import { useCargaListagemStore } from 'src/stores/cargaListagem'
import {
  fmtNumero,
  iconeCarga,
  corIconeCarga,
  rotulosDoPapel,
  sentidoMeta,
  ETAPA_META,
  ETAPA_FINAL,
} from 'src/utils/carga'

const store = useCargaListagemStore()
const { cargas, totais, paginacao, carregadoUmaVez, sacasTotais, culturaUnica } = storeToRefs(store)

// Abreviado: "Transferência" por extenso empurra a coluna e quebra o grid.
const SENTIDO_CURTO = { ENTRADA: 'Receb.', SAIDA: 'Exped.', TRANSFERENCIA: 'Transf.' }

function rota(carga) {
  return { name: 'carga-detalhe', params: { codcarga: carga.codcarga } }
}

// pesosaca da própria safra da carga: uma listagem sem filtro mistura culturas,
// e usar o peso de uma delas erraria as sacas das outras.
// `Safra.cultura` em minúsculo: relação aninhada num model é serializada pelo
// Eloquent em snake_case (só o `Safra` do topo é PascalCase, posto pelo Resource).
function sacasDa(carga) {
  if (carga.liquido == null) return null
  return Number(carga.liquido) / (Number(carga.Safra?.cultura?.pesosaca) || 60)
}

function percurso(carga) {
  const origem = rotulosDoPapel(carga, 'ORIGEM')
  const destino = rotulosDoPapel(carga, 'DESTINO')
  if (origem && destino) return `${origem} → ${destino}`
  return origem || destino || 'Sem origem/destino'
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
  <q-page>
    <!-- Totais do RECORTE INTEIRO, não da página carregada. Mesmo grid das
         linhas, pra cada número cair em cima da sua coluna. -->
    <q-item v-if="carregadoUmaVez && totais.qtd" class="bg-grey-2">
      <q-item-section avatar style="min-width: 32px" />
      <q-item-section>
        <!-- As larguras repetem as da linha (2+2+3 = 7, depois 2, 1, 2) pra
             cada total cair exatamente em cima da sua coluna. -->
        <div class="row items-center">
          <div class="q-px-sm col-6 col-md-7 text-caption text-weight-medium">
            {{ fmtNumero(totais.qtd) }} {{ totais.qtd === 1 ? 'romaneio' : 'romaneios' }}
          </div>
          <div class="q-px-sm col-6 col-md-2 text-caption text-grey-7">
            Bruto {{ fmtNumero(totais.bruto) }} kg
          </div>
          <div class="q-px-sm col-6 col-md-1 text-caption text-orange-9 text-right">
            − {{ fmtNumero(totais.desconto) }}
          </div>
          <div class="q-px-sm col-6 col-md-2 text-caption text-right">
            <span class="text-weight-bold text-green-9">{{ fmtNumero(totais.liquido) }} kg</span>
            <!-- Só some quando o recorte tem mais de uma cultura: somar saca de
                 soja com saca de milho não significaria nada. -->
            <span v-if="sacasTotais !== null" class="text-grey-7">
              · {{ fmtNumero(sacasTotais, 1) }} sc
              <q-tooltip>
                Sacas de {{ culturaUnica?.cultura }} ({{ culturaUnica?.pesosaca }} kg)
              </q-tooltip>
            </span>
          </div>
        </div>
      </q-item-section>
    </q-item>
    <q-separator v-if="carregadoUmaVez && totais.qtd" />

    <!-- Loading inicial -->
    <div v-if="paginacao.loading && cargas.length === 0" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Empty State -->
    <q-card v-else-if="cargas.length === 0" flat bordered class="q-pa-xl text-center q-ma-sm">
      <q-icon name="local_shipping" size="4em" color="grey-5" />
      <div class="text-h6 text-grey-7 q-mt-md">Nenhum romaneio encontrado</div>
      <div class="text-caption text-grey-7 q-mt-sm">
        <template v-if="store.contagemFiltros">Tente ajustar os filtros</template>
        <template v-else>Os romaneios aparecem aqui assim que forem registrados no pátio</template>
      </div>
    </q-card>

    <!-- Lista de Romaneios -->
    <q-infinite-scroll v-else :offset="250" @load="onLoad">
      <q-list separator>
        <q-item v-for="carga in cargas" :key="carga.codcarga" hoverable clickable :to="rota(carga)">
          <q-item-section avatar style="min-width: 32px" class="items-center">
            <q-icon :name="iconeCarga(carga)" :color="corIconeCarga(carga)">
              <q-tooltip>{{ sentidoMeta(carga.sentido).label }}</q-tooltip>
            </q-icon>
            <q-icon v-if="carga.inativo" name="block" color="negative" size="xs">
              <q-tooltip>Romaneio cancelado</q-tooltip>
            </q-icon>
          </q-item-section>

          <q-item-section>
            <div class="row items-center">
              <!-- Romaneio -->
              <div class="q-px-sm col-6 col-md-2 text-weight-medium text-caption">
                #{{ carga.codcarga }}
                <span class="text-grey-7">{{ SENTIDO_CURTO[carga.sentido] }}</span>
              </div>

              <!-- Placa / carreta -->
              <div class="q-px-sm col-6 col-md-2 text-caption text-grey-8">
                {{ carga.placa || 'Sem placa' }}
                <span v-if="carga.placacarreta" class="text-grey-6">
                  / {{ carga.placacarreta }}
                </span>
              </div>

              <!-- Origem → Destino -->
              <div
                class="q-px-sm col-12 col-md-3 text-weight-bold text-caption text-primary ellipsis"
              >
                {{ percurso(carga) }}
              </div>

              <!-- Chegada -->
              <div class="q-px-sm col-6 col-md-2 text-caption text-grey-7 ellipsis">
                {{ formataTimestamp(carga.data, 2) }}
              </div>

              <!-- Desconto -->
              <div class="q-px-sm col-6 col-md-1 text-caption text-orange-9 text-right">
                <template v-if="carga.desconto">− {{ fmtNumero(carga.desconto) }}</template>
              </div>

              <!-- Líquido — col-6 no estreito pra dividir a linha com o
                   desconto; em col-12 uma carga sem pesar deixava uma linha
                   inteira vazia só com o travessão. -->
              <div class="q-px-sm col-6 col-md-2 text-caption text-right">
                <template v-if="carga.liquido != null">
                  <span class="text-weight-bold text-green-9">
                    {{ fmtNumero(carga.liquido) }} kg
                  </span>
                  <span class="text-grey-7"> · {{ fmtNumero(sacasDa(carga), 1) }} sc</span>
                </template>
                <span v-else class="text-grey-6">—</span>
              </div>

              <!-- Etapa pendente, motorista e safra -->
              <div class="q-px-sm col-12 text-caption text-grey-7 ellipsis">
                <span
                  v-if="carga.etapa !== ETAPA_FINAL"
                  :class="`text-${ETAPA_META[carga.etapa]?.color}`"
                >
                  {{ ETAPA_META[carga.etapa]?.acao }} ·
                </span>
                {{ carga.motorista || 'Sem motorista' }}
                <template v-if="carga.Safra?.safra"> · {{ carga.Safra.safra }}</template>
              </div>
            </div>
          </q-item-section>
        </q-item>
      </q-list>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>

    <div
      v-if="carregadoUmaVez && cargas.length && !paginacao.hasMore"
      class="text-center text-caption text-grey-6 q-pa-md"
    >
      {{ cargas.length }} de {{ fmtNumero(paginacao.total) }} romaneios
    </div>

    <!-- FABs -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab-mini icon="print" color="grey-7" @click="store.imprimirRelatorio()">
        <q-tooltip>Imprimir Relatório</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
