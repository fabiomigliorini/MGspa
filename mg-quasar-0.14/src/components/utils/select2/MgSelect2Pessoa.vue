<template>
<mg-select2 :options="options" @select="onOptionSelect" @filter="search">
  <template slot="item" scope="option">
      <p>
        <strong>{{ option.data.fantasia }}</strong>
        <br> <small>{{ option.data.pessoa }}</small>
      </p>
    </template>
</mg-select2>
</template>
<script>
import MgSelect2 from './MgSelect2'
import { debounce } from 'quasar'
export default {
  name: 'mg-select2-pessoa',
  components: {
    MgSelect2
  },
  data () {
    return {
      filter: {},
      options: []
    }
  },
  methods: {
    onOptionSelect (option) {
      console.log('Selected option:', option)
    },
    search: debounce(function (pessoa) {
      let vm = this
      let params = {
        q: pessoa
      }
      window.axios.get('pessoa/select2', { params }).then(response => {
        vm.options = response.data
      })
    }, 500)
  },
  created () {
    // console.log(this)
  }
}
</script>

<style>
</style>
