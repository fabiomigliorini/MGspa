<template>
  <div class="erros-validacao">
    <div class="msg text-negative" v-for="erro in erros">· {{ erro }}</div>
  </div>
</template>
<style scoped>
.msg {
  font-size: 0.9rem
}
</style>
<script>
  export default {
    name: 'mg-erros-validacao',
    props: ['erros']
  }
</script>
