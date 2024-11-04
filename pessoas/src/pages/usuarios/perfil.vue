<template v-if="sUsuario.detalheUsuarios">
  <MGLayout>

    <template #tituloPagina>
      Perfil - Usuário
    </template>

    <template #content>
      <div class="row q-pa-md flex flex-center">
        <div class="col-lg-4 col-md-8 col-sm-12 col-xs-12">
          <q-card bordered>
            <q-list separator>
              <q-item>
                <q-item-section avatar>
                  <q-avatar color="primary" class="q-my-md" size="70px" text-color="white"
                    v-if="sUsuario.detalheUsuarios.usuario">
                    {{ primeiraLetra(sUsuario.detalheUsuarios.usuario).toUpperCase() }}
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label header>
                    <span class="text-h4 text-weight-bold"
                      :class="sUsuario.detalheUsuarios.inativo ? 'text-strike text-red-14' : null">{{
                        sUsuario.detalheUsuarios.usuario }}</span>
                  </q-item-label>
                </q-item-section>

                <q-item-section>
                  <q-item-label>
                    <q-toolbar>
                      <q-space />
                      <q-btn color="primary" label="Alterar senha" @click="editar()" />
                    </q-toolbar>
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item :to="'/pessoa/' + sUsuario.detalheUsuarios.codpessoa"
                v-if="sUsuario.detalheUsuarios.codpessoa">
                <q-item-section avatar top>
                  <q-avatar icon="badge" color="grey-2" text-color="blue" />
                </q-item-section>
                <q-item-section top>
                  <q-item-label v-if="sUsuario.detalheUsuarios.Pessoa">
                    {{ sUsuario.detalheUsuarios.Pessoa.pessoa }}
                  </q-item-label>
                  <q-item-label caption>
                    Pessoa
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="sUsuario.detalheUsuarios.codfilial">
                <q-item-section avatar top>
                  <q-avatar icon="corporate_fare" color="grey-2" text-color="blue" />
                </q-item-section>
                <q-item-section top>
                  <q-item-label>
                    {{ sUsuario.detalheUsuarios.filial }}
                  </q-item-label>
                  <q-item-label caption>
                    Filial
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="sUsuario.detalheUsuarios.codportador">
                <q-item-section avatar top>
                  <q-avatar icon="wallet" color="grey-2" text-color="blue" />
                </q-item-section>
                <q-item-section top>
                  <q-item-label>
                    {{ sUsuario.detalheUsuarios.portador }}
                  </q-item-label>
                  <q-item-label caption>
                    Portador
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section avatar top>
                  <q-avatar icon="login" color="grey-2" text-color="blue" />
                </q-item-section>
                <q-item-section top>
                  <q-item-label>
                    {{ moment(sUsuario.detalheUsuarios.ultimoacesso).format("DD/MMM/YYYY")
                    }} -
                    {{ moment(sUsuario.detalheUsuarios.ultimoacesso).fromNow() }}
                  </q-item-label>
                  <q-item-label caption>
                    Último acesso
                  </q-item-label>
                </q-item-section>
              </q-item>

            </q-list>
          </q-card>
        </div>
      </div>

      <!-- Dialog Alterar Senha -->
      <q-dialog v-model="dialogAlterarSenha">
        <q-card style="width: 350px">
          <q-form @submit="salvar()">
            <q-card-section>

              <!-- SENHA ANTIGA -->
              <q-item>
                <q-item-section avatar top>
                  <q-avatar icon="lock" color="grey-2" text-color="blue" />
                </q-item-section>
                <q-item-section top>
                  <q-item-label>
                    <q-input outlined v-model="modelPerfilUsuario.senha_antiga" :rules="[antigaValida]"
                      label="Senha antiga" :type="isPwd ? 'password' : 'text'">
                      <template v-slot:append>
                        <q-icon :name="isPwd ? 'visibility_off' : 'visibility'" class="cursor-pointer"
                          @click="isPwd = !isPwd" />
                      </template>
                    </q-input>
                  </q-item-label>
                </q-item-section>
              </q-item>

              <!-- SENHA NOVA -->
              <q-item>
                <q-item-section avatar top>
                  <q-avatar icon="lock" color="grey-2" text-color="blue" />
                </q-item-section>
                <q-item-section top>
                  <q-item-label>
                    <q-input outlined v-model="modelPerfilUsuario.senha" label="Nova Senha"
                      :type="isPwd ? 'password' : 'text'" :rules="[senhaValida]">

                      <template v-slot:append>
                        <q-icon :name="isPwd ? 'visibility_off' : 'visibility'" class="cursor-pointer"
                          @click="isPwd = !isPwd" />
                      </template>
                    </q-input>
                  </q-item-label>
                </q-item-section>
              </q-item>

              <!-- CONFIRMACAO SENHA -->
              <q-item>
                <q-item-section avatar top>
                  <q-avatar icon="lock" color="grey-2" text-color="blue" />
                </q-item-section>
                <q-item-section top>
                  <q-item-label>
                    <q-input outlined v-model="modelPerfilUsuario.senha_confirmacao" label="Confirmar nova senha"
                      :rules="[confirmacaoValida]" :type="isPwd ? 'password' : 'text'">

                      <template v-slot:append>
                        <q-icon :name="isPwd ? 'visibility_off' : 'visibility'" class="cursor-pointer"
                          @click="isPwd = !isPwd" />
                      </template>
                    </q-input>
                  </q-item-label>
                </q-item-section>
              </q-item>

            </q-card-section>

            <q-card-actions align="right" class="text-primary">
              <q-btn flat label="Cancelar" v-close-popup />
              <q-btn flat label="Salvar" type="submit" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </template>
  </MGLayout>
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
  name: "perfil",

  components: {
    MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
  },

  methods: {

    primeiraLetra(fantasia) {
      if (fantasia.charAt(0) == ' ') {
        return fantasia.charAt(1)
      }
      return fantasia.charAt(0)
    },

    editar() {

      this.modelPerfilUsuario = {
        codusuario: this.user.usuarioLogado.codusuario,
        usuario: this.user.usuarioLogado.usuario
      }
      this.dialogAlterarSenha = true

    },

    antigaValida(val) {
      return true;
    },

    senhaValida(val) {
      if (String(val).length < 6) {
        return 'Mínimo 6 letras ou números!'
      }
      // const senhavalidaRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,}$/
      // if (!senhavalidaRegex.test(val)) {
      //   return 'A senha deve conter pelo menos uma letra maiuscula, minuscula e um numero!'
      // }
      return true;
    },

    confirmacaoValida(val) {
      if (this.modelPerfilUsuario.senha != val) {
        return 'Senhas não batem!'
      }
      return true;
    },

    async salvar() {
      try {
        if (!this.modelPerfilUsuario.senha_antiga) {
          return this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Digite a senha antiga'
          })

        }
        const ret = await this.sUsuario.usuarioAlterarPerfil(this.modelPerfilUsuario)
        if (ret.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Senha alterada!'
          })
          this.dialogAlterarSenha = false
        }
      } catch (error) {
        console.log(error)
        if (error.response.data.errors && error.response.data.errors.senha) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.errors.senha[0]
          })
        }

        if (error.response.data.errors && error.response.data.errors.senha_antiga) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.errors.senha_antiga[0]
          })
        }

        if (error.response.data.errors && error.response.data.errors.usuario) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.errors.usuario[0]
          })
        }

      }

    }
  },

  setup() {

    const $q = useQuasar()
    const sUsuario = usuarioStore()
    const route = useRoute()
    const Documentos = formataDocumetos()
    const user = guardaToken()
    const modelPerfilUsuario = ref({})
    const dialogAlterarSenha = ref(false)




    return {
      formapagamento: ref({}),
      sUsuario,
      route,
      Documentos,
      user,
      moment,
      dialogAlterarSenha,
      modelPerfilUsuario,
      DialogDetalhes: ref(false),
      isPwd: ref(true),
    }
  },
  async mounted() {
    await this.sUsuario.getUsuario(this.user.usuarioLogado.codusuario)
  }
})
</script>

<style scoped></style>
