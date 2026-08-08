<script setup>
import { computed, inject } from 'vue'

// No recursivo da arvore XML. Nao guarda estado proprio: o conjunto de nos colapsados
// vive no MgXmlViewer e chega por inject. Isso permite o "expandir/recolher tudo" e faz
// um clique invalidar so o no clicado, em vez da arvore inteira.
const props = defineProps({
  no: { type: Object, required: true },
})

const colapsados = inject('mgXmlColapsados')

const aberto = computed(() => !colapsados.value.has(props.no.id))

// Muta o Set em vez de recriar: o proxy de colecao do Vue rastreia has(chave) por chave.
const alternar = () => {
  if (!props.no.filhos.length) return
  const set = colapsados.value
  if (set.has(props.no.id)) set.delete(props.no.id)
  else set.add(props.no.id)
}

// A linha vira uma lista de pedacos coloridos montada aqui, e nao spans soltos no
// template: sem `white-space: pre`, a quebra de linha entre dois spans do template
// viraria um espaco visivel no meio da tag.
const pedacos = computed(() => {
  const no = props.no
  const lista = [
    { texto: '<', cor: 'text-grey-5' },
    { texto: no.nome, cor: 'text-blue-9' },
  ]
  for (const atributo of no.atributos) {
    lista.push({ texto: ` ${atributo.nome}`, cor: 'text-deep-purple-7' })
    lista.push({ texto: '="', cor: 'text-grey-5' })
    lista.push({ texto: atributo.valor, cor: 'text-red-8' })
    lista.push({ texto: '"', cor: 'text-grey-5' })
  }
  lista.push({ texto: '>', cor: 'text-grey-5' })
  if (!no.filhos.length) {
    if (no.texto) lista.push({ texto: no.texto, cor: 'text-green-9' })
    lista.push({ texto: `</${no.nome}>`, cor: 'text-grey-5' })
  } else if (!aberto.value) {
    lista.push({ texto: `…</${no.nome}>`, cor: 'text-grey-5' })
  }
  return lista
})
</script>

<template>
  <div>
    <div class="row no-wrap items-start">
      <q-icon
        :name="no.filhos.length ? (aberto ? 'arrow_drop_down' : 'arrow_right') : 'remove'"
        size="18px"
        :class="no.filhos.length ? 'cursor-pointer text-grey-7' : 'text-grey-4'"
        @click="alternar"
      />
      <div class="col" :class="{ 'cursor-pointer': no.filhos.length }" @click="alternar">
        <span v-for="(p, i) in pedacos" :key="i" :class="p.cor">{{ p.texto }}</span>
        <q-badge
          v-if="no.filhos.length && !aberto"
          color="grey-3"
          text-color="grey-8"
          class="q-ml-xs"
          :label="no.filhos.length"
        />
      </div>
    </div>
    <!-- v-if e nao v-show: subarvore colapsada nunca monta, o que dispensa virtualizacao -->
    <div v-if="no.filhos.length && aberto" class="q-pl-md">
      <MgXmlNo v-for="filho in no.filhos" :key="filho.id" :no="filho" />
    </div>
  </div>
</template>
