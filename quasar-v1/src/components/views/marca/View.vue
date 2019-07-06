<template>
  <mg-layout back-path="/marca/">

    <!-- Título da Página -->
    <template slot="title">
      Marca: {{ item.marca }}
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">
      <div class="row q-pa-md q-col-gutter-sm">
        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
          <q-card class="my-card">
            <!--<q-img :src="item.imagem.url" v-if="item.codimagem">-->
              <!--<div class="absolute-bottom text-subtitle2 text-center">-->
                <!--{{item.marca}}-->
              <!--</div>-->
            <!--</q-img>-->
            <q-img src="https://dummyimage.com/600x400/000/fff">
              <div class="absolute-top text-subtitle2 text-center">
                <q-item-label class="text-h6">

                  {{item.marca}}

                  <q-btn color="grey-6" round flat icon="more_vert" class="float-right" ref="popover">
                    <q-menu cover auto-close>
                      <q-list>
                        <q-item clickable @click="$router.push('/marca/' + item.codmarca + '/foto/')">
                          <q-item-section v-if="!item.codimagem">Adicionar Imagem</q-item-section>
                          <q-item-section v-if="item.codimagem">Aterar Imagem</q-item-section>
                        </q-item>
                        <q-item clickable @click="deleteImage()" v-if="item.codimagem">
                          <q-item-section>Excluir imagem</q-item-section>
                        </q-item>
                        <q-item clickable @click.native="activate()" v-if="item.inativo">
                          <q-item-section>Ativar</q-item-section>
                        </q-item>
                        <q-item clickable @click.native="inactivate()" v-if="!item.inativo">
                          <q-item-section>Inativar</q-item-section>
                        </q-item>
                      </q-list>
                    </q-menu>
                  </q-btn>

                </q-item-label>

                <q-item-label>
                  <q-rating readonly v-model="item.abccategoria" :max="3" size="3rem" />
                  <q-icon name="trending_up" /> {{ numeral(item.abcposicao).format('0,0') }}&deg;
                </q-item-label>

              </div>
            </q-img>
          </q-card>
        </div>

        <div class="col-xs-12 col-sm-6 col-md-8 col-lg-8">

          <q-card class="my-card full-height">
            <q-card-section>
              <q-item class="q-px-none">
                <q-item-section>
                  <q-item-label class="text-subtitle1">
                    Representa {{ numeral(parseFloat(item.vendaanopercentual)).format('0,0.0000') }}% das vendas:
                  </q-item-label>
                  <q-item-label caption>
                    <li>R$ {{ numeral(new Intl.NumberFormat().format(item.vendabimestrevalor)).format() }} no Bimestre</li>
                    <li>R$ {{ numeral(new Intl.NumberFormat().format(item.vendasemestrevalor)).format() }} no Semestre</li>
                    <li>R$ {{ numeral(new Intl.NumberFormat().format(item.vendaanovalor)).format() }} no Ano</li>
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item class="q-px-none">
                <q-item-section v-if="item.itensabaixominimo > 0">
                  <q-item-label class="text-subtitle1">
                    Última compra {{moment(item.dataultimacompra).fromNow()}}.
                  </q-item-label>

                  <q-item-label caption>
                    <li><b>{{ numeral(item.itensabaixominimo).format('0,0') }}</b> produtos da marca estão abaixo do estoque mínimo!</li>
                    <li><b>{{ numeral(item.itensacimamaximo).format('0,0') }}</b> produtos da marca estão acima do estoque máximo!</li>
                  </q-item-label>

                </q-item-section>
              </q-item>

              <q-item class="q-px-none">
                <q-item-section class="text-subtitle1">
                  <q-item-label v-if="item.itensacimamaximo > 0">
                    Estoque programado para durar entre
                    {{ item.estoqueminimodias }} e
                    {{ item.estoquemaximodias }} dias.
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="item.site && item.descricaosite" class="q-px-none">
                <q-item-section>
                  <q-item-label class="text-subtitle1">Site</q-item-label>
                  <q-item-label caption>{{ item.descricaosite }}</q-item-label>
                </q-item-section>
              </q-item>

            </q-card-section>
          </q-card>

        </div>
      </div>

      <q-page-sticky corner="bottom-right" :offset="[18, 18]">
        <q-fab color="primary" icon="edit" active-icon="edit" direction="up" class="animate-pop">
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
      id: null
    }
  },
  methods: {
    // carrega registros da api
    loadData: debounce(function () {
      // inicializa variaveis
      var vm = this;
      var params = {};
      this.loading = true;

      // faz chamada api
      vm.$axios.get('marca/' + this.id + '/detalhes', { params }).then(response => {
        vm.item = response.data;
        // desmarca flag de carregando
        this.loading = false
      })
    }, 500),

    activate: function () {
      let vm = this;
      this.$q.dialog({
        cancel: 'Cancelar',
        persistent: true,
        title: 'Ativar',
        message: 'Tem certeza que deseja Ativar?'
      }).onOk(() => {
        vm.$axios.delete('marca/' + vm.item.codmarca + '/inativo').then(function (request) {
          vm.$q.notify({
            message: 'Registro ativado',
            type: 'positive',
          });
          vm.loadData(vm.item.codmarca)
        }).catch(function (error) {
          console.log(error)
        })
      }).onCancel(() => {});
    },

    inactivate: function () {
      let vm = this;
      this.$q.dialog({
        cancel: 'Cancelar',
        persistent: true,
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?'
      }).onOk(() => {
        vm.$axios.post('marca/' + vm.item.codmarca + '/inativo').then(function (request) {
          vm.$q.notify({
            message: 'Registro inativado',
            type: 'positive',
          });
          vm.loadData(vm.item.codusuario)
        }).catch(function (error) {
          console.log(error)
        })
      }).onCancel(() => {});
    },

    deleteImage: function () {
      let vm = this;

      this.$q.dialog({
        title: 'Excluir',
        message: 'Tem certeza de deseja excluir a imagem?',
        ok: 'Excluir',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.post('imagem/' + vm.item.codimagem + '/inativo', { codmarca: vm.item.codmarca }).then(function (request) {
          vm.loadData(vm.item.codmarca);
          vm.$q.notify({
            message:'Imagem excluida!',
            type:'negative'})
        }).catch(function (error) {
          console.log(error.response)
        })
      })
    },
    destroy: function () {
      let vm = this;
      this.$q.dialog({
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?',
        ok: 'Excluir',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.delete('marca/' + vm.item.codmarca).then(function (request) {
          vm.$router.push('/marca');
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
    this.id = this.$route.params.id;
    this.loadData()
  }

}
</script>

<style>
</style>
