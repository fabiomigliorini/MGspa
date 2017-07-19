<template>
  <div class="erros-validacao">
    <div class="text-negative" v-for="erro in erros">· {{ erro }}</div>
  </div>
</template>
<script>
  export default {
    name: 'erros-validacao',
    props: ['erros']
  }
</script>
