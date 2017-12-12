<template>
<mg-select2 :options="options" @select="onOptionSelect">
  <template slot="item" scope="option">
      <p>
        <strong>{{ option.title }}</strong>
        <br> <small>{{ option.description }}</small>
      </p>
    </template>
</mg-select2>
</template>
<script>
import MgSelect2 from './MgSelect2'
export default {
  name: 'mg-select2-pessoa',
  components: {
    MgSelect2
  },
  data () {
    return {
      options: [
        {
          title: 'First Scene',
          description: 'lorem ipsum dolor amet.',
          thumbnail: 'http://lorempicsum.com/nemo/200/200/1'
        },
        {
          title: 'Second Scene',
          description: 'lorem ipsum dolor amet.',
          thumbnail: 'http://lorempicsum.com/nemo/200/200/2'
        },
        {
          title: 'Third Scene',
          description: 'lorem ipsum dolor amet.',
          thumbnail: 'http://lorempicsum.com/nemo/200/200/3'
        },
        {
          title: 'Fourth Scene',
          description: 'lorem ipsum dolor amet.',
          thumbnail: 'http://lorempicsum.com/nemo/200/200/4'
        }
      ]
    }
  },
  methods: {
    onOptionSelect (option) {
      console.log('Selected option:', option)
    }
  }
}
</script>

<style>
</style>
