<script setup>
// Lista de opções operada pelo teclado: ↑/↓ movem, Enter escolhe, tecla numérica escolhe direto.
// Quem tem o foco é o dialog pai, que repassa o evento via tecla(e).
import { ref, watch, nextTick, onMounted } from 'vue'

const props = defineProps({
  opcoes: {
    type: Array,
    required: true,
  },
  // valor da opção que começa selecionada (ex.: conta PIX padrão do PDV)
  inicial: {
    type: [String, Number],
    default: null,
  },
})

const emit = defineEmits(['escolher'])

const indice = ref(0)
const itens = ref([])
// enquanto o operador não navega, a seleção acompanha a opção inicial (padrão do PDV),
// que pode chegar depois da montagem (listas carregadas do Dexie)
const interagiu = ref(false)

const selecaoInicial = () => {
  if (props.inicial != null) {
    const i = props.opcoes.findIndex((o) => o.valor === props.inicial && !o.desabilitado)
    if (i > -1) {
      return i
    }
  }
  const i = props.opcoes.findIndex((o) => !o.desabilitado)
  return i > -1 ? i : 0
}

const rolarAteSelecionado = () => {
  nextTick(() => {
    itens.value[indice.value]?.$el?.scrollIntoView({ block: 'nearest' })
  })
}

watch(
  [() => props.opcoes, () => props.inicial],
  () => {
    if (
      !interagiu.value ||
      props.opcoes[indice.value]?.desabilitado ||
      indice.value >= props.opcoes.length
    ) {
      indice.value = selecaoInicial()
      rolarAteSelecionado()
    }
  },
  { immediate: true },
)

// ao trocar de uma etapa com campo de texto para uma lista, o foco cai no body e o
// dialog para de receber teclas: devolve o foco ao card (ancestral com tabindex)
const raiz = ref(null)
onMounted(() => {
  nextTick(() => {
    const dentro = raiz.value?.$el?.closest('[tabindex="0"]')
    if (dentro && !dentro.contains(document.activeElement)) {
      dentro.focus()
    }
  })
})

const mover = (delta) => {
  interagiu.value = true
  const total = props.opcoes.length
  let i = indice.value
  for (let n = 0; n < total; n++) {
    i = (i + delta + total) % total
    if (!props.opcoes[i].desabilitado) {
      indice.value = i
      rolarAteSelecionado()
      return
    }
  }
}

const escolher = (i) => {
  const opcao = props.opcoes[i]
  if (!opcao || opcao.desabilitado) {
    return false
  }
  indice.value = i
  emit('escolher', opcao)
  return true
}

// devolve true quando consumiu a tecla
const tecla = (e) => {
  switch (e.key) {
    case 'ArrowDown':
      mover(1)
      return true
    case 'ArrowUp':
      mover(-1)
      return true
    case 'Enter':
      return escolher(indice.value)
    default: {
      const i = props.opcoes.findIndex((o) => o.tecla != null && String(o.tecla) === e.key)
      return i > -1 ? escolher(i) : false
    }
  }
}

defineExpose({ tecla })
</script>
<template>
  <q-list ref="raiz">
    <q-item
      v-for="(opcao, i) in opcoes"
      :key="opcao.valor ?? i"
      :ref="(el) => (itens[i] = el)"
      clickable
      v-ripple
      :active="i === indice"
      active-class="bg-blue-1 text-primary"
      :disable="opcao.desabilitado"
      @click="escolher(i)"
    >
      <q-item-section avatar>
        <q-avatar
          v-if="opcao.tecla != null"
          size="44px"
          font-size="20px"
          :color="i === indice ? 'primary' : 'grey-3'"
          :text-color="i === indice ? 'white' : 'grey-8'"
          class="text-weight-bold"
        >
          {{ opcao.tecla }}
        </q-avatar>
        <q-icon v-else-if="opcao.icone" :name="opcao.icone" size="md" />
      </q-item-section>
      <q-item-section>
        <q-item-label class="text-h6 text-weight-regular">{{ opcao.label }}</q-item-label>
        <q-item-label class="text-body2 text-grey-7" v-if="opcao.desabilitado && opcao.motivo">
          {{ opcao.motivo }}
        </q-item-label>
        <q-item-label class="text-body2 text-grey-7" v-else-if="opcao.caption">
          {{ opcao.caption }}
        </q-item-label>
      </q-item-section>
      <q-item-section side v-if="opcao.logo">
        <q-avatar rounded size="44px">
          <img :src="opcao.logo" />
        </q-avatar>
      </q-item-section>
      <q-item-section side v-else-if="opcao.tecla != null && opcao.icone">
        <q-icon :name="opcao.icone" size="md" />
      </q-item-section>
    </q-item>
  </q-list>
</template>
