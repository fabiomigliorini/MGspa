<template>
  <div>
    <v-icon :class="classPrimeiraEstrela">{{iconPrimeiraEstrela}}</v-icon>
    <v-icon :class="classSegundaEstrela">{{iconSegundaEstrela}}</v-icon>
    <v-icon :class="classTerceiraEstrela">{{iconTerceiraEstrela}}</v-icon>
  </div>
</template>

<script>
  export default {
    name: 'mg-layout',
    props: {
      abccategoria: {
        default: 4
      }
    },
    computed: {
      classPrimeiraEstrela: function () {
        if (this.abccategoria <= 3) {
          return 'yellow--text text--darken-3'
        } else {
          return 'grey--text text--lighten-1'
        }
      },
      classSegundaEstrela: function () {
        if (this.abccategoria <= 2) {
          return 'yellow--text text--darken-3'
        } else {
          return 'grey--text text--lighten-1'
        }
      },
      classTerceiraEstrela: function () {
        if (this.abccategoria <= 1) {
          return 'yellow--text text--darken-3'
        } else {
          return 'grey--text text--lighten-1'
        }
      },

      iconPrimeiraEstrela: function () {
        if (this.abccategoria <= 3) {
          return 'star'
        } else {
          return 'star_border'
        }
      },
      iconSegundaEstrela: function () {
        if (this.abccategoria <= 2) {
          return 'star'
        } else {
          return 'star_border'
        }
      },
      iconTerceiraEstrela: function () {
        if (this.abccategoria <= 1) {
          return 'star'
        } else {
          return 'star_border'
        }
      }
    }
  }
</script>

<style scoped>
</style>
