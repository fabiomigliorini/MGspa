<template>
  <q-form @submit="submit()">
    <!-- <pre>{{ this.sUsuario.detalheUsuarios }}</pre> -->
    <q-card bordered class="q-ma-md">
      <q-card-section>
        <q-item v-if="codusuario">
          <q-item-section avatar>
            <q-avatar color="primary" class="" size="70px" text-color="white">
              {{ primeiraLetra(sUsuario.detalheUsuarios.usuario).toUpperCase() }}
            </q-avatar>
          </q-item-section>
          <q-item-label header>
            <q-item-label>
              <span class="text-h4 text-weight-bold"
                :class="sUsuario.detalheUsuarios.inativo ? 'text-strike text-red-14' : null">
                {{ sUsuario.detalheUsuarios.usuario }}
              </span>
            </q-item-label>
          </q-item-label>
        </q-item>
        <q-item v-else>
          <q-item-section avatar>
            <q-avatar color="primary" class="" size="70px" text-color="white">
              N
            </q-avatar>
          </q-item-section>
          <q-item-label header>
            <q-item-label>
              <span class="text-h4 text-weight-bold">
                Novo Usuário
              </span>
            </q-item-label>
          </q-item-label>
        </q-item>

        <q-item class="q-px-none">
          <q-item-section avatar top>
            <q-avatar icon="corporate_fare" color="grey-2" text-color="primary" />
          </q-item-section>
          <q-item-section top>
            <q-item-label class="row ">
              <q-input class="col-6 q-pr-md" outlined v-model="model.usuario" maxlength="20" :rules="[usuarioValido]"
                label="Usuario" />
              <select-filial class="col-6" outlined label="Filial" v-model="model.codfilial" />
            </q-item-label>
          </q-item-section>
        </q-item>

        <q-item class="q-px-none">
          <q-item-section avatar top>
            <q-avatar icon="wallet" color="grey-2" text-color="primary" />
          </q-item-section>
          <q-item-section top>
            <q-item-label>
              <select-portador outlined label="Portador" v-model="model.codportador" />

            </q-item-label>
          </q-item-section>
        </q-item>
        <q-item class="q-px-none">
          <q-item-section avatar top>
            <q-avatar icon="people" color="grey-2" text-color="primary" />
          </q-item-section>
          <q-item-section top>
            <q-item-label>
              <select-pessoa-usuario outlined label="Pessoa" :modelcod-pessoa="model.codpessoa"
                v-model="model.codpessoa" :rules="[
                  (val) =>
                    (val !== null && val !== '' && val !== undefined) ||
                    'Pessoa Obrigatória',
                ]"></select-pessoa-usuario>
            </q-item-label>
          </q-item-section>
        </q-item>


      </q-card-section>

      <q-card-section class="q-px-md q-py-none">
        <div class="text-subtitle2 q-mb-md">
          Permissões
        </div>
        <div class="row q-col-gutter-md">
          <div class="col-12" v-for="grupo in grupos" v-bind:key="grupo.codgrupousuario">
            <q-card class="no-shadow" bordered>
              <q-list>

                <q-card-section>
                  <div class="text-h6">{{ grupo.grupousuario }}</div>
                </q-card-section>

                <q-separator />
                <q-card-section>
                  <div class="row">
                    <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-2" v-for="filial in filiais"
                      v-bind:key="filial.codfilial">
                      <q-checkbox :label="filial.filial"
                        v-model="model.permissoes[grupo.codgrupousuario][filial.codfilial]" />
                    </div>
                  </div>
                </q-card-section>
                <q-separator />

                <q-card-actions>
                  <q-btn flat @click="marcarTodos(grupo.codgrupousuario)">Marcar todos</q-btn>
                  <q-btn flat @click="marcarNenhum(grupo.codgrupousuario)">Nenhum</q-btn>
                </q-card-actions>
              </q-list>
            </q-card>
          </div>
        </div>
      </q-card-section>

    </q-card>
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="save" color="primary" type="submit" />
    </q-page-sticky>
  </q-form>

</template>

<script>
import { defineComponent, defineAsyncComponent } from 'vue'
import { useQuasar } from "quasar"
import { ref } from 'vue'
import { usuarioStore } from 'stores/usuario'
import { useRoute } from 'vue-router'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import { guardaToken } from 'stores/index'
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");


