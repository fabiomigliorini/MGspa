<template>
<mg-select2 :options="options" @select="onOptionSelect" @filter="search">
  {{ selected }} +++
  <template v-if="selected">
    <span>{{ selected.fantasia }}</span>
  </template>
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
      options: [],
      selected: false
    }
  },
  methods: {
    onOptionSelect (option) {
      // console.log('Selected option:', option)
      this.selected = option
    },
    search: debounce(function (pessoa) {
      console.log(pessoa)
      let vm = this
      if (pessoa !== undefined) {
        let params = {
          q: pessoa
        }
        if (!this.selected) {
          window.axios.get('pessoa/select2', { params }).then(response => {
            vm.options = response.data
          })
        }
      }
    }, 500)
  },
  created () {
    // console.log(this)
  }
}
</script>

<style>
</style>
