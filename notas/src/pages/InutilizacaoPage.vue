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
import { useInutilizacaoStore, dataEfetiva } from '../stores/inutilizacaoStore'
import InutilizacaoDialog from '../components/dialogs/InutilizacaoDialog.vue'
import LacunasDialog from '../components/dialogs/LacunasDialog.vue'

const inutilizacaoStore = useInutilizacaoStore()
const { codfilial, ano, filiais, loading } = storeToRefs(inutilizacaoStore)

const showDialogInutilizar = ref(false)
const showDialogLacunas = ref(false)

const filialAtual = computed(() => filiais.value.find((f) => f.codfilial === codfilial.value))

// Na tabela o cabecalho ja diz "Número", entao a celula mostra so o valor.
const rotuloFaixa = (f) =>
  f.numeroinicial === f.numerofinal
    ? formataNumero(f.numeroinicial, 0)
    : `${formataNumero(f.numeroinicial, 0)} a ${formataNumero(f.numerofinal, 0)} (${f.quantidade})`

const rotuloModelo = (modelo) => (modelo === 65 ? 'Modelo 65 — NFC-e' : `Modelo ${modelo} — NF-e`)

const abrirXmlFaixa = (faixa) =>
  abrirXml(
    api,
    `v1/inutilizacao/${faixa.codinutilizacao}/xml`,
    {},
    {
      titulo: `Inutilização — modelo ${faixa.modelo}, série ${faixa.serie}, nº ${rotuloFaixa(faixa)}`,
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

          <!-- Uma tabela por modelo: NF-e e NFC-e tem numeracoes independentes -->
          <q-card-section v-for="mod in grupo.modelos" :key="mod.modelo" class="q-pt-sm">
            <div class="text-subtitle2 text-weight-medium text-capitalize q-mb-sm">
              {{ rotuloModelo(mod.modelo) }}
            </div>

            <q-markup-table flat bordered wrap-cells>
              <thead>
                <tr>
                  <th class="text-left text-grey-7">Série</th>
                  <th class="text-left text-grey-7">Número</th>
                  <th class="text-left text-grey-7">Data</th>
                  <th class="text-left text-grey-7">Protocolo</th>
                  <th class="text-left text-grey-7">Justificativa</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="faixa in mod.faixas" :key="faixa.codinutilizacao">
                  <td>{{ faixa.serie }}</td>
                  <td>{{ rotuloFaixa(faixa) }}</td>
                  <!-- Data, protocolo e justificativa sao contexto, nao a informacao que se
                       procura na linha: ficam no mesmo cinza discreto do titulo do modelo. -->
                  <td class="text-no-wrap text-grey-7">
                    {{ formataTimestamp(dataEfetiva(faixa), 4, true) }}
                  </td>
                  <!--
                    Sem protocolo, a faixa foi enviada a SEFAZ mas a resposta nao voltou (ou
                    foi recusada). Ela pode estar inutilizada la e nao aqui, entao precisa
                    saltar aos olhos: e a unica linha da tela que pede acao.
                  -->
                  <td v-if="!faixa.homologada" class="text-no-wrap">
                    <q-badge color="negative" class="text-weight-medium">
                      <q-icon name="warning" size="xs" class="q-mr-xs" />
                      Sem protocolo
                    </q-badge>
                    <q-tooltip>
                      {{
                        faixa.xmotivo ||
                        'A SEFAZ não confirmou esta inutilização. Consulte a situação da faixa antes de tentar de novo.'
                      }}
                    </q-tooltip>
                  </td>
                  <td v-else class="text-no-wrap text-grey-7">
                    {{ formataProtocolo(faixa.protocolo) }}
                  </td>
                  <td class="text-grey-7">{{ faixa.justificativa }}</td>
                  <td class="text-right text-no-wrap">
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
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
          </q-card-section>
        </q-card>
      </template>
    </div>

    <InutilizacaoDialog v-model="showDialogInutilizar" />
    <LacunasDialog v-model="showDialogLacunas" />
  </q-page>
</template>