export default defineComponent({
  name: "FormUsuario",


  components: {
    SelectFilial: defineAsyncComponent(() => import('components/pessoa/SelectFilial.vue')),
    SelectPortador: defineAsyncComponent(() => import('components/select/SelectPortador.vue')),
    SelectPessoaUsuario: defineAsyncComponent(() => import('components/Usuarios/SelectPessoaUsuario.vue')),
  },

  methods: {

    async submit() {

      if (this.codusuario) {
        this.alterar()
      } else {
        this.criar()
      }
    },

    async criar() {

      this.$q.dialog({
        title: 'Criar usuário',
        message: 'Tem certeza que deseja criar esse usuário?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          const ret = await this.sUsuario.postUsuario(this.model)
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Usuário Criado!'
          })
          this.$router.push('/usuarios/')

        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.response.data.message
          })
        }
      })
    },

    async alterar() {

      this.$q.dialog({
        title: 'Alterar usuário',
        message: 'Tem certeza que deseja alterar esse usuário?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          const ret = await this.sUsuario.putUsuario(this.model)
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Usuário alterado!'
          })
          this.$router.push('/usuarios/' + this.sUsuario.detalheUsuarios.codusuario)
        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.response.data.message
          })
        }
      })
    },


    primeiraLetra(fantasia) {
      if (fantasia.charAt(0) == ' ') {
        return fantasia.charAt(1)
      }
      return fantasia.charAt(0)
    },

    marcarTodos(codgrupousuario) {
      for (var [codfilial, value] of Object.entries(this.model.permissoes[codgrupousuario])) {
        this.model.permissoes[codgrupousuario][codfilial] = true;
      }
    },

    marcarNenhum(codgrupousuario) {
      for (var [codfilial, value] of Object.entries(this.model.permissoes[codgrupousuario])) {
        this.model.permissoes[codgrupousuario][codfilial] = false;
      }
    }
  },

  setup() {

    const $q = useQuasar()
    const sUsuario = usuarioStore()
    const route = useRoute()
    const Documentos = formataDocumetos()
    const user = guardaToken()
    const model = ref({
      codusuario: null,
      usuario: null,
      codfilial: null,
      codportador: null,
      codpessoa: null,
      permissoes: {}
    })
    const codusuario = ref(null);
    const grupos = ref([])
    const filiais = ref([])

    const usuarioValido = (val) => {
      if (String(val).length < 4) {
        return 'No mínimo 4 caracteres!'
      }
      const usernameRegex = /^[a-z0-9_.]+$/
      if (!usernameRegex.test(val)) {
        return 'Somente Letras, Números, traços e pontos são aceitos!'
      }
      return true;
    }


    return {
      formapagamento: ref({}),
      sUsuario,
      route,
      Documentos,
      user,
      moment,
      usuarioValido,
      DialogDetalhes: ref(false),
      model,
      isPwd: ref(true),
      grupos,
      filiais,
      codusuario
    }
  },
  async mounted() {

    const retGrupos = await this.sUsuario.getGrupoUsuarios();
    const retFiliais = await this.sUsuario.getFilial();

    if (this.route.params.codusuario) {
      this.codusuario = this.route.params.codusuario;
    }

    this.grupos = retGrupos.data.data.sort((a, b) => {
      return a.grupousuario.localeCompare(b.grupousuario);
    });
    this.filiais = retFiliais.data.data.sort((a, b) => (a.codfilial - b.codfilial))


    if (this.codusuario) {
      this.model.codusuario = this.sUsuario.detalheUsuarios.codusuario;
      this.model.usuario = this.sUsuario.detalheUsuarios.usuario;
      this.model.codfilial = this.sUsuario.detalheUsuarios.codfilial;
      this.model.codportador = this.sUsuario.detalheUsuarios.codportador;
      this.model.codpessoa = this.sUsuario.detalheUsuarios.codpessoa;
    }

    var existe = false;
    var permGrupo = {};
    var codsfiliais = [];
    this.grupos.forEach((grupo) => {
      this.model.permissoes[grupo.codgrupousuario] = {};
      this.filiais.forEach((filial) => {
        existe = false;
        if (this.codusuario) {
          permGrupo = this.sUsuario.detalheUsuarios.permissoes.find((g) => {
            return g.codgrupousuario == grupo.codgrupousuario
          });
          if (permGrupo) {
            existe = permGrupo.filiais.some((f) => {
              return f.codfilial == filial.codfilial
            })
          }
        }
        this.model.permissoes[grupo.codgrupousuario][filial.codfilial] = existe;
      })
    })
  }
})
</script>

<style scoped></style>
