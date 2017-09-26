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
          url: 'https://api.github.com/search/repositories',
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
        templateResult: formatRepo,
        templateSelection: formatRepoSelection
      })
      .trigger('change')
        .on('change', function () {
          vm.$emit('input', this.value)
          console.log(this.value)
        })

    function formatRepo (repo) {
      if (repo.loading) {
        return repo.text
      }

      var markup = '<div class="select2-result-repository clearfix">' +
        '<div class="select2-result-repository__avatar"><img src=' + repo.owner.avatar_url + ' /></div>' +
        '<div class="select2-result-repository__meta">' +
        '<div class="select2-result-repository__title">' + repo.full_name + '</div>'

      if (repo.description) {
        markup += '<div class="select2-result-repository__description">' + repo.description + '</div>'
      }

      markup += '<div class="select2-result-repository__statistics">' +
        '<div class="select2-result-repository__forks"><i class="fa fa-flash"></i> ' + repo.forks_count + ' Forks</div>' +
        '<div class="select2-result-repository__stargazers"><i class="fa fa-star"></i> ' + repo.stargazers_count + ' Stars</div>' +
        '<div class="select2-result-repository__watchers"><i class="fa fa-eye"></i> ' + repo.watchers_count + ' Watchers</div>' +
        '</div>' +
        '</div></div>'

      return markup
    }

    function formatRepoSelection (repo) {
      return repo.full_name || repo.text
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
