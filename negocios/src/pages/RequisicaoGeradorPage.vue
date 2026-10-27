<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ListaOpcoes from '@components/cobranca/ListaOpcoes.vue'
import GeradorRascunho from 'components/requisicao/GeradorRascunho.vue'
import { requisicaoGeradorStore } from 'stores/requisicaoGerador'
import { negocioStore } from 'stores/negocio'

// Gerador de requisição (TASK-258), primeira tela: a loja que vai ser reposta. Começa na do
// rascunho, se ele tem quantidade; senão, na do PDV. Com rascunho, a troca é recusada.
// Quem não é Gerente só repõe a loja do PDV: a tela passa direto para as marcas.
const router = useRouter()
const sGerador = requisicaoGeradorStore()
const sNegocio = negocioStore()

const cardRef = ref(null)
const listaRef = ref(null)

const inicial = computed(() =>
  sGerador.totalItens ? sGerador.codestoquelocal : sNegocio.padrao.codestoquelocal,
)

const opcoes = computed(() =>
  sGerador.lojas
    .filter(
      (l) => sNegocio.podeEscolherLoja || l.codestoquelocal == sNegocio.padrao.codestoquelocal,
    )
    .map((l, i) => ({
      valor: l.codestoquelocal,
      label: l.estoquelocal,
      caption: l.sigla,
      icone: 'warehouse',
      cor: 'blue-grey-5',
      tecla: i < 9 ? i + 1 : null,
    })),
)

function escolher(opcao) {
  if (sGerador.usarLoja(opcao.valor)) {
    router.push(`/requisicao/gerador/${opcao.valor}/marcas`)
  }
}

// o card repassa as teclas (setas, número, Enter) para a lista
function tecla(e) {
  if (!listaRef.value?.tecla(e)) return
  e.preventDefault()
  e.stopPropagation()
}

onMounted(async () => {
  await sGerador.carregarLojas()
  // sem escolha: segue com a loja do PDV (replace, para o voltar não cair aqui de novo);
  // com rascunho de outra loja, fica nesta tela para gerar ou limpar
  const loja = sNegocio.padrao.codestoquelocal
  if (!sNegocio.podeEscolherLoja && loja && sGerador.usarLoja(loja)) {
    router.replace(`/requisicao/gerador/${loja}/marcas`)
    return
  }
  nextTick(() => cardRef.value?.$el?.focus())
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card ref="cardRef" flat bordered tabindex="0" class="no-outline" @keydown="tecla">
        <q-card-section class="row items-center q-pb-none">
          <div class="col">
            <div class="text-grey-9 text-overline">Gerador de requisição</div>
            <div class="text-h6 text-weight-regular">Qual loja vai ser reposta?</div>
          </div>
          <GeradorRascunho />
        </q-card-section>
        <q-card-section>
          <ListaOpcoes ref="listaRef" :opcoes="opcoes" :inicial="inicial" @escolher="escolher" />
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>
