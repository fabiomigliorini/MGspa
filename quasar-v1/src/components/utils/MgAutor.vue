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

      <q-btn color="primary">
        <q-menu>
          <q-card>
            <q-card-section v-if="imagemusuariocriacao">
              <img :src="imagemusuariocriacao">
            </q-card-section>
            <q-card-section v-if="usuariocriacao" class="text-subtitle1">
              <router-link :to="{ path: '/usuario/' + data.codusuariocriacao }">
                {{ usuariocriacao }}
              </router-link>
            </q-card-section>
            <q-card-section>
              <span class="text-faded" v-if="pessoacriacao">{{ pessoacriacao }}<br /></span>
              <small class="text-faded" v-if="data.criacao">{{ moment(data.criacao).format('LLLL') }}</small>
            </q-card-section>
          </q-card>
        </q-menu>
      </q-btn>
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
      <q-btn color="primary">
        <q-card>
          <q-card-section v-if="imagemusuarioalteracao">
            <img :src="imagemusuarioalteracao">
          </q-card-section>
          <q-card-section v-if="usuarioalteracao" class="text-subtitle1">
            <router-link :to="{ path: '/usuario/' + data.codusuarioalteracao }">
              {{ usuarioalteracao }}
            </router-link>
          </q-card-section>
          <q-card-section>
            <span class="text-faded" v-if="pessoaalteracao">{{ pessoaalteracao }}<br /></span>
            <small class="text-faded" v-if="data.alteracao">{{ moment(data.alteracao).format('LLLL') }}</small>
          </q-card-section>
        </q-card>
      </q-btn>
    </span>
  </span>
</template>

<script>

  export default {
    name: 'mg-autor',
    components: {
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
          };

          const vm = this;

          // TODO: Adicionar cache usando vuex para não chamar API toda hora
          if (this.data.codusuariocriacao != null) {
            vm.$axios.get('usuario/' + this.data.codusuariocriacao + '/autor', { params }).then(response => {
              vm.usuariocriacao = response.data.usuario;
              vm.pessoacriacao = response.data.pessoa;
              vm.imagemusuariocriacao = response.data.imagem
            })
          }

          // TODO: Adicionar cache usando vuex para não chamar API toda hora
          if (this.data.codusuarioalteracao != null) {
            vm.$axios.get('usuario/' + this.data.codusuarioalteracao + '/autor', { params }).then(response => {
              vm.usuarioalteracao = response.data.usuario;
              vm.pessoaalteracao = response.data.pessoa;
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
