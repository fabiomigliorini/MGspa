<template>
  <div class="container">
    <small class='text-muted'>{{ token }}</small>
  </div>
</template>

<script>
export default {
  name: 'hello',
  data () {
    return {
      token: null
    }
  },
  mounted () {
    this.token = localStorage.getItem('auth.token')
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
