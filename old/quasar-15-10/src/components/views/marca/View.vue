<template>
  <mg-layout back-path="/marca/">

    <!-- Título da Página -->
    <template slot="title">
      Marca: {{ item.marca }}
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">

        <!-- <ul class="breadcrumb">
        <li>
          <router-link :to="{ path: '/' }">
            <q-icon name="home"/>
          </router-link>
        </li>
        <li>
          <router-link :to="{ path: '/marca' }">
            <q-icon name="label_outline"/>
          </router-link>
        </li>
        <li>
          <router-link :to="{ path: '/marca/' + this.id }">
            {{ item.marca }}
          </router-link>
        </li>
      </ul>
      -->

      <!--
      <q-breadcrumbs separator=">">
        <q-breadcrumbs-el to="/"><q-icon name="home" size="2rem"/></q-breadcrumbs-el>
        <q-breadcrumbs-el to="/marca"><q-icon name="label_outline" size="2rem"/></q-breadcrumbs-el>
        <q-breadcrumbs-el>{{ item.marca }}</q-breadcrumbs-el>
      </q-breadcrumbs>
      -->

      <div class="row">
        <div class="col-md-6 col-lg-4 col-xl-3 q-pa-sm">
          <q-card>
            <q-card-media overlay-position="top">
              <img :src="item.imagem.url" v-if="item.codimagem">
              <img src="/statics/semimagem.jpg" v-else>
              <q-card-title slot="overlay">
                {{item.marca}}
                <div slot="subtitle">
                  <q-rating readonly v-model="item.abccategoria" :max="3" size="3rem" />
                  <q-icon name="trending_up" /> {{ numeral(item.abcposicao).format('0,0') }}&deg;
                </div>

                <div slot="right" class="row items-center">

                  <q-icon name="more_vert">
                    <q-popover ref="popover">
                      <q-list link class="no-border">
                        <q-item @click="$refs.popover.close()">
                          <q-item-main label="Inativar" />
                        </q-item>
                        <q-item @click="$refs.popover.close()">
                          <q-item-main label="Excluir imagem" />
                        </q-item>
                      </q-list>
                    </q-popover>
                  </q-icon>

                </div>
              </q-card-title>
            </q-card-media>
            <q-card-separator />
            <q-card-main>
              <p class="text-faded">
                Representa {{ numeral(parseFloat(item.vendaanopercentual)).format('0,0.0000') }}% das vendas: <br>
                R$ {{ numeral(new Intl.NumberFormat().format(item.vendabimestrevalor)).format() }} no Bimestre <br>
                R$ {{ numeral(new Intl.NumberFormat().format(item.vendasemestrevalor)).format() }} no Semestre <br>
                R$ {{ numeral(new Intl.NumberFormat().format(item.vendaanovalor)).format() }} no Ano
              </p>

              <!-- <p class="text-faded" v-if="item.itensabaixominimo > 0">
                Última compra {{moment(item.dataultimacompra).fromNow()}}. <br>
                <b>{{ numeral(item.itensabaixominimo).format('0,0') }}</b> produtos da marca estão abaixo do estoque mínimo! <br>
                <b>{{ numeral(item.itensacimamaximo).format('0,0') }}</b> produtos da marca estão acima do estoque máximo!
              </p> -->

              <!-- <p class="text-faded" v-if="item.itensacimamaximo > 0">
                Estoque programado para durar entre
                {{ item.estoqueminimodias }} e
                {{ item.estoquemaximodias }} dias.
              </p> -->

            </q-card-main>
            <q-card-separator />
            <q-card-actions>
              <q-btn color="primary" flat @click.native="planilhaPedido()">Planilha Pedido</q-btn>
              <q-btn color="primary" flat @click.native="planilhaDistribuicaoSaldoDeposito()">Distribuição Saldo</q-btn>
              <q-btn color="primary" flat @click.native="activate()" v-if="item.inativo">Ativar</q-btn>
              <q-btn color="red" flat @click.native="inactivate()" v-else>Inativar</q-btn>
              <q-btn color="primary" flat @click="$router.push('/marca/' + item.codmarca + '/foto/')">
                <span v-if="item.codimagem">Alterar Imagem</span>
                <span v-else>Cadastrar Imagem</span>
              </q-btn>
              <q-btn color="red" flat @click="deleteImage()" v-if="item.codimagem">Excluir Imagem</q-btn>
            </q-card-actions>
          </q-card>
        </div>

        <div class="col-md-6 q-pa-sm" v-if="item.site && item.descricaosite">
          <q-card>
            <q-card-title>
              Site
            </q-card-title>
            <q-card-main>
              <p class="text-faded">
                {{ item.descricaosite }}
              </p>
            </q-card-main>
          </q-card>
        </div>

      </div>

      <q-page-sticky corner="bottom-right" :offset="[18, 18]">
        <q-fab
          color="primary"
          icon="edit"
          active-icon="edit"
          direction="up"
          class="animate-pop"
        >
          <router-link :to="{ path: '/marca/' + item.codmarca + '/update' }">
            <q-fab-action color="primary" icon="edit">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar</q-tooltip>
            </q-fab-action>
          </router-link>
          <q-fab-action color="red" @click.native="destroy()" icon="delete">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Excluir</q-tooltip>
          </q-fab-action>
        </q-fab>
      </q-page-sticky>

    </div>

    <div slot="footer">
      <mg-autor
        :data="item"
        ></mg-autor>
    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'
