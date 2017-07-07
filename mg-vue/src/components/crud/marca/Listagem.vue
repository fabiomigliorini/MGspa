<template>
  <mg-layout menu>

    <div slot="titulo">
      Marcas
    </div>

    <div slot="menu">

      <v-flex xs12 class="container pt-3 pb-0 mb-0 mt-0">

        <v-text-field
         class="pt-0 pb-0 mb-0 mt-4"
          name="filtro"
          append-icon="search"
          label="Descrição"
          id="filtro"
          v-model="filtro.marca"
          @change.native.stop="pesquisar()"
        ></v-text-field>
      </v-flex>

      <v-list dense>

        <v-subheader class="mt-0 grey--text text--darken-1">ORDENAR POR</v-subheader>

        <v-list-tile @click.native="ordena('abcposicao')">
          <v-list-tile-action>
            <v-icon :class="(filtro.sort=='abcposicao')?'blue--text':''">trending_up</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.sort=='abcposicao')?'blue--text':''">Vendas</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="ordena('marca')">
          <v-list-tile-action>
            <v-icon :class="(filtro.sort=='marca')?'blue--text':''">sort_by_alpha</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.sort=='marca')?'blue--text':''">Descrição</v-list-tile-title>
        </v-list-tile>

        <v-subheader class="mt-0 grey--text text--darken-1">FILTRAR</v-subheader>

        <v-list-tile @click.native="sobrando()">
          <v-list-tile-action>
            <v-icon :class="(filtro.sobrando==true)?'blue--text':''">arrow_upward</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.sobrando==true)?'blue--text':''">Estoque Sobrando</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="faltando()">
          <v-list-tile-action>
            <v-icon :class="(filtro.faltando==true)?'blue--text':''">arrow_downward</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.faltando==true)?'blue--text':''">Estoque Faltando</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="abccategoria()">
          <v-list-tile-action>
            <v-icon :class="filtro.abccategoria?'blue--text':''">star</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="filtro.abccategoria?'blue--text':''">
            <span v-if="filtro.abccategoria">
            {{ 4 - filtro.abccategoria }}
            </span>
            Estrelas
          </v-list-tile-title>
        </v-list-tile>

        <v-subheader class="mt-0 grey--text text--darken-1">ATIVOS</v-subheader>

        <v-list-tile @click.native="inativo(1)">
          <v-list-tile-action>
            <v-icon :class="(filtro.inativo==1)?'blue--text':''">thumb_up</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.inativo==1)?'blue--text':''">Ativos</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="inativo(2)">
          <v-list-tile-action>
            <v-icon :class="(filtro.inativo==2)?'blue--text':''">thumb_down</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.inativo==2)?'blue--text':''">Inativos</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="inativo(9)">
          <v-list-tile-action>
            <v-icon :class="(filtro.inativo==9)?'blue--text':''">thumbs_up_down</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.inativo==9)?'blue--text':''">Ativos e Inativos</v-list-tile-title>
        </v-list-tile>

      </v-list>


    </div>

    <div slot="conteudo">
       <v-list two-line>
        <template v-for="item in marca">
          <v-list-tile avatar router :to="{path: '/marca/' + item.codmarca }" v-bind:key="item.codmarca">
            <v-list-tile-avatar>
              <!-- <img src="http://localhost/MGUplon/public/imagens/{{ item.codimagem }}.jpg"> -->
              <img v-if="item.codimagem" :src="'http://localhost/MGUplon/public/imagens/'+ item.codimagem + '.jpg'">
              <img v-else :src="'http://localhost/MGUplon/public/imagens/semimagem.jpg'">
            </v-list-tile-avatar>
            <v-list-tile-content>
              <v-list-tile-title>
                {{ item.marca }}
              </v-list-tile-title>
              <v-list-tile-sub-title>
                #{{ item.codmarca }}
              </v-list-tile-sub-title>
            </v-list-tile-content>

            <v-list-tile-content class="hidden-sm-and-down">
              <v-list-tile-sub-title>
                <span v-if="item.itensabaixominimo > 0">
                  {{ item.itensabaixominimo }} abaixo do mínimo /
                </span>
                <span v-if="item.itensacimamaximo > 0">
                  {{ item.itensacimamaximo }} acima do máximo
                </span>
              </v-list-tile-sub-title>
              <v-list-tile-sub-title>
                <span v-if="item.dataultimacompra">
                   Comprado
                   {{ moment(item.dataultimacompra).fromNow() }}
                 </span>
              </v-list-tile-sub-title>
            </v-list-tile-content>

            <v-list-tile-content class="hidden-sm-and-down">
              <v-list-tile-sub-title>
                Estoque de
                {{ item.estoqueminimodias }} à
                {{ item.estoquemaximodias }} Dias
              </v-list-tile-sub-title>
            </v-list-tile-content>

            <v-list-tile-content>
              <v-list-tile-title class="text-xs-right">
                <mg-abc-categoria :abccategoria="item.abccategoria"></mg-abc-categoria>
              </v-list-tile-title>
              <v-list-tile-sub-title class="text-xs-right">
                # {{ item.abcposicao }}
              </v-list-tile-sub-title>
            </v-list-tile-content>
          </v-list-tile>
          <v-divider></v-divider>
      </template>
    </v-list>

    <div class="container" v-if="!fim">
      <v-btn @click.native.stop="mais()" block info :loading="carregando">
        Mais
        <v-icon right>expand_more</v-icon>
      </v-btn>
    </div>

    <v-btn router :to="{path: '/marca/nova'}" class="red white--text" light fixed bottom right fab>
      <v-icon>add</v-icon>
    </v-btn>

  </div>

  <!--
  <div fixed slot="rodape">
  </div>
  -->

</mg-layout>
</template>

<script>
import MgLayout from '../../layout/MgLayout'
import MgAbcCategoria from '../../layout/MgAbcCategoria'

export default {
  name: 'hello',
  components: {
    MgLayout, MgAbcCategoria
  },
  data () {
    return {
      marca: [],
      pagina: 1,
      filtro: { }, // Vem do Store
      fim: false,
      tab: 0,
      carregando: false
    }
  },
  methods: {
    carregaListagem () {
      this.$store.commit('filtro/marca', this.filtro)
      var vm = this
      var params = this.filtro
      params.page = this.pagina
      this.carregando = true
      window.axios.get('marca', {params}).then(response => {
        vm.marca = vm.marca.concat(response.data.data)
        this.fim = (response.data.current_page >= response.data.last_page)
        this.carregando = false
      })
    },
    mais () {
      this.pagina++
      this.carregaListagem()
    },
    pesquisar () {
      this.pagina = 1
      this.marca = []
      this.fim = false
      this.carregaListagem()
    },
    ordena (campo) {
      this.filtro.sort = campo
      this.pesquisar()
    },
    inativo (valor) {
      this.filtro.inativo = valor
      this.pesquisar()
    },
    sobrando () {
      this.filtro.sobrando = !this.filtro.sobrando
      this.pesquisar()
    },
    faltando () {
      this.filtro.faltando = !this.filtro.faltando
      this.pesquisar()
    },
    abccategoria () {
      if (!this.filtro.abccategoria) {
        this.filtro.abccategoria = 0
      }
      this.filtro.abccategoria++
      if (this.filtro.abccategoria > 4) {
        this.filtro.abccategoria = 0
      }
      this.pesquisar()
    }
  },
  mounted () {
    this.filtro = this.$store.getters['filtro/marca']
    this.carregaListagem()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
