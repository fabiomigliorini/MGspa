<template>
  <mg-layout drawer back-path="/nfe-terceiro">

    <template slot="title">
      Detalhes da nota
    </template>

    <div slot="drawer">

      <template v-if="carregado">
        <q-list>
          <q-list-header>{{nf.emitente}}</q-list-header>

          <q-item dense>
            <q-item-side>
              <q-icon name="date_range" size="25px"/>
              Emissão:
            </q-item-side>
            <q-item-main align="end">{{moment(nf.emissao).format("DD MMM YYYY")}}</q-item-main>
          </q-item>

          <q-item dense>
            <q-item-side>
              <q-icon name="attach_money" size="25px"/>
              Total da nota:
            </q-item-side>
            <q-item-main align="end">R$ {{nf.valortotal}}</q-item-main>
          </q-item>

          <q-item dense>
            <q-item-side>
              <q-icon name="attach_money" size="25px"/>
              Total Complemento:
            </q-item-side>
            <q-item-main align="end">R$ 0000,00</q-item-main>
          </q-item>

          <q-item dense>
            <q-item-side>
              <q-icon name="attach_money" size="25px"/>
              Total Geral:
            </q-item-side>
            <q-item-main align="end">R$ 0000,00</q-item-main>
          </q-item>

          <q-item dense>
            <q-item-main>
              <!--informar natureza da operacao -->
              <q-item-tile>
                <q-field icon="arrow_drop_down_circle">
                  <q-input  float-label="Natureza da oprecação" clearable v-model="filter.filtro" />
                </q-field>
              </q-item-tile>

              <!-- informa data de entrada da nota -->
              <q-item-tile>
                <q-field icon="date_range">
                  <q-input stack-label="Entrada" type="date" v-model="filter.datainicial" align="center" clearable />
                </q-field>
              </q-item-tile>

              <!-- ver detalhes da nota -->
              <q-item-tile >
                <q-icon name="playlist_add" size="25px"/>
                <q-btn  @click="detalhesNF = true" flat>detalhes da nota</q-btn>
              </q-item-tile>

            </q-item-main>
          </q-item>
          <q-item-separator />
        </q-list>

        <template v-if="itemcarregado">
          <q-list multiline highlight v-for="produto in itens.data" :key="produto.codnotafiscalterceiro">
            <q-item>
              <q-item-main dense>
                {{produto.produto}}
              </q-item-main>
            </q-item>
          </q-list>
        </template>

      </template>

    </div>


    <div slot="content">

      <!-- <template v-if="carregado"> -->
      <template v-if="detalhesNF">

        <div class="row gutter-sm">
          <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

            <q-card>
              <q-card-title>
                <div class="row gutter-sm">
                  <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    {{nf.emitente}}
                  </div>
                  <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <small>
                      {{nf.cnpj}} / {{nf.ie}}
                    </small>
                  </div>
                </div>
                <div class="row">
                  <small>
                    <q-icon name="vpn_key"/>
                    {{nf.nfechave}}
                  </small>
                </div>
                <div class="row">
                  <small>
                    <q-icon name="local_offer"/>
                    {{nf.codnotafiscal}}
                  </small>
                </div>
              </q-card-title>
              <q-card-separator />
              <q-card-main>

                <div class="row gutter-xs">

                  <!-- Valores -->
                  <div class="col-xs-12 col-sm-6 col-md-3">
                    <q-card>
                      <q-card-title align="center">Valores</q-card-title>
                      <q-card-separator />
                      <q-card-main >

                        <q-item>
                          <q-item-side>Produtos:</q-item-side>
                          <q-item-main align="end">R$ {{nf.valorprodutos}}</q-item-main>
                        </q-item>

                        <q-item>
                          <q-item-side>Frete:</q-item-side>
                          <q-item-main align="end">R$ {{nf.valorfrete}}</q-item-main>
                        </q-item>

                        <q-item>
                          <q-item-side>Seguro:</q-item-side>
                          <q-item-main align="end">R$ {{nf.valorseguro}}</q-item-main>
                        </q-item>

                        <q-item>
                          <q-item-side>Desconto:</q-item-side>
                          <q-item-main align="end">R$ {{nf.valordesconto}}</q-item-main>
                        </q-item>

                        <q-item>
                          <q-item-side>Outros:</q-item-side>
                          <q-item-main align="end">R$ {{nf.valoroutras}}</q-item-main>
                        </q-item>

                        <q-item>
                          <q-item-side>Total:</q-item-side>
                          <q-item-main align="end">R$ {{nf.valortotal}}</q-item-main>
                        </q-item>

                      </q-card-main>
                    </q-card>
                  </div>

                  <!-- ICMS -->
                  <div class="col-xs-12 col-sm-6 col-md-3">
                    <q-card>
                      <q-card-title align="center">ICMS</q-card-title>
                      <q-card-separator />
                      <q-card-main>

                        <q-item>
                          <q-item-side>Base:</q-item-side>
                          <q-item-main align="end">R$: {{nf.icmsbase}}</q-item-main>
                        </q-item>

                        <q-item>
                          <q-item-side>Total:</q-item-side>
                          <q-item-main align="end">R$ {{nf.icmsvalor}}</q-item-main>
                        </q-item>

                      </q-card-main>
                    </q-card>
                  </div>

                  <!-- ICMSST -->
                  <div class="col-xs-12 col-sm-6 col-md-3">
                    <q-card>
                      <q-card-title align="center">
                        ICMSST
                      </q-card-title>
                      <q-card-separator />
                      <q-card-main>

                        <q-item>
                          <q-item-side>Base:</q-item-side>
                          <q-item-main align="end">R$: {{nf.icmsstbase}}</q-item-main>
                        </q-item>

                        <q-item>
                          <q-item-side>Total:</q-item-side>
                          <q-item-main align="end">R$ {{nf.icmsstvalor}}</q-item-main>
                        </q-item>

                      </q-card-main>
                    </q-card>
                  </div>

                  <!-- IPI -->
                  <div class="col-xs-12 col-sm-6 col-md-3">
                    <q-card>
                      <q-card-title align="center">
                        IPI
                      </q-card-title>
                      <q-card-separator />
                      <q-card-main>

                        <q-item>
                          <q-item-side>Total:</q-item-side>
                          <q-item-main align="end">R$ {{nf.ipivalor}}</q-item-main>
                        </q-item>

                      </q-card-main>
                    </q-card>
                  </div>

                </div>
              </q-card-main>
            </q-card>

          </div>
        </div>
      </template>
      <!-- </template> -->

    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'nfe-terceiro-detalhes-nfe',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data() {
    return {
      filter: {},
      data: {},
      carregado: false,
      itemcarregado: false,
      detalhesNF: false,
    }
  },
  watch: {
    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {},
      deep: true
    }

  },
  methods: {
    carregaNota: function() {
      let vm = this
      let params= {
        chave: this.filter.chave
      }
      vm.$axios.get('nfe-terceiro/busca-nfeterceiro',{params}).then(function(request){
        vm.nf = request.data[0]
        vm.carregado = true
        vm.carregaItens(vm.nf.codnotafiscalterceiro);

      }).catch(function(error) {
        console.log(error)
      })
    },

    carregaItens: function(codnotafiscalterceiro) {
      let vm = this
      let params= {
        codnotafiscalterceiro: codnotafiscalterceiro
      }
      console.log(params)
      vm.$axios.get('nfe-terceiro/lista-item',{params}).then(function(request){
        vm.itens = request
        vm.itemcarregado = true
        console.log(vm.itens)
      }).catch(function(error) {
        console.log(error)
      })
    },

  },
  created() {
    this.filter.chave = this.$route.params.chave
    this.carregaNota();

  }
}
</script>
