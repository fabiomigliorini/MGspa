<template>
  <mg-layout>

    <template slot="titulo">
      {{ dados.marca }}
    </template>

    <template slot="botoes-menu-esquerda">
      <v-btn icon class="blue--text" router :to="{path: '/marca/' }">
        <v-icon>arrow_back</v-icon>
      </v-btn>
    </template>

    <template slot="conteudo">

      <v-container fluid grid-list-md class="grey lighten-4">
        <v-layout row wrap>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover>
              <v-card-media v-if="dados.imagem" :src="dados.imagem.url" height="200px">
              </v-card-media>
            </v-card>
          </v-flex>

          <v-flex xs12 sm6 md3>
            <v-card hover>
              <v-card-text>
                <dl>
                    <dt>#</dt>
                    <dd>#{{ parseInt(dados.codmarca).toLocaleString('pt-BR', { minimumIntegerDigits: 8, useGrouping: false }) }}</dd>

                    <dt># OpenCart</dt>
                    <dd>{{ dados.codopencart }}</dd>

                    <dt>Site:</dt>
                    <dd>{{ dados.site ? 'Disponível no Site':'Não Disponível' }}</dd>

                    <dt v-if="dados.descricaosite">Descrição Site:</dt>
                    <dd v-if="dados.descricaosite" style="white-space: pre;">{{ dados.descricaosite }}</dd>
                </dl>
              </v-card-text>
            </v-card>
          </v-flex>

          <v-flex xs12 sm4 md2>
            <v-card hover>
              <v-card-text>

                <dl>
                    <dt>Itens Acabando</dt>
                    <dd>
                      {{ dados.itensabaixominimo }} <v-icon>arrow_downward</v-icon>
                    </dd>

                    <dt>Itens Sobrando</dt>
                    <dd>
                      {{ dados.itensacimamaximo }}
                      <v-icon>arrow_upward</v-icon>
                    </dd>

                    <dt>Mínimo e Máximo para</dt>
                    <dd>
                      <v-icon>date_range</v-icon>
                      {{ dados.estoqueminimodias }} à
                      {{ dados.estoquemaximodias }} Dias
                    </dd>

                    <dt>Última Compra</dt>
                    <dd>
                      <v-icon>add_shopping_cart</v-icon>
                       {{ moment(dados.dataultimacompra).fromNow() }}
                    </dd>

                </dl>
              </v-card-text>
            </v-card>
          </v-flex>

          <v-flex xs12 sm4 md2>
            <v-card hover>
              <v-card-text>
                <dl>

                  <template v-if="dados.abcignorar">

                    <dt>Curva ABC</dt>
                    <dd>Ignorada</dd>

                  </template>
                  <template v-else>

                    <dt>Curva ABC</dt>
                    <dd><mg-abc-categoria :abccategoria="dados.abccategoria"></mg-abc-categoria></dd>

                    <dt>Posição</dt>
                    <dd>
                      <template v-if="dados.abcposicao">
                         {{ parseInt(dados.abcposicao).toLocaleString('pt-BR') }}&deg;
                      </template>
                      <template v-else>
                        &#8212;
                      </template>
                    </dd>

                  </template>

                  <dt>Participação Venda Anual</dt>
                  <dd>
                    {{ parseFloat(dados.vendaanopercentual).toLocaleString('pt-BR', {minimumFractionDigits: 4, maximumFractionDigits: 4}) }} %
                  </dd>

                  <dt>Venda Bimestre</dt>
                  <dd>{{ parseFloat(dados.vendabimestrevalor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</dd>

                  <dt>Venda Semestre</dt>
                  <dd>{{ parseFloat(dados.vendasemestrevalor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</dd>

                  <dt>Venda Ano</dt>
                  <dd>{{ parseFloat(dados.vendaanovalor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</dd>

                </dl>
              </v-card-text>
            </v-card>
          </v-flex>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover class="purple white--text">
              <v-container fluid grid-list-lg>
                <v-layout row>
                  <v-flex xs7>
                    <div>
                      <div class="headline">Halycon Days</div>
                      <div>Ellie Goulding</div>
                    </div>
                  </v-flex>
                  <v-flex xs5>
                     <v-card-media
                        :src="dados.imagem.url"
                        height="125px"
                        contain
                      ></v-card-media>
                  </v-flex>
                </v-layout>
              </v-container>
            </v-card>
          </v-flex>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover class="purple white--text">
              <v-container fluid grid-list-lg>
                <v-layout row>
                  <v-flex xs7>
                    <div>
                      <div class="headline">Halycon Days</div>
                      <div>Ellie Goulding</div>
                    </div>
                  </v-flex>
                  <v-flex xs5>
                     <v-card-media
                        :src="dados.imagem.url"
                        height="125px"
                        contain
                      ></v-card-media>
                  </v-flex>
                </v-layout>
              </v-container>
            </v-card>
          </v-flex>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover class="purple white--text">
              <v-container fluid grid-list-lg>
                <v-layout row>
                  <v-flex xs7>
                    <div>
                      <div class="headline">Halycon Days</div>
                      <div>Ellie Goulding</div>
                    </div>
                  </v-flex>
                  <v-flex xs5>
                     <v-card-media
                        :src="dados.imagem.url"
                        height="125px"
                        contain
                      ></v-card-media>
                  </v-flex>
                </v-layout>
              </v-container>
            </v-card>
          </v-flex>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover class="purple white--text">
              <v-container fluid grid-list-lg>
                <v-layout row>
                  <v-flex xs7>
                    <div>
                      <div class="headline">Halycon Days</div>
                      <div>Ellie Goulding</div>
                    </div>
                  </v-flex>
                  <v-flex xs5>
                     <v-card-media
                        :src="dados.imagem.url"
                        height="125px"
                        contain
                      ></v-card-media>
                  </v-flex>
                </v-layout>
              </v-container>
            </v-card>
          </v-flex>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover class="purple white--text">
              <v-container fluid grid-list-lg>
                <v-layout row>
                  <v-flex xs7>
                    <div>
                      <div class="headline">Halycon Days</div>
                      <div>Ellie Goulding</div>
                    </div>
                  </v-flex>
                  <v-flex xs5>
                     <v-card-media
                        :src="dados.imagem.url"
                        height="125px"
                        contain
                      ></v-card-media>
                  </v-flex>
                </v-layout>
              </v-container>
            </v-card>
          </v-flex>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover class="purple white--text">
              <v-container fluid grid-list-lg>
                <v-layout row>
                  <v-flex xs7>
                    <div>
                      <div class="headline">Halycon Days</div>
                      <div>Ellie Goulding</div>
                    </div>
                  </v-flex>
                  <v-flex xs5>
                     <v-card-media
                        :src="dados.imagem.url"
                        height="125px"
                        contain
                      ></v-card-media>
                  </v-flex>
                </v-layout>
              </v-container>
            </v-card>
          </v-flex>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover class="purple white--text">
              <v-container fluid grid-list-lg>
                <v-layout row>
                  <v-flex xs7>
                    <div>
                      <div class="headline">Halycon Days</div>
                      <div>Ellie Goulding</div>
                    </div>
                  </v-flex>
                  <v-flex xs5>
                     <v-card-media
                        :src="dados.imagem.url"
                        height="125px"
                        contain
                      ></v-card-media>
                  </v-flex>
                </v-layout>
              </v-container>
            </v-card>
          </v-flex>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover class="purple white--text">
              <v-container fluid grid-list-lg>
                <v-layout row>
                  <v-flex xs7>
                    <div>
                      <div class="headline">Halycon Days</div>
                      <div>Ellie Goulding</div>
                    </div>
                  </v-flex>
                  <v-flex xs5>
                     <v-card-media
                        :src="dados.imagem.url"
                        height="125px"
                        contain
                      ></v-card-media>
                  </v-flex>
                </v-layout>
              </v-container>
            </v-card>
          </v-flex>


        </v-layout>
      </v-container>

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
    </template>


  </mg-layout>
</template>

<script>
import MgLayout from '../../layout/MgLayout'
import MgAbcCategoria from '../../layout/MgAbcCategoria'

export default {
  name: 'hello',
  components: {
    MgLayout,
    MgAbcCategoria
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
      window.axios.get('marca/' + this.$route.params.id + '/details').then(function (request) {
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
</style>
