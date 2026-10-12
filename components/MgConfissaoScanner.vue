<script setup>
// Escanear a confissão de dívida assinada (PDV → Confissão de Dívida; contas → Fechamentos, na
// conferência da duplicata). Foto → o servidor lê o QR (ou o texto) e acha a venda → Anexar. Se
// não achar, digita o código da venda e o valor e procura. Quem usa chama
// `confissaoStore().configurar({ fixos })` antes (o PDV manda o uuid do dispositivo).
import { ref, watch } from 'vue'
import { confissaoStore } from '@components/stores/confissaoStore'
import MgSlim from '@components/MgSlim.vue'
import MgInputValor from '@components/MgInputValor.vue'

const emit = defineEmits(['anexada'])

const store = confissaoStore()
const slim = ref(null)

const RATIOS = [
  { value: '1:2', label: 'Confissão Impressora Térmica' },
  { value: 'free', label: 'Livre' },
]
const ratio = ref('1:2')

// a imagem saiu do store (enviada ou descartada): limpa o recorte
watch(
  () => store.imagem,
  (imagem) => {
    if (imagem == null) {
      slim.value?.remover()
    }
  },
)

const anexar = async () => {
  const codnegocio = await store.enviarConfissao()
  if (codnegocio) {
    emit('anexada', codnegocio)
  }
}
</script>

<template>
  <div>
    <q-select
      v-model="ratio"
      :options="RATIOS"
      outlined
      emit-value
      map-options
      label="Tamanho"
      class="q-mb-md"
    />
    <MgSlim
      ref="slim"
      :ratio="ratio"
      manter
      label="Toque para fotografar a confissão"
      :size="{ width: 800, height: 1600 }"
      :jpeg-compression="80"
      @imagem="(b64) => store.novaImagem(b64, ratio)"
      @removida="store.descartar()"
    />
    <div class="row q-col-gutter-md q-mt-sm">
      <div class="col-6">
        <MgInputValor
          v-model="store.codnegocio"
          label="Venda"
          :decimals="0"
          :min="0"
          :grouping="false"
          :disable="store.encontrados == 1"
        />
      </div>
      <div class="col-6">
        <MgInputValor
          v-model="store.valor"
          label="Valor"
          :min="0"
          :readonly="store.encontrados == 1"
        />
      </div>
    </div>
    <div class="row justify-end q-gutter-sm q-mt-sm">
      <q-btn
        v-if="store.imagem && store.encontrados != 1"
        flat
        color="accent"
        icon="find_in_page"
        label="Procurar"
        @click="store.procurar()"
      />
      <q-btn
        v-if="store.encontrados == 1"
        unelevated
        color="primary"
        icon="upload"
        label="Anexar"
        :loading="store.enviando"
        @click="anexar"
      />
    </div>
  </div>
</template>
