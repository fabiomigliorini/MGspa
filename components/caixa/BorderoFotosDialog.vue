<script setup>
// Fotos do borderô de um lançamento da maquineta de parceiro (doc-4, "Itens de parceiro"): mostra
// as que já foram anexadas e, para quem opera o portador, anexa mais uma (a foto não muda valor:
// vale com o período fechado). `anexar(base64)` envia e devolve se deu certo; a tela que usa
// recarrega a linha.
import { ref, watch, onBeforeUnmount } from 'vue'
import { api } from 'src/services/api'
import MgSlim from '@components/MgSlim.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import { blobUrlFromApi } from '@components/blobUrlFromApi'

const props = defineProps({
  codportadormovimento: { type: Number, default: null },
  fotos: { type: Array, default: () => [] },
  podeAnexar: { type: Boolean, default: false },
  anexar: { type: Function, default: null },
})
const aberto = defineModel({ type: Boolean, default: false })

const urls = ref([])
const enviando = ref(false)

function limpar() {
  urls.value.forEach((f) => URL.revokeObjectURL(f.url))
  urls.value = []
}

async function carregar() {
  limpar()
  for (const arquivo of props.fotos) {
    try {
      const url = await blobUrlFromApi(
        api,
        `v1/portador-movimento/${props.codportadormovimento}/foto/${arquivo}`,
        null,
      )
      urls.value.push({ arquivo, url })
    } catch {
      // foto que não abre não impede ver as outras
    }
  }
}

async function enviar(base64) {
  if (!props.anexar) return
  enviando.value = true
  try {
    await props.anexar(base64)
  } finally {
    enviando.value = false
  }
}

watch(
  () => [aberto.value, props.fotos.join('|')],
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
        <div v-if="urls.length" class="row q-col-gutter-sm q-mb-md">
          <div v-for="f in urls" :key="f.arquivo" class="col-6 col-sm-4">
            <a :href="f.url" target="_blank">
              <q-img :src="f.url" :ratio="1" fit="contain" class="rounded-borders" />
            </a>
          </div>
        </div>
        <MgEmptyState v-else-if="!fotos.length" plain icon="no_photography">
          Sem a foto do borderô.
        </MgEmptyState>
        <MgSlim v-if="podeAnexar" label="Toque para fotografar o borderô" @imagem="enviar" />
        <q-inner-loading :showing="enviando" />
      </q-card-section>
      <q-separator inset />
      <q-card-actions align="right">
        <q-btn flat label="Fechar" color="grey-8" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
