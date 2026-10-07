<script setup>
// Fotos do borderô de um lançamento da maquineta de parceiro (doc-4, "Itens de parceiro"): mostra
// as que já foram anexadas e, com `podeAnexar` (quem opera o portador), anexa mais uma pelo
// periodoStore (a foto não muda valor: vale com o período fechado); o período volta com a linha
// atualizada.
import { ref, watch, onBeforeUnmount } from 'vue'
import { api } from 'src/services/api'
import MgSlim from '@components/MgSlim.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import { blobUrlFromApi } from '@components/blobUrlFromApi'
import { periodoStore } from '@components/stores/periodoStore'

const props = defineProps({
  codportadormovimento: { type: Number, default: null },
  fotos: { type: Array, default: () => [] },
  podeAnexar: { type: Boolean, default: false },
})
const aberto = defineModel({ type: Boolean, default: false })

const store = periodoStore()
const urls = ref([])
// cada carga tem a sua vez: a que chegar atrasada (fechou ou trocou de linha) é descartada
let vez = 0

function limpar() {
  vez++
  urls.value.forEach((f) => URL.revokeObjectURL(f.url))
  urls.value = []
}

async function carregar() {
  limpar()
  const minha = vez
  for (const arquivo of props.fotos) {
    let url
    try {
      url = await blobUrlFromApi(
        api,
        `v1/portador-movimento/${props.codportadormovimento}/foto/${arquivo}`,
        null,
        store.params(),
      )
    } catch {
      continue // foto que não abre não impede ver as outras
    }
    if (minha !== vez) return URL.revokeObjectURL(url)
    urls.value.push({ arquivo, url })
  }
}

watch(
  () => [aberto.value, props.codportadormovimento, props.fotos.join('|')],
  ([sim]) => (sim ? carregar() : limpar()),
)
onBeforeUnmount(limpar)
</script>

<template>
  <q-dialog v-model="aberto">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-card-section class="text-grey-9 text-overline">FOTO DO BORDERÔ</q-card-section>
      <q-separator inset />
      <q-card-section>
        <div v-if="fotos.length" class="row q-col-gutter-sm q-mb-md">
          <div v-for="f in urls" :key="f.arquivo" class="col-6 col-sm-4">
            <a :href="f.url" target="_blank">
              <q-img :src="f.url" :ratio="1" fit="contain" class="rounded-borders" />
            </a>
          </div>
        </div>
        <MgEmptyState v-else plain icon="no_photography">Sem a foto do borderô.</MgEmptyState>
        <MgSlim
          v-if="podeAnexar"
          label="Toque para fotografar o borderô"
          @imagem="(b) => store.anexarFotoBordero(codportadormovimento, b)"
        />
        <q-inner-loading :showing="store.salvando" />
      </q-card-section>
      <q-separator inset />
      <q-card-actions align="right">
        <q-btn flat label="Fechar" color="grey-8" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
