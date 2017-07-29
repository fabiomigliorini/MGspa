<template>
  <span>
    <span>
      <q-icon name="add" />
      <img v-if="imagemusuariocriacao" :src="imagemusuariocriacao" class="avatar"/>
      <span v-else-if="usuariocriacao">
        {{ usuariocriacao }}
      </span>
      <small v-if="data.criacao">
        {{ moment(data.criacao).fromNow() }}
      </small>
      <q-popover style="width: 220px;">
        <q-card-media v-if="imagemusuariocriacao">
          <img :src="imagemusuariocriacao">
        </q-card-media>
        <q-card-title v-if="usuariocriacao">
          <router-link :to="{ path: '/usuario/' + data.codusuariocriacao }">
            {{ usuariocriacao }}
          </router-link>
        </q-card-title>
        <q-card-main>
          <span class="text-faded" v-if="pessoacriacao">{{ pessoacriacao }}<br /></span>
          <small class="text-faded" v-if="data.criacao">{{ moment(data.criacao).format('LLLL') }}</small>
        </q-card-main>
      </q-popover>
    </span>

    <span>
      <q-icon name="edit" />
      <img v-if="imagemusuarioalteracao" :src="imagemusuarioalteracao" class="avatar"/>
      <span v-else-if="usuarioalteracao">
        {{ usuarioalteracao }}
      </span>
      <small v-if="data.alteracao">
        {{ moment(data.alteracao).fromNow() }}
      </small>
      <q-popover style="width: 220px;">
        <q-card-media v-if="imagemusuarioalteracao">
          <img :src="imagemusuarioalteracao">
        </q-card-media>
        <q-card-title v-if="usuarioalteracao">
          <router-link :to="{ path: '/usuario/' + data.codusuarioalteracao }">
            {{ usuarioalteracao }}
          </router-link>
        </q-card-title>
        <q-card-main>
          <span class="text-faded" v-if="pessoaalteracao">{{ pessoaalteracao }}<br /></span>
          <small class="text-faded" v-if="data.alteracao">{{ moment(data.alteracao).format('LLLL') }}</small>
        </q-card-main>
      </q-popover>
    </span>
  </span>
</template>

<script>

  import { QIcon, QBtn, QPopover, QList, QItem, QItemSide, QItemMain, QCard, QCardMedia, QCardTitle, QCardMain, QCardSeparator } from 'quasar'

  export default {

    name: 'mg-autor',

    components: {
      QIcon,
      QBtn,
      QPopover,
      QList,
      QItem,
      QItemSide,
      QItemMain,
      QCard,
      QCardMedia,
      QCardTitle,
      QCardMain,
      QCardSeparator
    },

    props: [
      'data'
    ],

    data () {
      return {
        usuariocriacao: null,
        pessoacriacao: null,
        imagemusuariocriacao: null,
        usuarioalteracao: null,
        pessoaalteracao: null,
        imagemusuarioalteracao: null
      }
    },

    watch: {

      // observa filtro, sempre que alterado chama a api
      data: {
        handler: function (val, oldVal) {
          const params = {
            // fields: 'codusuario,usuario'
          }

          const vm = this
          console.log(this.data.codusuariocriacao)

          // TODO: Adicionar cache usando vuex para não chamar API toda hora
          if (this.data.codusuariocriacao != null) {
            window.axios.get('usuario/' + this.data.codusuariocriacao + '/autor', { params }).then(response => {
              vm.usuariocriacao = response.data.usuario
              vm.pessoacriacao = response.data.pessoa
              vm.imagemusuariocriacao = response.data.imagem
            })
          }

          // TODO: Adicionar cache usando vuex para não chamar API toda hora
          if (this.data.codusuarioalteracao != null) {
            window.axios.get('usuario/' + this.data.codusuarioalteracao + '/autor', { params }).then(response => {
              vm.usuarioalteracao = response.data.usuario
              vm.pessoaalteracao = response.data.pessoa
              vm.imagemusuarioalteracao = response.data.imagem
            })
          }
        },
        deep: true
      }

    }

  }
</script>

<style scoped>

img.avatar {
  max-height: 40px;
  max-width: 40px;
}
</style>