import { debounce } from 'quasar'

export default {
  components: {
    MgLayout,
    MgAutor,
    debounce
  },

  data () {
    return {
      item: false,
      id: null,
      planilha: null,
    }
  },
  methods: {
    // carrega registros da api
    loadData: debounce(function () {
      // inicializa variaveis
      var vm = this
      var params = {}
      this.loading = true

      // faz chamada api
      vm.$axios.get('marca/' + this.id + '/detalhes', { params }).then(response => {
        vm.item = response.data
        // desmarca flag de carregando
        this.loading = false
      })
    }, 500),

    activate: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Ativar',
        message: 'Tem certeza que deseja inativar?',
        ok: 'Ativar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.delete('marca/' + vm.item.codmarca + '/inativo').then(function (request) {
          vm.$q.notify({
            message: 'Registro ativado',
            type: 'positive',
          })
          vm.loadData(vm.item.codmarca)
        }).catch(function (error) {
          console.log(error)
        })
      })
    },

    inactivate: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?',
        ok: 'Inativar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.post('marca/' + vm.item.codmarca + '/inativo').then(function (request) {
          vm.$q.notify({
            message: 'Registro inativado',
            type: 'positive',
          })
          vm.loadData(vm.item.codmarca)
        }).catch(function (error) {
          console.log(error)
        })
      })
    },

    planilhaPedido: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Planilha de Pedido',
        message: 'Tem certeza que deseja gerar a planilha de pedido de compra?',
        ok: 'Gerar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.put('marca/' + vm.item.codmarca + '/planilha/pedido').then(function (request) {
          let arquivo = request.data;
          vm.planilha = arquivo;
          vm.$q.notify({
            message: 'Planilha criada em "' + arquivo + '"!',
            type: 'positive',
          })
        }).catch(function (error) {
          console.log(error)
        })
      })
    },

    planilhaDistribuicaoSaldoDeposito: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Planilha de Distribuição de Saldo',
        message: 'Tem certeza que deseja criar a planilha de distribuição de saldo do estoque do depósito para as lojas?',
        ok: 'Gerar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.put('marca/' + vm.item.codmarca + '/planilha/distribuicao-saldo-deposito').then(function (request) {
          let arquivo = request.data;
          vm.planilha = arquivo;
          vm.$q.notify({
            message: 'Planilha criada em ' + arquivo + '!',
            type: 'positive',
          })
        }).catch(function (error) {
          console.log(error)
        })
      })
    },

    deleteImage: function () {
      let vm = this
      this.$q.dialog({
        title: 'Excluir',
        message: 'Tem certeza de deseja excluir a imagem?',
        ok: 'Excluir',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.post('imagem/' + vm.item.codimagem + '/inativo', { codmarca: vm.item.codmarca }).then(function (request) {
          vm.loadData(vm.item.codmarca)
          vm.$q.notify({
            message:'Imagem excluida!',
            type:'negative'})
        }).catch(function (error) {
          console.log(error.response)
        })
      })
    },
    destroy: function () {
      let vm = this
      this.$q.dialog({
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?',
        ok: 'Excluir',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.delete('marca/' + vm.item.codmarca).then(function (request) {
          vm.$router.push('/marca')
          vm.$q.notify({
            message:'Registro excluido!',
            type:'positive'})
        }).catch(function (error) {
          console.log(error)
        })
      })

    }
  },
  created () {
    this.id = this.$route.params.id
    this.loadData()
  }

}
</script>

<style>
</style>
