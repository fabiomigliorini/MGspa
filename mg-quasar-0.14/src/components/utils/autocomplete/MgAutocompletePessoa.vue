<template>
  <q-search v-model="terms"  :init="init" :placeholder="placeholder">
    <q-autocomplete
      v-on:selected="selected"
      @search="search"
      @selected="selected"
      :min-characters="3"
      :max-results="90"
      :debounce="600"
      />
  </q-search>
</template>

<script>
import {
  QAutocomplete,
  QSearch
} from 'quasar'

export default {
  name: 'mg-autocomplete-pessoa',
  props: ['init', 'placeholder'],
  components: {
    QAutocomplete,
    QSearch
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
    },
    init: {
      handler: function (val, oldVal) {
        if (val !== null) {
          this.initSelect(val)
        }
      }
    }
  },
  methods: {
    initSelect (codpessoa) {
      let vm = this
      window.axios.get('pessoa/' + codpessoa).then(response => {
        let pessoa = response.data
        vm.terms = pessoa.pessoa
      }).catch(function (error) {
        console.log(error)
      })
    },
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
