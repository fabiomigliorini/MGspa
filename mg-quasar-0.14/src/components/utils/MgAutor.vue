<template>
  <span>
    <span>
      <q-icon name="add" />
      <img v-if="imagemusuariocriacao" :src="imagemusuariocriacao" class="avatar"/>
      <span v-else-if="usuariocriacao">
        {{ usuariocriacao }}
      </span>
      <small>
      {{ moment(data.criacao).fromNow() }}
      </small>
      <q-popover>
        Criado por
        <router-link :to="{ path: '/usuario/' + data.codusuariocriacao }">
          {{ pessoacriacao }}
        </router-link>,
        {{ moment(data.criacao).format('LLLL') }}
      </q-popover>
    </span>

    <span>
      <q-icon name="edit" />
      <img v-if="imagemusuarioalteracao" :src="imagemusuarioalteracao" class="avatar"/>
      <span v-else-if="usuarioalteracao">
        {{ usuarioalteracao }}
      </span>
      <small>
      {{ moment(data.alteracao).fromNow() }}
      </small>
      <q-popover>
        <div class="group" style="width: 220px; padding:10px; text-align: center;">
          Alterado por
          <router-link :to="{ path: '/usuario/' + data.codusuarioalteracao }">
            {{ pessoaalteracao }}
          </router-link>,
          {{ moment(data.alteracao).format('LLLL') }}
        </div>
      </q-popover>
    </span>


    <!--
    <div class="row items-baseline">
      <div class="col">
        teste
      </div>
      <div class="col">

        <a>
          Hover
          <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">
            <strong>Tooltip</strong> on <em>top</em> (<q-icon name="keyboard_arrow_up" />)
          </q-tooltip>
        </a>
        <span v-if="data.criacao || data.codusuariocriacao">
          <span v-if="data.criacao">
            <img :src="imagemusuariocriacao" v-if="imagemusuariocriacao" class="q-item-avatar"/>
            <abbr :title="moment(data.alteracao).format('LLLL')">{{ moment(data.criacao).fromNow() }}</abbr>
            <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">
              <strong>Tooltip</strong> on <em>top</em> (<q-icon name="keyboard_arrow_up" />)
            </q-tooltip>
          </span>
          <span v-if="data.codusuariocriacao">
            por
            <router-link :to="{ path: '/usuario/' + data.codusuariocriacao }">
              <abbr :title="pessoacriacao">{{ usuariocriacao }}</abbr>
              <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">
                <strong>Tooltip</strong> on <em>top</em> (<q-icon name="keyboard_arrow_up" />)
              </q-tooltip>
            </router-link>
          </span>
        </span>
        <BR class="xs" />
        <span v-if="data.alteracao && (data.alteracao !== data.criacao) || data.codusuarioalteracao">
          <q-icon name="edit" />
          <span v-if="data.alteracao && (data.alteracao !== data.criacao)"> <abbr :title="moment(data.alteracao).format('LLLL')">{{ moment(data.alteracao).fromNow() }}</abbr> </span>
          <span v-if="data.codusuarioalteracao">
            por
            <router-link :to="{ path: '/usuario/' + data.codusuarioalteracao }">
              <abbr :title="pessoaalteracao">{{ usuarioalteracao }}</abbr>
            </router-link>
          </span>
        </span>
      </div>
    </div>
    -->
  </span>
</template>

<script>

  import { QIcon, QBtn, QPopover, QList, QItem, QItemSide, QItemMain } from 'quasar'

  export default {

    name: 'mg-autor',

    components: {
      QIcon,
      QBtn,
      QPopover,
      QList,
      QItem,
      QItemSide,
      QItemMain
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

          // faz chamada api
          if (this.data.codusuariocriacao != null) {
            window.axios.get('usuario/' + this.data.codusuariocriacao + '/autor', { params }).then(response => {
              vm.usuariocriacao = response.data.usuario
              vm.pessoacriacao = response.data.pessoa
              vm.imagemusuariocriacao = response.data.imagem
            })
          }

          // faz chamada api
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

img {
  max-height: 40px;
  max-width: 40px;
}
</style>
