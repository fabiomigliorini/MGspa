<template>
  <mg-layout>

    <div slot="titulo">
      {{ dados.marca }}
    </div>

    <div slot="menu">
      <div class="container">
      </div>
    </div>

    <div slot="conteudo">
      <v-card class="elevation-0">
          <v-card-text>
            <v-container fluid>
              <v-layout row wrap>
                <v-flex sm6>
                  <img v-if="dados.codimagem" :src="'http://localhost/MGUplon/public/imagens/'+ dados.codimagem + '.jpg'" width="100%">
                  <img v-else :src="'http://localhost/MGUplon/public/imagens/semimagem.jpg'" width="100%">
                </v-flex>
                <v-flex sm6>
                  <dl>
                      <dt>Código OpenCart</dt>
                      <dd>{{ dados.codopencart }}</dd>

                      <dt>Site:</dt>
                      <dd>{{ dados.site ? 'Disponível no Site':'Não Disponível' }}</dd>

                      <dt v-if="dados.descricaosite">Descrição Site:</dt>
                      <dd v-if="dados.descricaosite" style="white-space: pre;">{{ dados.descricaosite }}</dd>
                  </dl>
                </v-flex>
              </v-layout>
            </v-container>
          </v-card-text>
        </v-card>
        <v-speed-dial v-model="fab.fab" :bottom="fab.bottom" :right="fab.right" direction="top" transition="scale">
          <v-btn slot="activator" class="blue darken-2" dark fab hover v-model="fab">
            <v-icon>keyboard_arrow_down</v-icon>
            <v-icon>keyboard_arrow_up</v-icon>
          </v-btn>
          <v-btn fab dark small class="red" @click.native.stop="deletar()" v-tooltip:left="{ html: 'Excluir'}">
            <v-icon>delete</v-icon>
          </v-btn>
          <v-btn v-if="dados.inativo" fab dark small class="orange" @click.native.stop="inativar()" v-tooltip:left="{ html: 'inativar'}">
            <v-icon>thumb_down</v-icon>
          </v-btn>
          <v-btn v-else fab dark small class="orange" @click.native.stop="ativar()" v-tooltip:left="{ html: 'inativar'}">
            <v-icon>thumb_up</v-icon>
          </v-btn>
          <v-btn fab dark small class="indigo" router :to="{ path: '/marca/' + dados.codmarca + '/imagem' }" v-tooltip:left="{ html: 'Imagem'}">
            <v-icon>add_a_photo</v-icon>
          </v-btn>
          <v-btn fab dark small class="green" router :to="{ path: '/marca/' + dados.codmarca + '/editar' }" v-tooltip:left="{ html: 'Editar'}">
            <v-icon>edit</v-icon>
          </v-btn>
        </v-speed-dial>
      </div>
      <!--
      <div fixed slot="rodape">
      </div>
      -->
  </mg-layout>
</template>

<script>
import MgLayout from '../../layout/MgLayout'

export default {
  name: 'hello',
  components: {
    MgLayout
  },
  data () {
    return {
      fab: {
        fab: false,
        right: true,
        bottom: true
      },
      dados: {}
    }
  },

  methods: {
    carregaDados: function (id) {
      var vm = this
      window.axios.get('marca/' + this.$route.params.id).then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    deletar: function (id) {
      var vm = this
      window.axios.delete('marca/' + this.$route.params.id).then(function (request) {
        vm.$router.push('/marca')
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  mounted () {
    this.carregaDados(this.$route.params.id)
  }
}
</script>

<style scoped>
  dt {
    font-size: 0.8em;
    color: grey;
  }
  dd {
    margin-bottom: 7px;
    font-weight: 300;
  }
  .speed-dial {
    position: fixed;
  }
  .btn--floating {
    position: relative;
  }
</style>
