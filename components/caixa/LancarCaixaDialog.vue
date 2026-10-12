<script setup>
// Lançar no período (doc-4): o FAB dos lançamentos abre esta lista do que dá para fazer e cada
// opção, com o seu ícone e a sua cor, abre o seu dialog já no sentido escolhido. Sangria primeiro
// (a mais usada). No caixa do contas: Sangria, Reforço, Entrada e Saída de item, Vendas de
// Parceiro e Ajuste; no caixa do PDV só Sangria, Reforço e Vendas de Parceiro; no
// banco Enviar, Receber e Ajuste/taxa/tarifa/rendimento. O que o caixa não tem (item, maquineta)
// não aparece; a Saída de item com o caixa vazio aparece desabilitada, com o motivo. No teclado
// (setas e Enter), como o wizard de cobrança.
import { ref, computed } from 'vue'
import ListaOpcoes from '@components/cobranca/ListaOpcoes.vue'
import { periodoStore } from '@components/stores/periodoStore'

const store = periodoStore()
const cardRef = ref(null)
const listaRef = ref(null)

const opcoes = computed(() => {
  const caixa = !!store.portador?.ehCaixa
  const itens = (store.periodo?.itens ?? []).filter((i) => !i.inativo)
  // entrada e saída de item: portador em espécie com item ativo (no contas)
  const temItens = !store.pdv && caixa && itens.length > 0
  // borderô da maquineta de parceiro: portador em espécie com maquineta ativa
  const temMaquinetas = caixa && (store.periodo?.maquinetas ?? []).length > 0
  return [
    {
      label: caixa ? 'Sangria' : 'Enviar',
      icone: 'outbox',
      cor: 'red-5',
      abrir: () => store.abrirTransferir('E'),
    },
    {
      label: caixa ? 'Reforço' : 'Receber',
      icone: 'move_to_inbox',
      cor: 'green-6',
      abrir: () => store.abrirTransferir('R'),
    },
    {
      label: 'Entrada de item no caixa',
      icone: 'add_box',
      cor: 'teal-6',
      mostra: temItens,
      abrir: () => store.abrirItem(1),
    },
    {
      label: 'Saída de item do caixa',
      icone: 'indeterminate_check_box',
      cor: 'orange-7',
      mostra: temItens,
      desabilitado: !itens.some((i) => i.saida?.length),
      motivo: 'Nenhum item no caixa (saldo inicial ou entrada do período)',
      abrir: () => store.abrirItem(-1),
    },
    {
      label: 'Vendas de Parceiro',
      icone: 'receipt_long',
      cor: 'indigo-5',
      mostra: temMaquinetas,
      abrir: () => (store.dialogMaquineta = true),
    },
    {
      label: caixa ? 'Ajuste' : 'Ajuste, taxa, tarifa, rendimento',
      icone: 'tune',
      cor: 'blue-grey-5',
      mostra: !store.pdv,
      abrir: () => (store.dialogAvulso = true),
    },
  ]
    .filter((o) => o.mostra !== false)
    .map((o, i) => ({ ...o, valor: i }))
})

function escolher(opcao) {
  store.dialogLancar = false
  opcao.abrir()
}

// as teclas vão para a lista
function tecla(e) {
  if (!listaRef.value?.tecla(e)) return
  e.preventDefault()
  e.stopPropagation()
}
</script>

<template>
  <q-dialog v-model="store.dialogLancar" @show="cardRef?.$el.focus()">
    <q-card
      ref="cardRef"
      flat
      tabindex="0"
      class="no-outline"
      style="width: 400px; max-width: 95vw"
      @keydown="tecla"
    >
      <q-card-section class="text-grey-9 text-overline text-uppercase">Lançar</q-card-section>
      <q-separator inset />
      <q-card-section>
        <ListaOpcoes ref="listaRef" :opcoes="opcoes" @escolher="escolher" />
      </q-card-section>
      <q-separator inset />
      <q-card-actions align="right">
        <q-btn
          flat
          label="Cancelar"
          color="grey-8"
          tabindex="-1"
          @click="store.dialogLancar = false"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
