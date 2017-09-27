<template>
    <select :placeholder="placeholder" style="width:100%"></select>
</template>

<script>
import $ from 'jquery'
import 'select2'

export default {
  name: 'mg-select2-pessoa',
  props: ['value', 'placeholder'],
  data () {
    return {
      selected: 2
    }
  },
  mounted: function () {
    let vm = this
    $(this.$el)
      .select2({
        ajax: {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Authorization: `Bearer ${localStorage.getItem('auth.token')}`
          },
          url: process.env.API_BASE_URL + 'pessoa/select2',
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              q: params.term,
              page: params.page
            }
          },
          processResults: function (data, params) {
            params.page = params.page || 1

            return {
              results: data.items,
              pagination: {
                more: (params.page * 30) < data.total_count
              }
            }
          },
          cache: true
        },
        placeholder: vm.placeholder,
        escapeMarkup: function (markup) {
          return markup
        }, // let our custom formatter work
        minimumInputLength: 1,
        templateResult: formatData,
        templateSelection: formatDataSelection
      })
      .trigger('change')
        .on('change', function () {
          vm.$emit('input', this.value)
          console.log(this.value)
        })

    function formatData (data) {
      if (data.loading) {
        return data.text
      }

      var markup = '' +
        '<div class="q-item-main q-item-section">' +
          '<div class="q-item-label">' +
            data.fantasia + '<div class="right q-item-stamp">' + data.id + '</div>' +
          '</div>' +
          '<div class="q-item-sublabel">' +
            data.pessoa + '<div class="right q-item-stamp">' + data.cnpj + '</div>' +
          '</div>'

      if (data.inativo) {
        markup += '<div class="text-negative">' +
          'inativo desde ' + data.inativo +
        '</div>'
      }

      markup += '</div>'

      return markup
    }

    function formatDataSelection (data) {
      return data.fantasia || data.text
    }
  },
  watch: {
    value: function (value) {
      // update value
      $(this.$el).val(value).trigger('change')
    }
    // options: function (options) {
    //   // update options
    //   $(this.$el).select2({
    //     data: options
    //   })
    // }
  }
  // destroyed: function () {
  //   $(this.$el).off().select2('destroy')
  // }
}
</script>

<style>
</style>
