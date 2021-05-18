<template>
  <mg-layout drawer  back-path="/">

    <template slot="title" back-path="/">
      Stone Connect
    </template>

    <div slot="content">
      <div class="q-pa-md row items-start q-gutter-md">
        <q-card class="my-card col-3" flat bordered v-for="filial in filiais">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">{{ filial.filial }}</div>
            <div class="ellipsis">
              {{ filial.stonecode }}
            </div>
          </q-card-section>
          <q-separator />

          <q-list>
            <q-item >
              <q-item-section side>
                <q-icon name="schedule" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="ellipsis">
                  <template v-if="filial.datatoken">
                    <abbr :title="moment(filial.datatoken).format('llll')">
                      {{ moment(filial.datatoken).fromNow() }}
                    </abbr>
                  </template>
                </q-item-label>
                <q-item-label caption>Último Token</q-item-label>
              </q-item-section>
            </q-item>

            <q-item >
              <q-item-section side>
                <q-icon name="fingerprint" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="ellipsis">
                  {{ filial.establishmentid }}
                </q-item-label>
                <q-item-label caption>Establishment ID</q-item-label>
              </q-item-section>
            </q-item>

            <q-item v-for="pos in filial.StonePosS" >
              <q-item-section side>
                <q-icon name="point_of_sale" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="ellipsis">
                  {{pos.apelido}} {{ pos.serialnumber }}
                </q-item-label>
                <q-item-label caption class="ellipsis">
                  {{ pos.referenceid }}
                </q-item-label>
              </q-item-section>
              <q-item-section side v-if="!pos.inativo">
                <q-btn flat>
                  <q-icon name="delete" @click="excluirPos(pos)" />
                </q-btn>
              </q-item-section>
            </q-item>

          </q-list>
          <q-separator />
          <q-card-actions>
            <q-btn icon="add" flat @click="abrirDialogPos(filial.codstonefilial)"> NOVO POS</q-btn>
            <q-btn icon="public" flat> Webhooks</q-btn>
          </q-card-actions>

        </q-card>
      </div>
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="add" color="primary" to="/mdfe/create"/>
      </q-page-sticky>

      <q-dialog v-model="dialogPos" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Novo POS</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-input outlined v-model="pos.apelido" autofocus label="Apelido" />
          <q-card-section class="q-pt-none">
          </q-card-section>
            <q-input outlined v-model="pos.serialnumber"  label="Serial" />
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn flat label="Salvar" color="primary" v-close-popup @click="salvarPos()"/>
          </q-card-actions>
        </q-card>
      </q-dialog>

    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import { debounce } from 'quasar'
export default {
  name: 'stone-connect-index',
  components: {
    MgLayout,
  },
  data () {
    return {
      filiais: [],
      dialogPos: false,
      pos: {
        codstonefilial: null,
        serialnumber: null,
        apelido: null
      },
    }
  },
  watch: {
  },
  methods: {

    loadFiliais: debounce(function () {
      var vm = this;
      vm.$axios.get('stone-connect/filial').then(response => {
        vm.filiais = response.data.data
      })
    }, 500),

    salvarPos: debounce(function () {
      var vm = this;
      var params = this.pos;
      // console.log(params);
      vm.$axios.post('stone-connect/pos', params).then(response => {
        vm.$q.notify({
          message: 'POS Criado!',
          type: 'positive',
        });
        var filial = response.data.data;
        const idx = vm.filiais.findIndex(el => el.codstonefilial === filial.codstonefilial);
        vm.$set(vm.filiais, idx, filial) //works fine
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao criar POS!',
          type: 'negative',
        });
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        });
      });
    }, 500),

    excluirPos: debounce(function (pos) {
      this.$q.dialog({
        title: 'Confirma',
        message: 'Deseja mesmo Excluir o POS "' + pos.apelido + '" (' + pos.serialnumber + ')?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        var vm = this;
        vm.$axios.delete('stone-connect/pos/' + pos.codstonepos).then(response => {
          vm.$q.notify({
            message: 'POS Excluído!',
            type: 'positive',
          });
          var filial = response.data.data;
          const idx = vm.filiais.findIndex(el => el.codstonefilial === filial.codstonefilial);
          vm.$set(vm.filiais, idx, filial) //works fine
        }).catch(function(error) {
          console.log(error);
          vm.$q.notify({
            message: 'Falha ao excluir POS!',
            type: 'negative',
          });
          vm.$q.notify({
            message: error.response.data.message,
            type: 'negative',
          });
        });
      })
    }, 500),

    abrirDialogPos: function(codstonefilial) {
      this.dialogPos = true;
      this.pos.codstonefilial = codstonefilial;
    },

  },
  created () {
    this.loadFiliais();
  }
}
</script>

<style scoped>
</style>
