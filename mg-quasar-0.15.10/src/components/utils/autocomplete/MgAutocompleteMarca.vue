<template>
  <q-search clearable v-model="terms"  :init="init" :placeholder="placeholder">
    <q-autocomplete
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
  name: 'mg-autocomplete-marca',
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
          vm.$emit('input', null)
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
    initSelect (codmarca) {
      let vm = this
      vm.$axios.get('marca/' + codmarca).then(response => {
        let marca = response.data
        vm.terms = marca.marca
      }).catch(function (error) {
        console.log(error)
      })
    },
    selected (item) {
      let vm = this
      vm.$emit('input', item.id)
    },
    search (terms, done) {
      let vm = this
      let params = {}
      params.sort = 'marca'
      params.marca = terms
      vm.$axios.get('marca/autocompletar', { params }).then(response => {
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
