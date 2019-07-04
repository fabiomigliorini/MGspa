<template>
  <div class="erros-validacao">
    <div class="text-caption text-negative" v-for="erro in erros">{{ erro }}</div>
  </div>
</template>
<style scoped>
</style>
<script>
  export default {
    name: 'mg-erros-validacao',
    props: ['erros']
  }
</script>
