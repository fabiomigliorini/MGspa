<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import api from '../services/api'
import { abrirXml } from '@components/abrirXml'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import {
  formataMesAno,
  formataNumero,
  formataProtocolo,
  formataTimestamp,
} from '@components/formatters'
import { useInutilizacaoStore } from '../stores/inutilizacaoStore'
import InutilizacaoDialog from '../components/dialogs/InutilizacaoDialog.vue'
import LacunasDialog from '../components/dialogs/LacunasDialog.vue'

const inutilizacaoStore = useInutilizacaoStore()
const { codfilial, ano, filiais, loading } = storeToRefs(inutilizacaoStore)

const showDialogInutilizar = ref(false)
const showDialogLacunas = ref(false)

const filialAtual = computed(() => filiais.value.find((f) => f.codfilial === codfilial.value))

const rotuloFaixa = (f) =>
  f.numeroinicial === f.numerofinal
    ? `Número ${f.numeroinicial}`
    : `Números ${f.numeroinicial} a ${f.numerofinal} (${f.quantidade})`

const abrirXmlFaixa = (faixa) =>
  abrirXml(
    api,
    `v1/inutilizacao/${faixa.codinutilizacao}/xml`,
    {},
    {
      titulo: `Inutilização ${rotuloFaixa(faixa)}`,
      nomeArquivo: `${faixa.modelo}-${faixa.serie}-${faixa.numeroinicial}-${faixa.numerofinal}-inut.xml`,
    },
  )

// A pagina inicializa a navegacao — o drawer so mostra a lista de anos, porque no mobile ele
// nao monta enquanto o usuario nao o abre.
onMounted(() => inutilizacaoStore.inicializar())
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <div class="row items-center q-mb-md">
        <div class="col">
          <div class="text-h6">Inutilizações</div>
          <div class="text-caption text-grey-7">
            <template v-if="filialAtual">{{ filialAtual.filial }} — {{ ano }}</template>
            <template v-else>Nenhuma filial com histórico</template>
          </div>
        </div>
        <div class="col-auto">
          <q-btn
            flat
            round
            size="sm"
            color="grey-7"
            icon="playlist_remove"
            @click="showDialogLacunas = true"
          >
            <q-tooltip>Detectar Lacunas</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="block"
            @click="showDialogInutilizar = true"
          >
            <q-tooltip>Inutilizar Faixa</q-tooltip>
          </q-btn>
        </div>
      </div>

      <!-- Abas de filial: só as que têm histórico (os anos ficam no drawer) -->
      <q-tabs
        v-if="filiais.length"
        :model-value="codfilial"
        class="text-grey-7 q-mb-md"
        active-color="primary"
        indicator-color="primary"
        align="left"
        inline-label
        @update:model-value="inutilizacaoStore.selecionarFilial"
      >
        <q-tab v-for="f in filiais" :key="f.codfilial" :name="f.codfilial" :label="f.filial">
          <q-badge color="grey-5" class="q-ml-sm">{{ formataNumero(f.faixas, 0) }}</q-badge>
        </q-tab>
      </q-tabs>

      <!-- Carregando -->
      <div v-if="loading" class="text-center q-pa-lg">
        <q-spinner color="primary" size="2em" />
        <div class="text-caption q-mt-sm">Carregando inutilizações...</div>
      </div>

      <!-- Vazio -->
      <MgEmptyState v-else-if="!inutilizacaoStore.faixasPorMes.length" icon="block">
        Nenhuma inutilização registrada nesta filial.
      </MgEmptyState>

      <!-- Um card por mês -->
      <template v-else>
        <q-card
          v-for="grupo in inutilizacaoStore.faixasPorMes"
          :key="grupo.mes"
          bordered
          flat
          class="q-mb-md"
        >
          <q-card-section class="row items-center q-pb-none">
            <div class="col text-subtitle1 text-weight-medium text-capitalize">
              {{ formataMesAno(grupo.mes) }}
            </div>
            <div class="col-auto text-caption text-grey-7">
              {{ formataNumero(grupo.totalFaixas, 0) }}
              {{ grupo.totalFaixas === 1 ? 'faixa' : 'faixas' }} —
              {{ formataNumero(grupo.totalNumeros, 0) }}
              {{ grupo.totalNumeros === 1 ? 'número' : 'números' }}
            </div>
          </q-card-section>

          <q-list separator>
            <q-item v-for="faixa in grupo.faixas" :key="faixa.codinutilizacao">
              <q-item-section>
                <q-item-label>
                  {{ rotuloFaixa(faixa) }}
                  <q-badge color="blue-grey-5" class="q-ml-sm">
                    Mod {{ faixa.modelo }} / Sér {{ faixa.serie }}
                  </q-badge>
                  <q-badge v-if="faixa.ambiente === 2" color="orange-6" class="q-ml-sm">
                    Homologação
                  </q-badge>
                  <q-badge v-if="!faixa.homologada" color="red-5" class="q-ml-sm">
                    Sem protocolo
                  </q-badge>
                </q-item-label>
                <q-item-label caption>{{ faixa.justificativa }}</q-item-label>
                <q-item-label caption>
                  Protocolo {{ formataProtocolo(faixa.protocolo) }}
                  <template v-if="faixa.protocolodata">
                    — {{ formataTimestamp(faixa.protocolodata, 4, true) }}
                  </template>
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                <div class="row items-center no-wrap">
                  <MgInfoCriacao :registro="faixa" />
                  <q-btn
                    v-if="faixa.temxml"
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="code"
                    @click="abrirXmlFaixa(faixa)"
                  >
                    <q-tooltip>Ver XML</q-tooltip>
                  </q-btn>
                </div>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>
    </div>

    <InutilizacaoDialog v-model="showDialogInutilizar" />
    <LacunasDialog v-model="showDialogLacunas" />
  </q-page>
</template>
