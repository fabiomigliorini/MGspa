<script setup>
// Fotos do borderô de um lançamento da maquineta de parceiro (doc-4, "Itens de parceiro"): mostra
// as que já foram anexadas e, com `podeAnexar` (quem opera o portador), anexa mais uma ou exclui a
// errada pelo periodoStore (a foto não muda valor: vale com o período fechado); o período volta
// com a linha atualizada.
import { ref, watch, onBeforeUnmount } from 'vue'
import { useQuasar } from 'quasar'
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

const $q = useQuasar()
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

function excluir(arquivo) {
  $q.dialog({
    title: 'Excluir a foto',
    message: 'Excluir esta foto do borderô? Não dá para desfazer.',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(() => store.excluirFotoBordero(props.codportadormovimento, arquivo))
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
        <MgEmptyState v-if="!fotos.length && !podeAnexar" plain icon="no_photography">
          Sem a foto do borderô.
        </MgEmptyState>
        <div v-else class="row q-col-gutter-sm">
          <div v-for="f in urls" :key="f.arquivo" class="col-6 col-sm-4">
            <div class="relative-position">
              <a :href="f.url" target="_blank">
                <q-img :src="f.url" :ratio="1" fit="contain" class="rounded-borders" />
              </a>
              <q-btn
                v-if="podeAnexar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="delete"
                class="absolute-top-right"
                :disable="store.salvando"
                @click="excluir(f.arquivo)"
              >
                <q-tooltip>Excluir a foto</q-tooltip>
              </q-btn>
            </div>
          </div>
          <!-- do tamanho das fotos: o quadrado, com o Slim preenchendo -->
          <div v-if="podeAnexar" class="col-6 col-sm-4">
            <q-responsive :ratio="1">
              <MgSlim
                style="position: absolute; inset: 0; min-width: 0; min-height: 0; overflow: hidden"
                label="Toque para fotografar o borderô"
                @imagem="(b) => store.anexarFotoBordero(codportadormovimento, b)"
              />
            </q-responsive>
          </div>
        </div>
        <q-inner-loading :showing="store.salvando" />
      </q-card-section>
      <q-separator inset />
      <q-card-actions align="right">
        <q-btn flat label="Fechar" color="grey-8" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
