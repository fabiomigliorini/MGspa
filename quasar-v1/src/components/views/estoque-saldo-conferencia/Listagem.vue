<template>
  <mg-layout drawer back-path="/estoque-saldo-conferencia/">

    <template slot="title" v-if="carregado">
      {{data.local.estoquelocal }} / {{ data.marca.marca }}
    </template>

    <div slot="drawer">
      <q-item-label class="text-subtitle1 text-grey-7 q-pa-md">Filtros</q-item-label>

      <!-- FILTRO ATIVOS -->
        <q-item dense>
        <q-item-section avatar>
          <q-icon name="thumb_up"/>
        </q-item-section>
        <q-item-section class="text-subtitle1">
          Ativos
        </q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.inativo" :val="0" />
        </q-item-section>
      </q-item>

      <!-- Filtra Inativos -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="thumb_down"/>
        </q-item-section>
        <q-item-section class="text-subtitle1">
          Inativos
        </q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.inativo" :val="1" />
        </q-item-section>
      </q-item>

      <!-- Filtra Ativos e Inativos -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="thumbs_up_down"/>
        </q-item-section>
        <q-item-section class="text-subtitle1">
          Todos
        </q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.inativo" :val="9" />
        </q-item-section>
      </q-item>
      <q-separator />

      <!-- Filtra por data de corte -->
      <q-item-label class="text-subtitle1 text-grey-7 q-pa-md">Data de Corte Conferência</q-item-label>
      <q-item dense>
        <q-item-section>
          <q-input v-model="filter.dataCorte" mask="date" :rules="['date']">
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy ref="qDateProxy" transition-show="scale" transition-hide="scale">
                  <q-date v-model="date" @input="() => $refs.qDateProxy.hide()" />
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </q-item-section>
      </q-item>
    </div>
    <div slot="content">
      <q-tabs v-model="filter.conferidos" dense class="bg-primary text-white" indicator-color="positive">
        <q-tab name="conferir" icon="close" label="Para Conferir" default/>
        <q-tab name="conferidos" icon="check" label="Já Conferido"/>
      </q-tabs>

      <!-- Listagem de Produtos -->
      <template v-if="carregado">

        <!-- Infinite scroll -->
        <q-infinite-scroll :handler="loadMore" ref="infiniteScroll" v-if="data.produtos.length > 0">

          <!-- Funcao swipe para zerar o produto -->
          <div v-touch-swipe.horizontal="swipeProduto(produto)" >

            <q-list separator >
              <q-item @click.native="buscaProduto(produto)" v-for="produto in data.produtos" :key="produto.id">
                <q-item-section thumbnail>
                  <img :src="produto.imagem" v-if="produto.imagem"/>
                  <img src="assets/no-image-16-9.png" v-else/>
                </q-item-section>

                <q-item-section>
                  <q-item-label>
                    {{ produto.produto }}
                    <template v-if="produto.variacao">| {{produto.variacao}}</template>
                  </q-item-label>
                  <q-item-label caption>
                    <q-chip detail square dense icon="vpn_key">
                      {{ numeral(produto.codproduto).format('000000') }}
                    </q-chip>
                    <q-chip text-color="white" detail square dense icon="widgets" :color="(produto.saldoquantidade>0)?'green':(produto.saldoquantidade<0)?'red':'grey'">
                      {{ numeral(produto.saldoquantidade).format('0,0') }}
                    </q-chip>
                    <q-chip detail square dense icon="thumb_down" v-if="produto.inativo" color="red" text-color="white">
                      {{ moment(produto.inativo).fromNow() }}
                    </q-chip>
                  </q-item-label>

                  <q-item-label class="lt-sm text-grey-7" v-if="produto.ultimaconferencia">
                    <q-icon name="assignment_turned_in"/>
                    {{ moment(produto.ultimaconferencia).fromNow() }}
                  </q-item-label>
                </q-item-section>

                <q-item-section side v-if="produto.ultimaconferencia" class="gt-xs">
                  <q-item-label>
                    <q-icon name="assignment_turned_in"/>
                    {{ moment(produto.ultimaconferencia).fromNow() }}
                  </q-item-label>
                </q-item-section>

              </q-item>
            </q-list>


          </div>

        </q-infinite-scroll>

        <!-- se não houver produtos para mostrar -->
        <q-item v-else>
          <q-item-section>
            <h3 v-if="filter.conferidos == 'conferidos' " class="text-red text-center">
              Nenhum produto conferido! <br />
              <q-icon name="thumb down" size="25vh"/>
            </h3>
            <h3 v-else class="text-green text-center">
              Nenhum produto para conferir! <br />
              <q-icon name="thumb up" size="25vh"/>
            </h3>
          </q-item-section>
        </q-item>

      </template>

      <!-- BOTAO PARA PROCURAR PRODUTO POR CODIGO DE BARRAS -->
      <q-page-sticky corner="bottom-right" :offset="[25, 25]">
        <q-btn fab color="primary" icon="add" @click.native="modalBuscaPorBarras = true" />
      </q-page-sticky>

      <!-- MODAL DE DETALHES DO PRODUTO -->
      <q-dialog v-model="modalProduto" persistent maximized transition-show="slide-up" transition-hide="slide-down">
        <q-layout view="Lhh lpR fff" container class="bg-white">

          <q-page-container v-if="produtoCarregado">
            <q-page padding>

              <div class="row justify-center">

                <!-- COLUNA 1 -->
                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                  <q-card class="my-card no-shadow">
                   <!-- <img :src="produtoImagem" v-if="produtoImagem">
                    <img src="assets/no-image-16-9.png" v-else>-->
                    <img src="assets/no-image-16-9.png">

                    <q-card-section>
                      <q-item>
                        <q-item-section>
                          <q-item-label class="text-subtitle1">
                            {{produto.produto.produto}} {{produto.variacao.variacao}}
                          </q-item-label>
                          <q-item-label>
                            <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.produto.inativo">
                              Produto Inativo {{ moment(produto.produto.inativo).fromNow() }}
                            </q-chip>
                            <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.variacao.inativo">
                              Variação Inativa {{ moment(produto.variacao.inativo).fromNow() }}
                            </q-chip>
                            <q-chip text-color="white" square dense icon="thumb_down" color="red" v-if="produto.variacao.descontinuado">
                              Descontinuado {{ moment(produto.variacao.descontinuado).fromNow() }}
                            </q-chip>
                          </q-item-label>
                        </q-item-section>
                      </q-item>
                    </q-card-section>
                  </q-card>
                </div>

                <!-- COLUNA 2 -->
                <div class="xs-12 col-sm-6 col-md-4 col-lg-3">
                  <div class="row">

                    <div class="col-12">
                      <q-item>

                        <q-item-section avatar>
                          <q-icon color="primary" name="widgets" />
                        </q-item-section>

                        <q-item-section>
                          <q-item-label>
                            <b>{{ numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0') }}</b> {{ produto.produto.siglaunidademedida }} em estoque
                          </q-item-label>

                          <q-item-label caption>
                            Sugerido entre {{ numeral(parseFloat(produto.variacao.estoqueminimo)).format('0,0') }} e {{ numeral(parseFloat(produto.variacao.estoquemaximo)).format('0,0') }} {{ produto.produto.siglaunidademedida }}.
                          </q-item-label>

                        </q-item-section>
                      </q-item>

                      <q-item>
                        <q-item-section avatar>
                          <q-icon color="primary" name="attach_money" />
                        </q-item-section>

                        <q-item-section>
                          <q-item-tile label>
                            Vendido à R$
                            <b>{{ numeral(parseFloat(produto.produto.preco)).format('0,0.00') }}</b>
                          </q-item-tile>

                          <q-item-tile sublabel>
                            Cada {{ produto.produto.unidademedida }} custa R$
                            <b>{{ numeral(parseFloat(produto.saldoatual.custo)).format('0,0.00') }}</b>.
                          </q-item-tile>

                        </q-item-section>
                      </q-item>
                    </div>

                    <div class="col-12">

                      <!--ESTOQUE LOCAL E LOCALIZACAO PRATELEIRA-->
                      <q-item>

                        <q-item-section avatar>
                          <q-icon color="green" name="place"/>
                        </q-item-section>

                        <q-item-section>
                          <q-item-label>
                            {{ produto.localizacao.estoquelocal }}
                          </q-item-label>

                          <q-item-label caption v-if="produto.localizacao.corredor">
                            Corredor&nbsp{{ numeral(produto.localizacao.corredor).format('00') }} Prateleira&nbsp{{ numeral(produto.localizacao.prateleira).format('00') }} Coluna&nbsp{{ numeral(produto.localizacao.coluna).format('00') }} Bloco&nbsp{{ numeral(produto.localizacao.bloco).format('00')
                            }}
                          </q-item-label>

                        </q-item-section>
                      </q-item>

                      <!-- SE DATA VENCIMENTO-->
                      <q-item v-if="produto.localizacao.vencimento">
                        <q-item-section avatar>
                          <q-icon color="red" name="access alarm"/>
                        </q-item-section>

                        <q-item-section>
                          <q-item-label v-if="moment(produto.localizacao.vencimento).isAfter()">
                            Vence {{ moment(produto.localizacao.vencimento).fromNow() }}
                          </q-item-label>

                          <q-item-label v-else color="red">
                            Vencido {{ moment(produto.localizacao.vencimento).fromNow() }}
                          </q-item-label>

                          <q-item-label caption>
                            {{ moment(produto.localizacao.vencimento).format('dddd, LL') }}
                          </q-item-label>

                        </q-item-section>
                      </q-item>

                    </div>

                    <div class="col-12">

                      <!--CODIGO DO PRODUTO-->
                      <q-item>
                        <q-item-section avatar>
                          <q-icon color="black" name="vpn_key" />
                        </q-item-section>

                        <q-item-section>
                          {{ numeral(produto.produto.codproduto).format('000000') }}
                        </q-item-section>
                      </q-item>

                      <!--PRODUTO VARIACAO-->
                      <q-item>
                        <q-item-section avatar>
                          <q-icon color="black" name="local_offer" />
                        </q-item-section>

                        <q-item-section>
                          <template v-if="produto.variacao.referencia">
                            {{ produto.variacao.referencia }}
                          </template>
                          <template v-else-if="produto.produto.referencia">
                            {{ produto.produto.referencia }}
                          </template>
                        </q-item-section>
                      </q-item>

                      <!--PRODUTO BARRAS-->
                      <q-item>
                        <q-item-section avatar>
                          <q-icon name="view_column"/>
                        </q-item-section>

                        <q-item-section>
                          <q-item-label caption v-for="barra in produto.barras" :key="barra.barras">
                            {{ barra.barras }} {{ barra.siglaunidademedida }}
                            <template v-if="barra.quantidade > 1">
                              C/{{ parseInt(barra.quantidade) }}
                            </template>
                          </q-item-label>
                        </q-item-section>
                      </q-item>

                    </div>
                  </div>
                </div>

                <div class="col-xs-12 col-sm-12 col-md-5 col-lg-3" v-if="produto.conferencias.length > 0">

                  <q-timeline color="secondary">
                    <q-timeline-entry class="text-h6 text-grey-7">
                      Últimas conferências
                    </q-timeline-entry>

                    <transition-group enter-active-class="animated fadeInDown" leave-active-class="animated fadeOutUp">
                      <q-timeline-entry v-for="(conf, iconf) in produto.conferencias"
                                        :key="conf.codestoquesaldoconferencia"
                                        :title="numeral(parseFloat(conf.quantidadeinformada)).format('0,0') + ' '
                                                + produto.produto.siglaunidademedida + ' custando R$ '
                                                + numeral(parseFloat(conf.customedioinformado)).format('0,0.00')
                                                + ' cada ' + produto.produto.unidademedida"
                                        :subtitle="conf.usuario + ' ' + moment(conf.criacao).fromNow()"
                                        side="left"
                      >
                        <div>
                          <small class="text-faded">
                            O saldo do sistema no momento da conferência era de
                            <b>{{ numeral(parseFloat(conf.quantidadesistema)).format('0,0') }}</b> {{produto.produto.siglaunidademedida}}
                            custando R$ <b>{{numeral(parseFloat(conf.customediosistema)).format('0,0.00')}}</b>
                            cada {{ produto.produto.unidademedida }}.
                          </small>
                          <small class="text-faded" v-if="conf.observacoes">
                            <br />
                            {{conf.observacoes}}
                          </small>
                          <q-btn round flat dense icon="thumb_down" color="red" @click.native="inativarConferencia(conf.codestoquesaldoconferencia)" />

                        </div>
                      </q-timeline-entry>
                    </transition-group>
                  </q-timeline>
                </div>
              </div>

              <q-page-sticky position="bottom-right" :offset="[25, 80]">
                <q-btn round color="red" icon="arrow_back" @click="modalProduto = false" v-close-popup/>
                <q-tooltip anchor="center left" self="center right">
                  Voltar
                </q-tooltip>
              </q-page-sticky>

              <q-page-sticky position="bottom-right" :offset="[25, 25]">
                <q-btn round color="primary" icon="add" @click="modalConferencia = true" v-close-popup/>
                <q-tooltip anchor="center left" self="center right">
                  Fazer Conferência
                </q-tooltip>
              </q-page-sticky>

            </q-page>
          </q-page-container>
        </q-layout>
      </q-dialog>

      <!-- MODAL DE CONFERÊNCIA DO PRODUTO -->
      <q-dialog maximized v-model="modalConferencia" v-if="produtoCarregado" @hide="focoCampoBarras()" @show="focoCampoQuantidadeInformada()">
          <div class="row justify-center">
            <div class="col-xs-12 col-sm-6 col-md-5 col-lg-4">
              <q-card-title>

                <!-- Nome do Produto e Variação -->
                <q-item-tile>
                  <!-- Código do Produto -->
                  <q-chip detail square dense icon="vpn_key">
                    {{ numeral(produto.produto.codproduto).format('000000') }}
                  </q-chip>

                  {{produto.produto.produto}} {{produto.variacao.variacao}}
                  <template v-if="produto.produto.variacao">- {{produto.produto.variacao}}</template>

                  <!-- Ativo / Inativo / Descontinuado -->
                  <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.produto.inativo">
                    Produto Inativo {{ moment(produto.produto.inativo).fromNow() }}
                  </q-chip>
                  <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.variacao.inativo">
                    Variação Inativa {{ moment(produto.variacao.inativo).fromNow() }}
                  </q-chip>
                  <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.variacao.descontinuado">
                    Descontinuado {{ moment(produto.variacao.descontinuado).fromNow() }}
                  </q-chip>
                </q-item-tile>
              </q-card-title>

              <q-card-main style="max-width: 85vw">
                <form>

                    <!-- QUANTIDADE -->
                    <q-item dense>
                      <q-item-side icon="widgets" color="blue"/>
                      <q-item-main>
                        <q-field label="Quantidade" :helper="'Quantidade Atual: ' + numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0')">
                          <q-input required type="number" v-model="conferencia.quantidade" :decimals="3" autofocus align="right" ref="campoQuantidadeInformada" clearable @keydown.enter="salvaConferencia()"/>
                        </q-field>
                      </q-item-main>
                    </q-item>

                    <!-- VENCIMENTO -->
                    <q-item dense>
                      <q-item-side icon="access alarm" color="red"/>
                      <q-item-main>
                        <q-field label="Vencimento">
                          <q-input type="date" v-model="conferencia.vencimento" align="center" clearable />
                        </q-field>
                      </q-item-main>
                    </q-item>

                    <!-- BOTAO LOCALIZACAO -->
                    <q-item dense>
                      <q-item-side icon="place" color="green"/>
                      <q-item-main>
                        <q-field label="Corr/Prat/Col/Bloco">
                          <div class="row gutter-xs">
                            <div class="col">
                              <q-input v-model="conferencia.corredor" type="number" :decimals="0" min="0" max="99" align="center" clearable @keydown.enter="salvaConferencia()"/>
                            </div>
                            <div class="col">
                              <q-input v-model="conferencia.prateleira" type="number" :decimals="0" min="0" max="99" align="center" clearable @keydown.enter="salvaConferencia()"/>
                            </div>
                            <div class="col">
                              <q-input v-model="conferencia.coluna" type="number" :decimals="0" min="0" max="99" align="center" clearable @keydown.enter="salvaConferencia()"/>
                            </div>
                            <div class="col">
                              <q-input v-model="conferencia.bloco" type="number" :decimals="0" min="0" max="99" align="center" clearable @keydown.enter="salvaConferencia()"/>
                            </div>
                          </div>
                        </q-field>
                      </q-item-main>
                    </q-item>

                    <!-- CUSTO -->
                    <q-item dense>
                      <q-item-side icon="attach_money" color="blue"/>
                      <q-item-main>
                        <q-field label="Custo" :helper="'O custo atual é R$ ' + numeral(parseFloat(produto.saldoatual.custo)).format('0,0.00')">
                          <q-input required type="number" v-model="conferencia.customedio" :decimals="6" align="right" clearable  @keydown.enter="salvaConferencia()"  ref="campoCustoMedioInformado"/>
                        </q-field>
                      </q-item-main>
                    </q-item>

                    <!-- OBSERVACOES -->
                    <q-item dense>
                      <q-item-side icon="description" color="black"/>
                      <q-item-main>
                        <q-field label="Observações">
                          <q-input type="textarea" v-model="conferencia.observacoes" clearable />
                        </q-field>
                      </q-item-main>
                    </q-item>
                  </form>

                  <!-- BOTAO FECHAR -->
                  <q-page-sticky position="bottom-right" :offset="[25, 80]">
                    <q-btn round color="red" icon="arrow_back" @click="modalConferencia = false" />
                  </q-page-sticky>
                  <!-- BOTAO CONFIRMAR -->
                  <q-page-sticky position="bottom-right" :offset="[25, 25]">
                    <q-btn round color="primary" icon="done" @click="salvaConferencia()" />
                  </q-page-sticky>

                </q-card-main>

              </div>
            </div>
      </q-dialog>

      <!-- MODAL DE BUSCA POR BARRAS -->
      <q-dialog maximized v-model="modalBuscaPorBarras" @show="focoCampoBarras()">
        <div class="row justify-center " style="padding-top: 20vh">
          <div class="col-xs-10 col-sm-6 col-md-5 col-lg-4 col-xl-3 gutter-sx">
            <form @submit.prevent="buscaProduto()">
              <q-item>
                <q-item-side icon="view column" color="black"/>
                <q-item-main>
                  <q-field >
                    <q-input clearable v-model="buscaPorBarras" float-label="Informe o código de barras" ref="campoBarras" align="center" />
                  </q-field>
                </q-item-main>
              </q-item>
            </form>
            <q-page-sticky position="bottom-right" :offset="[25, 80]">
              <q-btn round color="red" icon="arrow_back" @click="modalBuscaPorBarras = false" />
            </q-page-sticky>
            <q-page-sticky position="bottom-right" :offset="[25, 25]">
              <q-btn round color="primary" icon="done" @click="buscaProduto()" />
            </q-page-sticky>
          </div>
        </div>
      </q-dialog>

    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'estoque-saldo-conferencia-listagem',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data() {
    return {
      date: this.moment().startOf('day').subtract(15, 'days').format('YYYY-MM-DD'),
      swipe: false,
      page: 1,
      filter: {
        codestoquelocal: null,
        codmarca: null,
        fiscal: 1,
        data: null,
        inativo: 0,
        conferidos: 'conferir',
        dataCorte: null
      },
      erros: {
        quantidade: null,
        customedio: null
      },
      buscaPorBarras: null,
      produto: {},
      data: {},
      conferencia: {
        quantidade: null,
        customedio: null,
        vencimento: null,
        observacoes: null,
        corredor: null,
        prateleira: null,
        coluna: null,
        bloco: null

      },
      carregado: false,
      codigoproduto: null,
      produtoImagem: null,
      modalProduto: false,
      modalConferencia: false,
      modalBuscaPorBarras: false,
      produtoCarregado: false,
      conferenciaSalva: false
    }
  },
  watch: {

    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {
        this.page = 1;
        this.buscaListagem(false, null)
      },
      deep: true
    }

  },
  methods: {


    // Chama API para zerar saldo do estoque do produto
    zerarProduto (produto) {

      let vm = this;

      let params = {
        codprodutovariacao: produto.codprodutovariacao,
        codestoquelocal: vm.filter.codestoquelocal,
        fiscal: parseInt(vm.filter.fiscal),
        data: vm.filter.data
      };

      vm.$axios.post('estoque-saldo-conferencia/zerar-produto', params).then(function(request) {
        vm.parseProduto(request.data);
        vm.$q.notify({
          message: 'Saldo do estoque zerado!',
          type: 'positive',
        });
        vm.swipe = false
      }).catch(function(error) {
        console.log(error);
        vm.swipe = false
      })

    },

    // confirma se tem certeza que eh pra zerar saldo do estoque do produto
    swipeProduto (produto) {
      let vm = this;

      return function (props) {

        // se nao estiver na aba de itens pra conferir, cai fora
        if (vm.filter.conferidos != 'conferir') {
          return
        }

        // variavel de controle pra saber se esta deslizando pra direita
        // que eh verificada no click pra nao abrir o modalProduto
        vm.swipe = true;

        let message = 'Deseja zerar estoque do produto ' + produto.produto;

        if (produto.variacao) {
          message += ' | ' + produto.variacao
        }

        message += '?';

        vm.$q.dialog({
          title: 'Zerar o estoque',
          message: message,
          ok: 'Zerar',
          cancel: 'Cancelar'
        }).onOk(() => {
          vm.zerarProduto(produto)
        }).catch(function(error) {
          vm.swipe = false
        })
      }

    },

    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++;
      this.buscaListagem(true, done)
    },

    salvaConferencia: function() {
      let vm = this;

      if (vm.conferencia.quantidade == null) {
        vm.$q.notify({
          message: 'Quantidade deve ser preenchida!',
          type: 'negative',
        });
        // jogar foco na quantidade
        vm.focoCampoQuantidadeInformada();
        return
      }

      if (vm.conferencia.customedio == null) {
        vm.$q.notify({
          message: 'Custo deve ser preenchido!',
          type: 'negative',
        });
        // jogar foco na quantidade
        vm.focoCampoCustoMedioInformado();
        return
      }

      let params = {
        codprodutovariacao: vm.produto.variacao.codprodutovariacao,
        codestoquelocal: vm.filter.codestoquelocal,
        fiscal: parseInt(vm.filter.fiscal),
        quantidadeinformada: vm.conferencia.quantidade,
        customedioinformado: vm.conferencia.customedio,
        data: vm.filter.data,
        observacoes: vm.conferencia.observacoes,
        vencimento: vm.conferencia.vencimento,
        corredor: vm.conferencia.corredor,
        prateleira: vm.conferencia.prateleira,
        coluna: vm.conferencia.coluna,
        bloco: vm.conferencia.bloco
      };
      vm.$axios.post('estoque-saldo-conferencia', params).then(function(request) {
        vm.modalConferencia = false;
        vm.conferenciaSalva = true;
        vm.parseProduto(request.data);
        vm.$q.notify({
          message: 'Conferência realizada',
          type: 'positive',
        })
      }).catch(function(error) {
        console.log(error)
      })
    },

    inativarConferencia: function(codestoquesaldoconferencia) {
      let vm = this;
      vm.$q.dialog({
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?',
        ok: 'Inativar',
        cancel: 'Cancelar'
      }).onOk(() => {
        vm.$axios.post('estoque-saldo-conferencia/' + codestoquesaldoconferencia + '/inativo').then(function(request) {
          vm.parseProduto(request.data);
          vm.$q.notify({
            message: 'Conferência Inativada',
            type: 'warning',
          })
        }).catch(function(error) {
          console.log(error);
          vm.erros = error.response.data.erros
        })
      })
    },

    buscaListagem: function(concat, done) {
      // inicializa variaveis
      let vm = this;

      // se for primeira pagina, marca como dados nao carregados ainda
      if (this.page == 1) {
        vm.carregado = false
      }

      // Monta Parametros da API
      let params = {
        codestoquelocal: vm.filter.codestoquelocal,
        codmarca: vm.filter.codmarca,
        fiscal: vm.filter.fiscal,
        inativo: vm.filter.inativo,
        dataCorte: vm.filter.dataCorte,
        conferidos: (vm.filter.conferidos=='conferidos')?1:0,
        page: vm.page
      };

      vm.$axios.get('estoque-saldo-conferencia/busca-listagem', {
        params
      }).then(function(request) {

        // Se for para concatenar, senao inicializa
        if (vm.page == 1) {
          vm.data = request.data
        }
        else {
          vm.data.produtos = vm.data.produtos.concat(request.data.produtos)
        }
        vm.carregado = true;

        // Desativa Scroll Infinito se chegou no fim
        if (vm.$refs.infiniteScroll) {
          if (request.data.produtos.length === 0) {
            vm.$refs.infiniteScroll.stop()
          }
          else {
            vm.$refs.infiniteScroll.resume()
          }
        }

        // Executa done do scroll infinito
        if (done) {
          done()
        }

      }).catch(function(error) {
        console.log(error)
      })
    },

    parseProduto: function(data) {
      // armazena os dados retornados pela api como variavel local
      this.produto = data;

      // copia os dados do produto como default no preenchimento da conferência
      this.conferencia.vencimento = this.produto.localizacao.vencimento;
      if (this.conferencia.vencimento) {
        this.conferencia.vencimento = this.moment(this.conferencia.vencimento).format('YYYY-MM-DD')
      }
      //this.conferencia.quantidade = this.produto.saldoatual.quantidade
      this.conferencia.quantidade = null;
      this.conferencia.customedio = this.produto.saldoatual.custo;
      this.conferencia.corredor = this.produto.localizacao.corredor;
      this.conferencia.prateleira = this.produto.localizacao.prateleira;
      this.conferencia.coluna = this.produto.localizacao.coluna;
      this.conferencia.bloco = this.produto.localizacao.bloco;
      this.conferencia.observacoes = null;

      // procura registro na listagem de produtos
      var cod = this.produto.variacao.codprodutovariacao;
      var i = this.data.produtos.findIndex(k => k.codprodutovariacao==cod);

      // se nao estiver no array
      if (i == -1) {

        // se for um novo item conferido adiciona
        if (this.filter.conferidos == 'conferidos' && this.moment(this.produto.saldoatual.ultimaconferencia).isAfter(this.filter.dataCorte)) {

          let novoproduto = {
            codproduto: this.produto.produto.codproduto,
            codprodutoimagem: null,
            codprodutoimagemvariacao: null,
            codprodutovariacao: this.produto.variacao.codprodutovariacao,
            descontinuado: this.produto.variacao.descontinuado,
            imagem: null,
            inativo: (this.produto.produto.inativo != null)?this.produto.produto.inativo:this.produto.variacao.inativo,
            produto: this.produto.produto.produto,
            saldoquantidade: this.produto.saldoatual.quantidade,
            ultimaconferencia: this.produto.saldoatual.ultimaconferencia,
            variacao: this.produto.variacao.variacao
          };

          this.data.produtos.unshift(novoproduto)

        // se nao nao faz nada
        } else {
          return
        }
      }

      // remove registro da listagem se está na aba de *conferidos*
      // e data da *conferencia nao e posterior a data de corte*
      if (this.filter.conferidos == 'conferidos'
          && (!this.moment(this.produto.saldoatual.ultimaconferencia).isAfter(this.filter.dataCorte))) {
        this.data.produtos.splice(i, 1);
        return

      // remove registro da listagem se está na aba de produtos *a conferir*
      // e a data de *conferencia e posterior a data de corte*
      } else if (this.filter.conferidos != 'conferidos'
          && this.moment(this.produto.saldoatual.ultimaconferencia).isAfter(this.filter.dataCorte)) {
        this.data.produtos.splice(i, 1);
        return
      }

      // atualiza dados na listagem
      this.data.produtos[i].saldoquantidade = parseFloat(this.produto.saldoatual.quantidade);
      this.data.produtos[i].ultimaconferencia = this.produto.saldoatual.ultimaconferencia

    },

    buscaProduto: function(produto) {
      if (this.swipe) {
        return
      }
      let vm = this;
      var params = {
        barras: null,
        codprodutovariacao: null,
        codestoquelocal: this.filter.codestoquelocal,
        fiscal: this.filter.fiscal
      };
      if (produto == null) {
        if (vm.buscaPorBarras == null) {
          return
        }
        params.barras = vm.buscaPorBarras
      } else {
        this.produtoImagem = produto.imagem;
        params.codprodutovariacao = produto.codprodutovariacao
      }

      // precisa de um tempo (0.3 segundos) para limpar o cambo de codigo de barras em tela
      // se nao no leitor das lojas que e mais lento a dom nao atualiza apesar da variavel estar vazia
      setTimeout(function(){ vm.buscaPorBarras = null }, 300);

      vm.$axios.get('estoque-saldo-conferencia/busca-produto', { params }).then(function(request){
        if (request.data.erro == true) {
          vm.$q.notify({
            message: request.data.mensagem,
            type: 'negative',
          });
          return
        }
        vm.parseProduto(request.data);
        vm.produtoCarregado = true;
        if (produto == null) {
          vm.modalConferencia = true
        } else {
          vm.modalProduto = true
        }

      }).catch(function(error) {
        console.log(error);
        if (produto == null) {
          vm.modalConferencia = false
        } else {
          vm.modalProduto = false
        }
        vm.produtoCarregado = false
      })
    },
    focoCampoBarras: function() {
      this.$nextTick(() => this.$refs.campoBarras.focus())
    },
    focoCampoQuantidadeInformada: function() {
      this.$nextTick(() => this.$refs.campoQuantidadeInformada.focus())
    },
    focoCampoCustoMedioInformado: function() {
      this.$nextTick(() => this.$refs.campoCustoMedioInformado.focus())
    }

  },
  created() {
    this.filter.codestoquelocal = this.$route.params.codestoquelocal;
    this.filter.codmarca = this.$route.params.codmarca;
    this.filter.fiscal = this.$route.params.fiscal;
    this.filter.data = this.$route.params.data;
    this.filter.dataCorte = this.moment().startOf('day').subtract(15, 'days').format('YYYY-MM-DD')
  }
}
</script>
