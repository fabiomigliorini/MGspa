<template>
    <select :placeholder="placeholder" style="width:100%">
      <option value="pessoa.codpessoa" selected="selected" v-if="pessoa">{{ pessoa.pessoa }}</option>
    </select>
</template>

<script>
import $ from 'jquery'
import 'select2'

export default {
  name: 'mg-select2-pessoa',
  deep: true,
  props: ['value', 'placeholder'],
  data () {
    return {
      pessoa: false
    }
  },
  watch: {
    value: function (value) {
      $(this.$el).val(value).trigger('change')
      console.log('Mudou o valor')
    }
  },
  mounted: function () {
    let vm = this

    if (vm.value) {
      let params = {
        id: vm.value
      }
      window.axios.get('pessoa/select2', { params }).then(function (request) {
        vm.pessoa = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    }

    $(this.$el)
      .select2({
        placeholder: vm.placeholder,
        language: 'pt-BR',
        minimumInputLength: 2,
        allowClear: true,
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
        escapeMarkup: function (markup) {
          return markup
        },
        templateResult: formatData,
        templateSelection: formatDataSelection
      })
      .trigger('change')
        .on('change', function () {
          vm.$emit('input', this.value)
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
  destroyed: function () {
    $(this.$el).off().select2('destroy')
  }
}
</script>

<style>
</style>
