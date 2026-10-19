<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { formataNumero, formataTimestamp } from '@components/formatters'
import { sincronizacaoStore } from 'stores/sincronizacao'

// Janela "Sincronizar...": abre com sSinc.abrirJanela(), que recusa com outra janela sincronizando
// (botao do cabecalho do PDV, menu do quiosque e a sincronizacao ao abrir o sistema).
const sSinc = sincronizacaoStore()

// ao abrir o sistema com a sincronizacao velha, a janela abre e ja sincroniza (TASK-197)
onMounted(() => sSinc.sincronizarAoAbrir())

// a cada vez que a janela abre ve se a incremental esta' disponivel e foca nela; se nao, so' a
// completa fica habilitada, com o foco. Rodando (a que abre sozinha), os dois estao desabilitados
const btnIncremental = ref(null)
const btnCompleta = ref(null)
const aoMostrar = async () => {
  if (!sSinc.importacao.rodando) {
    await sSinc.verificarIncremental()
  }
  await nextTick()
  const botao = sSinc.importacao.incremental ? btnIncremental : btnCompleta
  botao.value?.$el.focus()
}

// uma linha por etapa (TASK-197), todas no mesmo formato: nome, texto numa linha so' e barra
// sempre visivel, para a janela nao mudar de tamanho enquanto roda
const textoEtapa = (etapa) => {
  // rodando sem nada contado ainda; depois a contagem toma o lugar. Sem contagem (prancheta,
  // vales, antes da versao chegar) so' executa
  if (etapa.rodando && !etapa.registros) {
    return etapa.total ? 'Baixando...' : 'Executando...'
  }
  if (etapa.modo == 'diff') {
    if (!etapa.total) {
      return 'Nada mudou desde a última'
    }
    return `${formataNumero(etapa.alterados, 0)} alterados, ${formataNumero(etapa.excluidos, 0)} excluídos`
  }
  // completa: registros; configuracoes: as quatro
  if (etapa.total) {
    return `${formataNumero(etapa.registros, 0)} / ${formataNumero(etapa.total, 0)}`
  }
  // prancheta e vales nao contam registros
  if (etapa.concluida) {
    return 'Completo'
  }
  // espaco em branco: a linha do texto existe mesmo vazia
  return '\u00a0'
}

// a localizacao e' obrigatoria e vem antes das etapas: linha propria, no mesmo formato
const loc = computed(() => sSinc.importacao.localizacao)
const textoLocalizacao = computed(() => {
  if (loc.value.buscando) {
    return 'Buscando a localização do dispositivo...'
  }
  if (loc.value.erro) {
    return 'Sem localização'
  }
  if (loc.value.precisao !== null) {
    return `± ${formataNumero(loc.value.precisao, 0)} m`
  }
  return ' '
})

// sem contagem (prancheta, vales, antes da versao chegar) a barra fica indeterminada enquanto roda
const progressoEtapa = (etapa) => {
  if (etapa.total) {
    return etapa.registros / etapa.total
  }
  return etapa.concluida ? 1 : 0
}
</script>

<template>
  <!-- persistent: ESC e clique fora nao fecham, para ninguem perder a completa sem querer; so' o
  Cancelar fecha, e fechar para a sincronizacao: ela nao pode seguir rodando escondida -->
  <q-dialog
    v-model="sSinc.importacao.dialog"
    persistent
    @show="aoMostrar()"
    @hide="sSinc.abortarSincronizacao()"
  >
    <q-card flat style="width: 400px; max-width: 95vw">
      <q-card-section class="q-pb-none">
        <div class="text-h6">Sincronizar</div>
        <div class="text-caption text-grey-7">
          <template v-if="sSinc.ultimaSincronizacao.completa">
            Última sincronização
            {{ formataTimestamp(sSinc.ultimaSincronizacao.completa, 4, true) }}
          </template>
          <template v-else>Nunca sincronizado</template>
          <template v-if="sSinc.importacao.rodando">
            · {{ sSinc.importacao.tempoTotal }} s</template
          >
        </div>
      </q-card-section>

      <!-- as etapas rodam em paralelo: cada uma tem a sua linha, seu estado, sua barra e seu toggle -->
      <q-list>
        <!-- obrigatoria: o toggle fica sempre ligado -->
        <q-item>
          <q-item-section avatar>
            <q-spinner v-if="loc.buscando" color="primary" size="24px" />
            <q-icon v-else-if="loc.erro" name="error" color="red-5" />
            <q-icon v-else-if="loc.precisao !== null" name="check_circle" color="green-5" />
            <q-icon v-else name="radio_button_unchecked" color="grey-5" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Localização</q-item-label>
            <q-item-label caption lines="1">{{ textoLocalizacao }}</q-item-label>
            <q-linear-progress
              :value="loc.precisao !== null ? 1 : 0"
              :indeterminate="loc.buscando"
              :color="loc.erro ? 'red-5' : 'primary'"
              rounded
              class="q-mt-xs"
            />
          </q-item-section>
          <q-item-section side>
            <q-toggle :model-value="true" disable tabindex="-1" />
          </q-item-section>
        </q-item>
        <q-item v-for="(etapa, chave) in sSinc.importacao.etapas" :key="chave">
          <q-item-section avatar>
            <q-spinner v-if="etapa.rodando" color="primary" size="24px" />
            <q-icon v-else-if="etapa.erro" name="error" color="red-5" />
            <q-icon v-else-if="etapa.concluida" name="check_circle" color="green-5" />
            <q-icon v-else name="radio_button_unchecked" color="grey-5" />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ etapa.label }}</q-item-label>
            <q-item-label caption lines="1">{{ textoEtapa(etapa) }}</q-item-label>
            <q-linear-progress
              :value="progressoEtapa(etapa)"
              :indeterminate="etapa.rodando && !etapa.total"
              :color="etapa.erro ? 'red-5' : 'primary'"
              rounded
              class="q-mt-xs"
            />
          </q-item-section>
          <q-item-section side>
            <q-toggle
              v-model="sSinc.sincronizacao[chave]"
              :disable="sSinc.importacao.rodando"
              tabindex="-1"
            />
          </q-item-section>
        </q-item>
      </q-list>

      <q-card-actions align="right">
        <q-btn
          flat
          label="Cancelar"
          color="grey-8"
          @click="sSinc.abortarSincronizacao()"
          tabindex="-1"
        />
        <!-- pessoas e produtos pelo arquivo inteiro, mesmo se a incremental estiver disponivel -->
        <q-btn
          ref="btnCompleta"
          flat
          label="Completa"
          color="primary"
          @click="sSinc.sincronizar(true)"
          :disable="sSinc.importacao.rodando || sSinc.importacao.incremental === null"
        />
        <q-btn
          ref="btnIncremental"
          flat
          label="Incremental"
          color="primary"
          @click="sSinc.sincronizar()"
          :disable="sSinc.importacao.rodando || !sSinc.importacao.incremental"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
