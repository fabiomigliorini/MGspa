<script setup>
import { ref, watch, nextTick, onUnmounted } from 'vue'
import { produtoStore } from 'stores/produto'
import MgInput from '@components/MgInput.vue'
import MgGradeProduto from '@components/MgGradeProduto.vue'
import MgSelect from '@components/MgSelect.vue'

// Pesquisa de produto em grade de cards, na base offline (F1 do PDV e do quiosque).
// Emite `select` com o código de barras escolhido; o que fazer com ele é de quem usa.
//
// A grade, o agrupamento por produto e a navegação por teclado são do
// @components/MgGradeProduto, o mesmo da pesquisa das telas online: o foco fica
// no campo de pesquisa o tempo todo; Enter pesquisa, as setas andam pela grade,
// Enter avança e Esc volta uma etapa.
//
// Fecha sozinho depois de TEMPO_INATIVIDADE sem uso; qualquer toque, tecla ou
// rolagem reinicia a contagem.
const emit = defineEmits(['select'])

const TEMPO_INATIVIDADE = 60000 // 60s sem uso -> fecha a pesquisa
const EVENTOS_USO = ['pointerdown', 'keydown', 'wheel', 'touchstart', 'scroll']

const sProduto = produtoStore()

const grade = ref(null)

// O teclado só funciona com o foco no campo (o keydown sobe dele): clicar
// na grade ou no fundo devolve o foco para lá. O q-select é a exceção, porque
// tem navegação própria e abre o menu fora do dialog.
const refPesquisa = ref(null)
const manterFoco = (evento) => {
  if (!sProduto.dialogPesquisa) return
  if (evento.relatedTarget?.closest?.('.q-select, .q-menu')) return
  nextTick(() => refPesquisa.value?.focus())
}

const fechar = () => {
  sProduto.dialogPesquisa = false
}

const escolher = (barra) => {
  emit('select', barra)
  fechar()
}

// Um handler só, no card do diálogo: o keydown sobe do campo de pesquisa.
const aoTeclar = (evento) => {
  // o q-select tem navegação própria nas opções; não roubar as teclas dele
  if (evento.target?.closest?.('.q-select')) return
  if (grade.value?.teclar(evento)) return
  if (evento.key === 'Enter') {
    evento.preventDefault()
    sProduto.pesquisar()
  }
}

// Texto editado desfaz o destaque: o Enter tem que voltar a significar
// "pesquisar" e não "escolher".
watch(
  () => sProduto.textoPesquisa,
  () => grade.value?.soltar(),
)

// ---- fecha sozinho depois de um tempo sem uso ----
let timerInatividade = null

const reiniciarInatividade = () => {
  clearTimeout(timerInatividade)
  timerInatividade = setTimeout(fechar, TEMPO_INATIVIDADE)
}

// captura no document: pega também o que acontece dentro do dialog e a
// rolagem, que não borbulha
const ligarInatividade = () => {
  EVENTOS_USO.forEach((ev) =>
    document.addEventListener(ev, reiniciarInatividade, { capture: true, passive: true }),
  )
  reiniciarInatividade()
}

const desligarInatividade = () => {
  EVENTOS_USO.forEach((ev) =>
    document.removeEventListener(ev, reiniciarInatividade, { capture: true }),
  )
  clearTimeout(timerInatividade)
}

// a grade nasce de novo a cada abertura: abre sempre com o teclado no campo,
// sem card em destaque, na etapa dos produtos
watch(
  () => sProduto.dialogPesquisa,
  (aberto) => {
    if (aberto) {
      ligarInatividade()
    } else {
      desligarInatividade()
    }
  },
  { immediate: true },
)

onUnmounted(desligarInatividade)
</script>

<template>
  <q-dialog v-model="sProduto.dialogPesquisa" maximized>
    <q-card class="column full-height no-wrap" @keydown="aoTeclar">
      <q-card-section class="bg-primary text-white col-auto">
        <div class="row q-col-gutter-sm">
          <MgInput
            ref="refPesquisa"
            outlined
            autofocus
            v-model="sProduto.textoPesquisa"
            label="Pesquisa"
            bg-color="white"
            class="col"
            @blur="manterFoco"
          >
            <template v-slot:append>
              <q-btn round flat icon="close" tabindex="-1" @click="sProduto.textoPesquisa = ''">
                <q-tooltip class="bg-accent">Limpar</q-tooltip>
              </q-btn>
              <q-btn round flat icon="search" tabindex="-1" @click="sProduto.pesquisar()">
                <q-tooltip class="bg-accent">Pesquisar</q-tooltip>
              </q-btn>
              <q-btn round flat icon="logout" tabindex="-1" @click="fechar">
                <q-tooltip class="bg-accent">Fechar</q-tooltip>
              </q-btn>
            </template>
          </MgInput>
          <MgSelect
            v-model="sProduto.sortPesquisa"
            :options="['Alfabética', 'Preço', 'Código', 'Barras']"
            label="Ordem"
            bg-color="white"
            style="width: 130px"
            @update:model-value="sProduto.pesquisar()"
          />
        </div>
        <div class="text-caption q-mt-xs">
          Enter pesquisa · setas andam pelos produtos · Enter abre o que estiver em destaque · Esc
          volta
        </div>
      </q-card-section>

      <q-card-section class="q-pa-none q-ma-none col scroll">
        <MgGradeProduto
          ref="grade"
          :barras="sProduto.resultadoPesquisa"
          :imagem="(barra) => sProduto.urlImagem(barra.codimagem)"
          @select="escolher"
        />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
