<template>
  <q-search v-model="terms" :placeholder="placeholder">
    <q-autocomplete v-on:selected="selected" @search="search" @selected="selected" :min-characters="3" :debounce="600"/>
  </q-search>
</template>

<script>

import { QSearch, QAutocomplete } from 'quasar'

export default {
  name: 'mg-autocomplete-pessoa',
  props: [
    'placeholder'
  ],
  components: {
    QSearch, QAutocomplete
  },
  data () {
    return {
      terms: ''
    }
  },
  watch: {
    terms: {
      handler: function (val, oldVal) {
        if (val.length === 0) {
          let vm = this
          vm.$emit('seleciona', null)
        }
      }
    }
  },
  methods: {
    selected (item) {
      let vm = this
      vm.$emit('seleciona', item.id)
    },
    search (terms, done) {
      let params = {}
      params.sort = 'fantasia'
      params.pessoa = terms
      window.axios.get('pessoa/autocomplete', { params }).then(response => {
        let results = response.data
        done(results)
      }).catch(function (error) {
        done([])
        console.log(error.response)
      })
    }
  }
}
</script>

<style>
</style>
