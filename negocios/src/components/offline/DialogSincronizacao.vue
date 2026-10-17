<script setup>
import { onMounted } from 'vue'
import { formataNumero, formataTimestamp } from '@components/formatters'
import { sincronizacaoStore } from 'stores/sincronizacao'

// Janela "Sincronizar...": abre com sSinc.importacao.dialog = true
// (botao do cabecalho do PDV e menu do quiosque).
const sSinc = sincronizacaoStore()

// ao abrir o sistema com a sincronizacao velha, a janela abre e ja sincroniza (TASK-197)
onMounted(() => sSinc.sincronizarAoAbrir())

// uma linha por etapa (TASK-197): produto e pessoa dizem se foi a base inteira ou so' o diff
const textoEtapa = (etapa) => {
  if (etapa.modo == 'full') {
    return `${formataNumero(etapa.registros, 0)} / ${formataNumero(etapa.total, 0)} registros`
  }
  if (etapa.modo == 'diff') {
    if (!etapa.total) {
      return 'Nada mudou desde a última'
    }
    return `${formataNumero(etapa.alterados, 0)} alterados, ${formataNumero(etapa.excluidos, 0)} excluídos`
  }
  // configuracoes conta as quatro; prancheta e vales nao contam nada (o icone ja diz o estado)
  if (etapa.total) {
    return `${etapa.registros} / ${etapa.total}`
  }
  return ''
}
</script>

<template>
  <!-- fechar por qualquer caminho (Cancelar, ESC, clique fora) para a sincronizacao: ela nao
  pode seguir rodando escondida -->
  <q-dialog v-model="sSinc.importacao.dialog" @hide="sSinc.abortarSincronizacao()">
    <q-card flat class="q-pa-md" style="width: 520px; max-width: 95vw">
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
        <q-btn
          flat
          label="Sincronizar"
          color="primary"
          @click="sSinc.sincronizar()"
          autofocus
          :disable="sSinc.importacao.rodando"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
