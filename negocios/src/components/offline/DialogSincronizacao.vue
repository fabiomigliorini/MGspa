<script setup>
import { nextTick, onMounted, ref } from 'vue'
import { formataNumero, formataTimestamp } from '@components/formatters'
import { sincronizacaoStore } from 'stores/sincronizacao'

// Janela "Sincronizar...": abre com sSinc.importacao.dialog = true
// (botao do cabecalho do PDV e menu do quiosque).
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

// uma linha por etapa (TASK-197): produto e pessoa dizem se foi a base inteira ou so' o diff,
// e enquanto roda a linha diz o que esta' fazendo, para nao parecer travada
const textoEtapa = (etapa) => {
  if (etapa.modo == 'full') {
    const contagem = `${formataNumero(etapa.registros, 0)} / ${formataNumero(etapa.total, 0)} registros`
    return etapa.rodando ? `Baixando tudo: ${contagem}` : contagem
  }
  if (etapa.modo == 'diff') {
    if (!etapa.total) {
      return 'Nada mudou desde a última'
    }
    const contagem = `${formataNumero(etapa.alterados, 0)} alterados, ${formataNumero(etapa.excluidos, 0)} excluídos`
    return etapa.rodando ? `Baixando alterações: ${contagem}` : contagem
  }
  // configuracoes conta as quatro; prancheta e vales nao contam nada (o icone ja diz o estado)
  if (etapa.total) {
    return `${etapa.registros} / ${etapa.total}`
  }
  // pessoas e produtos antes de saber se vai a base inteira ou o diff; prancheta e vales
  return etapa.rodando ? 'Consultando o servidor...' : ''
}
</script>

<template>
  <!-- fechar por qualquer caminho (Cancelar, ESC, clique fora) para a sincronizacao: ela nao
  pode seguir rodando escondida -->
  <q-dialog
    v-model="sSinc.importacao.dialog"
    @show="aoMostrar()"
    @hide="sSinc.abortarSincronizacao()"
  >
    <q-card flat class="q-pa-md" style="width: 600px; max-width: 95vw">
      <q-card-section>
        <div class="text-h4 text-center">Sincronizar...</div>

        <div class="flex flex-center q-my-md">
          <q-circular-progress
            show-value
            :indeterminate="sSinc.importacao.progresso == 0 && sSinc.importacao.rodando"
            rounded
            size="160px"
            color="secondary"
            class="q-ma-md"
            center-color="green-1"
            :value="sSinc.importacao.progresso"
          >
            {{ sSinc.importacao.progresso }}%
          </q-circular-progress>
        </div>
        <div class="text-center text-grey">{{ sSinc.importacao.tempoTotal }} Segundos</div>
        <div class="text-center text-grey">
          <template v-if="sSinc.ultimaSincronizacao.completa">
            Última sincronização
            {{ formataTimestamp(sSinc.ultimaSincronizacao.completa, 4, true) }}
          </template>
          <template v-else>Nunca sincronizado</template>
        </div>

        <!-- as etapas rodam em paralelo: cada uma tem a sua linha, seu estado e seu toggle -->
        <q-list class="q-mt-md">
          <q-item v-for="(etapa, chave) in sSinc.importacao.etapas" :key="chave">
            <q-item-section avatar>
              <q-spinner v-if="etapa.rodando" color="primary" size="sm" />
              <q-icon v-else-if="etapa.erro" name="error" color="red-5" />
              <q-icon v-else-if="etapa.concluida" name="check_circle" color="green-5" />
              <q-icon v-else name="radio_button_unchecked" color="grey-5" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ etapa.label }}</q-item-label>
              <q-item-label caption>{{ textoEtapa(etapa) }}</q-item-label>
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
      </q-card-section>
      <q-card-actions align="center">
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
          label="Sincronização completa"
          color="primary"
          @click="sSinc.sincronizar(true)"
          :disable="sSinc.importacao.rodando || sSinc.importacao.incremental === null"
        />
        <q-btn
          ref="btnIncremental"
          flat
          label="Sincronização incremental"
          color="primary"
          @click="sSinc.sincronizar()"
          :disable="sSinc.importacao.rodando || !sSinc.importacao.incremental"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
