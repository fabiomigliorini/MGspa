<template>
  <div class="autocomplete-input">
    <p class="control has-icon has-icon-right">
      <input
        v-model="keyword"
        class="input is-large"
        placeholder="Search..."
        @input="onInput($event.target.value)"
        @keyup.esc="isOpen = false"
        @blur="isOpen = false"
        @keydown.down="moveDown"
        @keydown.up="moveUp"
        @keydown.enter="select"
      >
      <i class="fa fa-angle-down"></i>
    </p>
    <ul v-show="isOpen"
      class="options-list"
    >
      <li v-for="(option, index) in fOptions"
        :class="{
          'highlighted': index === highlightedPosition
        }"
        @mouseenter="highlightedPosition = index"
        @mousedown="select"
      >
        <slot name="item" :data="option"></slot>
      </li>
    </ul>
  </div>
</template>

<script>
  // import { QSelect } from 'quasar'
  export default {
    name: 'mg-select2',
    props: {
      options: {
        type: Array,
        required: true
      }
    },
    data () {
      return {
        isOpen: false,
        highlightedPosition: 0,
        keyword: ''
      }
    },
    computed: {
      fOptions () {
        // const re = new RegExp(this.keyword, 'i')
        // console.log(this.options)
        return this.options
        // console.log(this.options)
        // return this.options.filter(o => o.title.match(re))
      }
    },
    watch: {
      keyword: {
        handler: function (val, oldVal) {
          if (val =! 'undefined') {
            this.$emit('filter', val)
          }
        }
      }
    },
    methods: {
      onInput (value) {
        this.highlightedPosition = 0
        this.isOpen = !!value
      },
      moveDown () {
        if (!this.isOpen) {
          return
        }
        this.highlightedPosition =
          (this.highlightedPosition + 1) % this.fOptions.length
      },
      moveUp () {
        if (!this.isOpen) {
          return
        }
        this.highlightedPosition = this.highlightedPosition - 1 < 0
          ? this.fOptions.length - 1
          : this.highlightedPosition - 1
      },
      select () {
        const selectedOption = this.fOptions[this.highlightedPosition]
        this.$emit('select', selectedOption)
        this.isOpen = false
        this.keyword = selectedOption.title
      }
    }
  }
</script>
<style scoped>
  ul {
    list-style-type: none;
    padding: 0;
  }

  li {
    display: inline-block;
    margin: 0 10px;
  }

  .autocomplete-input {
    position: relative;
  }

  ul.options-list {
    display: flex;
    flex-direction: column;
    margin-top: -12px;
    border: 1px solid #dbdbdb;
    border-radius: 0 0 3px 3px;
    position: absolute;
    width: 100%;
    overflow: hidden;
  }

  ul.options-list li {
    width: 100%;
    flex-wrap: wrap;
    background: white;
    margin: 0;
    border-bottom: 1px solid #eee;
    color: #363636;
    padding: 7px;
    cursor: pointer;
  }

  ul.options-list li.highlighted {
    background: #f8f8f8
  }
</style>
