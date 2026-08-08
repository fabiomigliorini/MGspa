<script setup>
import { computed, ref } from 'vue'
import { useDialogPluginComponent } from 'quasar'
import MgXmlViewer from './MgXmlViewer.vue'
import { baixarArquivo } from './baixarArquivo'

const props = defineProps({
  // Conteudo ja baixado: ou um XML puro, ou o log de conversa com a SEFAZ.
  conteudo: { type: String, default: '' },
  titulo: { type: String, default: 'XML' },
  nomeArquivo: { type: String, default: 'documento.xml' },
})

defineEmits([...useDialogPluginComponent.emits])

const { dialogRef, onDialogHide } = useDialogPluginComponent()

const MARCA_REQUISICAO = '<!-- REQUEST -->'
const MARCA_RESPOSTA = '<!-- RESPONSE -->'

// O cabecalho HTTP nunca tem linha comecando com '<'; o corpo sempre comeca com uma.
// Corta por linha (e nao por regex) para nao arriscar um ^\s*< casar antes da hora.
function partirCabecalhoCorpo(parte) {
  const linhas = parte.split('\n')
  const i = linhas.findIndex((l) => l.trimStart().startsWith('<'))
  if (i < 0) return { cabecalho: parte.trim(), corpo: '' }
  return {
    cabecalho: linhas.slice(0, i).join('\n').trim(),
    corpo: linhas.slice(i).join('\n').trim(),
  }
}

// O log gravado pelo SefazLogService tem 4 partes: cabecalho e corpo da requisicao,
// cabecalho e corpo da resposta. Qualquer uma pode vir vazia. Devolve null quando o
// conteudo e um XML puro (os demais call sites) — ai o dialog mostra um viewer so.
function partirLogSefaz(texto) {
  const t = texto || ''
  const iRequisicao = t.indexOf(MARCA_REQUISICAO)
  const iResposta = iRequisicao < 0 ? -1 : t.indexOf(MARCA_RESPOSTA, iRequisicao)
  if (iRequisicao < 0 || iResposta < 0) return null
  return {
    requisicao: partirCabecalhoCorpo(t.slice(iRequisicao + MARCA_REQUISICAO.length, iResposta)),
    resposta: partirCabecalhoCorpo(t.slice(iResposta + MARCA_RESPOSTA.length)),
  }
}

const log = computed(() => partirLogSefaz(props.conteudo))

const paineis = computed(() => {
  if (!log.value) return []
  const base = props.nomeArquivo.replace(/\.[^.]+$/, '')
  return [
    {
      nome: 'requisicao',
      rotulo: 'Requisição',
      ...log.value.requisicao,
      arquivo: `${base}-requisicao.xml`,
    },
    {
      nome: 'resposta',
      rotulo: 'Resposta',
      ...log.value.resposta,
      arquivo: `${base}-resposta.xml`,
    },
  ]
})

// Abre na resposta: e o que interessa em quase toda consulta.
const aba = ref('resposta')

const baixarLog = () => baixarArquivo(props.conteudo, props.nomeArquivo)
</script>

<template>
  <q-dialog ref="dialogRef" :maximized="$q.screen.lt.md" @hide="onDialogHide">
    <q-card flat class="column" :style="{ width: '1100px', maxWidth: '95vw', height: '90vh' }">
      <q-card-section class="text-grey-9 text-overline row items-center no-wrap q-py-sm">
        <div class="col ellipsis">{{ titulo }}</div>
        <q-btn v-if="log" flat round size="sm" color="grey-7" icon="save_alt" @click="baixarLog">
          <q-tooltip>Baixar log completo</q-tooltip>
        </q-btn>
        <q-btn v-close-popup flat round size="sm" color="grey-7" icon="close" />
      </q-card-section>

      <template v-if="log">
        <q-tabs
          v-model="aba"
          align="left"
          no-caps
          class="text-grey-7"
          active-color="primary"
          indicator-color="primary"
        >
          <q-tab v-for="painel in paineis" :key="painel.nome" :name="painel.nome">
            {{ painel.rotulo }}
          </q-tab>
        </q-tabs>
        <q-separator />
        <!-- keep-alive: sem ele, trocar de aba perde o estado de abre/fecha da arvore -->
        <q-tab-panels v-model="aba" keep-alive class="col">
          <q-tab-panel
            v-for="painel in paineis"
            :key="painel.nome"
            :name="painel.nome"
            class="column no-wrap"
          >
            <q-expansion-item
              v-if="painel.cabecalho"
              icon="http"
              label="Cabeçalho HTTP"
              class="q-mb-sm bg-grey-1 rounded-borders"
            >
              <div
                class="q-pa-sm text-caption"
                style="font-family: monospace; white-space: pre-wrap; overflow-wrap: anywhere"
                v-text="painel.cabecalho"
              />
            </q-expansion-item>
            <MgXmlViewer class="col" :xml="painel.corpo" :nome-arquivo="painel.arquivo" />
          </q-tab-panel>
        </q-tab-panels>
      </template>

      <q-card-section v-else class="col column no-wrap q-pt-none">
        <MgXmlViewer class="col" :xml="conteudo" :nome-arquivo="nomeArquivo" />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
