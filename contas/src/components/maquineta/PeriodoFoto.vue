<script setup>
// Foto do borderô do período da maquineta (TASK-188 M9.8), para comparar com o borderô do sistema
// ao lado: no computador, a coluna da esquerda, alta e rolável, parada enquanto o detalhe rola;
// no celular, em cima, recolhível. Clicar abre a foto inteira em outra aba (para dar zoom).
// Opcional: sem foto o período fica "sem borderô"; anexa e exclui a qualquer hora, também no
// conferido.
import { ref, watch, onBeforeUnmount } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { api } from 'src/services/api'
import { blobUrlFromApi } from '@components/blobUrlFromApi'
import MgSlim from '@components/MgSlim.vue'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'

const $q = useQuasar()
const store = useMaquinetaPeriodoStore()
const { periodo, salvando } = storeToRefs(store)

const fotos = ref([])
async function carregarFotos() {
  fotos.value.forEach((f) => URL.revokeObjectURL(f.url))
  fotos.value = []
  const cod = periodo.value?.codmaquinetalote
  for (const arquivo of periodo.value?.fotos ?? []) {
    try {
      const url = await blobUrlFromApi(api, `v1/maquineta-lote/${cod}/foto/${arquivo}`, null)
      fotos.value.push({ arquivo, url })
    } catch {
      // foto que não abre não impede a conferência
    }
  }
}
function excluirFoto(arquivo) {
  $q.dialog({
    title: 'Excluir a foto',
    message: 'Excluir esta foto do borderô? Não dá para desfazer.',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(() => store.excluirFoto(arquivo))
}
watch(() => periodo.value?.fotos?.join('|') + periodo.value?.codmaquinetalote, carregarFotos, {
  immediate: true,
})
onBeforeUnmount(() => fotos.value.forEach((f) => URL.revokeObjectURL(f.url)))
</script>

<template>
  <q-card flat bordered :style="$q.screen.gt.sm ? 'position: sticky; top: 16px' : ''">
    <q-expansion-item
      :default-opened="$q.screen.gt.sm"
      :label="fotos.length ? `Foto do borderô (${fotos.length})` : 'Foto do borderô'"
      header-class="text-subtitle1 text-weight-medium"
    >
      <q-card-section
        class="q-pt-none"
        :style="$q.screen.gt.sm ? 'max-height: calc(100vh - 120px); overflow-y: auto' : ''"
      >
        <div v-for="f in fotos" :key="f.arquivo" class="relative-position q-mb-sm">
          <a :href="f.url" target="_blank">
            <q-img :src="f.url" fit="contain" class="rounded-borders" />
          </a>
          <q-btn
            flat
            round
            size="sm"
            color="grey-7"
            icon="delete"
            class="absolute-top-right bg-white"
            :disable="salvando"
            @click="excluirFoto(f.arquivo)"
          >
            <q-tooltip>Excluir a foto</q-tooltip>
          </q-btn>
        </div>
        <MgSlim label="Toque para fotografar o borderô" @imagem="store.anexarFoto" />
      </q-card-section>
    </q-expansion-item>
  </q-card>
</template>
