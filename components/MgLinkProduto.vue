<script setup>
// O nome do produto como link para o cadastro dele no MGLara, em outra aba. O clique e o Enter
// param no link: dentro de um q-item clicável, abrem o produto e não a ação da linha. Não usar
// dentro de q-item com :to ou href (ele vira <a> e ficaria link dentro de link).
const props = defineProps({
  codproduto: { type: [Number, String], required: true },
})

const url = () => `${process.env.MGLARA_URL}produto/${props.codproduto}`
</script>

<template>
  <a
    :href="url()"
    target="_blank"
    rel="noopener"
    class="q-link q-link--focusable text-primary"
    @click.stop
    @keyup.stop
  >
    <slot />
  </a>
</template>
