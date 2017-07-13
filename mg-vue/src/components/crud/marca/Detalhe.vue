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

    <template slot="conteudo" v-if="!carregando">

      <v-container fluid grid-list-md class="grey lighten-4">
        <v-layout row wrap>

          <v-flex xs12 sm6 md3 v-if="dados.imagem">
            <v-card hover>
              <v-card-media :src="dados.imagem.url" height="200px">
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

          <!--
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
        -->

        <!--
          <v-flex xs12 sm6 md6>

              <v-card hover>
                <v-list three-line dense>
                  <v-subheader>ABAIXO DO MÍNIMO</v-subheader>
                  <template v-for="produto in dados.produtosAbaixoMinimo">
                    <v-list-tile avatar v-bind:key="produto.codproduto">
                      <v-list-tile-avatar>
                        <img v-if="produto.imagem" v-bind:src="produto.imagem"/>
                      </v-list-tile-avatar>
                      <v-list-tile-content>
                        <v-list-tile-title>
                          <span class="grey--text text--darken-1">
                            #{{ parseInt(produto.codproduto).toLocaleString('pt-BR', { minimumIntegerDigits: 6, useGrouping: false }) }}
                          </span>
                          {{ produto.produto }}
                          {{ produto.variacao }}
                          <span class="grey--text text--darken-1">
                            Referencia {{ produto.referencia }}
                          </span>
                       </v-list-tile-title>
                       <v-list-tile-sub-title>
                          Saldo de
                          <span class="red--text">
                            {{ parseFloat(produto.saldoquantidade).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }}
                            {{ produto.unidademedida }}
                          </span>
                          ({{ parseInt(produto.estoqueminimo).toLocaleString('pt-BR') }}<v-icon>arrow_downward</v-icon><v-icon>arrow_upward</v-icon>{{ parseInt(produto.estoquemaximo).toLocaleString('pt-BR') }})
                          /
                          R$ {{ parseFloat(produto.saldovalor).toLocaleString('pt-BR', { minimumFractionDigits:2, maximumFractionDigits: 2 }) }}
                          suficiente para
                          <span class="red--text">
                            {{ parseFloat(produto.dias).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }} dias
                          </span>

                        </v-list-tile-sub-title>
                       <v-list-tile-sub-title>
                         Venda R$ {{ parseFloat(produto.preco).toLocaleString('pt-BR', { maximumFractionDigits: 2, minimumFractionDigits: 2 }) }}
                         <template v-if="produto.quantidadeultimacompra">
                           | Comprado
                           {{ moment(produto.dataultimacompra).fromNow() }}
                           {{ parseFloat(produto.quantidadeultimacompra).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }}
                           {{ produto.unidademedida }}
                           por R$
                           {{ parseFloat(produto.custoultimacompra).toLocaleString('pt-BR', { maximumFractionDigits: 2, minimumFractionDigits: 2 }) }}
                         </template>
                       </v-list-tile-sub-title>
                      </v-list-tile-content>
                    </v-list-tile>
                    <v-divider inset></v-divider>
                  </template>
                </v-list>
              </v-card>

              <br>


          </v-flex>
        -->


        </v-layout>

        <v-subheader>ABAIXO DO MÍNIMO</v-subheader>

        <detalhe-produtos :produtos="dados.produtosAbaixoMinimo"></detalhe-produtos>

        <v-subheader>ACIMA DO MAXIMO</v-subheader>

        <v-layout row wrap>

          <template v-for="produto in dados.produtosAcimaMaximo">

              <v-flex lg3 md4 sm6 xs12 style="height:100%">
                <v-card hover >
                  <v-card-media class="" height="200px" :src="produto.imagem" v-if="produto.imagem">
                  </v-card-media>
                  <v-card-title>
                    <h3 class="headline mb-0" style="width: 100%">
                      {{produto.produto}}
                    </h3>
                    <span v-if="produto.variacao">
                      {{produto.variacao}}
                      -
                    </span>
                    <span class="grey--text text--darken-2">
                      R$ {{ parseFloat(produto.preco).toLocaleString('pt-BR', { maximumFractionDigits: 2, minimumFractionDigits: 2 }) }}
                    </span>
                  </v-card-title>
                  <v-card-text class="pt-0">
                    <small class="grey--text text--darken-2">

                      #{{ parseInt(produto.codproduto).toLocaleString('pt-BR', { minimumIntegerDigits: 6, useGrouping: false }) }}
                      Referencia {{ produto.referencia }}

                      <br>

                      Saldo:
                      <span class="red--text">
                        {{ parseFloat(produto.saldoquantidade).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }}
                        {{ produto.unidademedida }}
                      </span>

                      ({{ parseInt(produto.estoqueminimo).toLocaleString('pt-BR') }}<v-icon>arrow_downward</v-icon><v-icon>arrow_upward</v-icon>{{ parseInt(produto.estoquemaximo).toLocaleString('pt-BR') }})
                      <span v-if="produto.dias">
                        /
                        <span class="red--text">
                          {{ parseFloat(produto.dias).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }} dias
                        </span>
                      </span>

                      <br>

                      <span v-if="produto.saldovalor >0">
                        Valor do Estoque: R$ {{ parseFloat(produto.saldovalor).toLocaleString('pt-BR', { minimumFractionDigits:2, maximumFractionDigits: 2 }) }}
                        <br>
                      </span>


                      <span v-if="produto.quantidadeultimacompra">
                        Comprado
                        {{ moment(produto.dataultimacompra).fromNow() }}
                        {{ parseFloat(produto.quantidadeultimacompra).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }}
                        {{ produto.unidademedida }}
                        por R$
                        {{ parseFloat(produto.custoultimacompra).toLocaleString('pt-BR', { maximumFractionDigits: 2, minimumFractionDigits: 2 }) }}
                      </span>

                    </small>
                  </v-card-text>
                </v-card>
              </v-flex>
          </template>
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
import DetalheProdutos from './DetalheProdutos'

export default {
  name: 'hello',
  components: {
    MgLayout,
    MgAbcCategoria,
    DetalheProdutos
  },
  data () {
    return {
      fab: {
        fab: false,
        right: true,
        bottom: true
      },
      dados: {},
      carregando: true
    }
  },

  methods: {
    carregaDados: function (id) {
      var vm = this
      window.axios.get('marca/' + this.$route.params.id + '/details').then(function (request) {
        vm.dados = request.data
        vm.carregando = false
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
.sombra {
  text-shadow: 0 0 2px #FFF; /* horizontal-offset vertical-offset 'blur' colour */
  -moz-text-shadow: 0 0 2px #FFF;
  -webkit-text-shadow: 0 0 2px #FFF;
}
</style>
