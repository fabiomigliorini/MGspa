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
      <v-fab error router :to="{ path: '/marca/' + dados.codmarca + '/imagem' }" style="bottom:255px">
        <v-icon light>add_a_photo</v-icon>
      </v-fab>
      <v-fab error router :to="{ path: '/marca/' + dados.codmarca + '/editar' }" style="bottom:190px">
        <v-icon light>mode_edit</v-icon>
      </v-fab>
      <v-fab error @click.native.stop="deletar()">
        <v-icon light>delete</v-icon>
      </v-fab>

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
      dados: {}
    }
  },
  methods: {
    carregaDados: function (id) {
      var vm = this
      window.axios.get('marcas/' + this.$route.params.id).then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error)
      })
    },
    deletar: function (id) {
      var vm = this
      window.axios.delete('marcas/' + this.$route.params.id).then(function (request) {
        vm.$router.push('/marca')
      }).catch(function (error) {
        console.log(error)
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


</style>
