<template v-if="sUsuario.detalheUsuarios">
  <q-card bordered>
    <q-list separator>
      <q-item>
        <q-item-section avatar>
          <q-avatar color="primary" class="q-my-md" size="70px" text-color="white"
            v-if="sUsuario.detalheUsuarios.usuario">
            {{ primeiraLetra(sUsuario.detalheUsuarios.usuario).toUpperCase() }}
          </q-avatar>
        </q-item-section>
        <q-item-label header>
          <q-item-label>
            <span class="text-h4 text-weight-bold"
              :class="sUsuario.detalheUsuarios.inativo ? 'text-strike text-red-14' : null">{{
                sUsuario.detalheUsuarios.usuario }}</span>

            <q-btn flat round icon="edit" @click="editar()" v-if="user.verificaPermissaoUsuario('Administrador')" />

            <q-btn flat round icon="delete" @click="excluir(sUsuario.detalheUsuarios.codusuario)" />

            <q-btn v-if="user.verificaPermissaoUsuario('Administrador') && !sUsuario.detalheUsuarios.inativo" flat round
              icon="pause" @click="inativar(sUsuario.detalheUsuarios.codusuario)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Inativar
              </q-tooltip>
            </q-btn>

            <q-btn v-if="user.verificaPermissaoUsuario('Administrador') && sUsuario.detalheUsuarios.inativo" flat round
              icon="play_arrow" @click="ativar(sUsuario.detalheUsuarios.codusuario)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Ativar
              </q-tooltip>
            </q-btn>

            <q-btn color="primary" label="Resetar Senha" @click="resetarSenha(sUsuario.detalheUsuarios.codusuario)" />

          </q-item-label>
          <q-item-label v-if="sUsuario.detalheUsuarios.inativo">
            Inativo
          </q-item-label>
          <q-item-label caption v-if="sUsuario.detalheUsuarios.inativo">
            {{ moment(sUsuario.detalheUsuarios.inativo).format('DD/MM/YYYY hh:mm') }}
          </q-item-label>
        </q-item-label>

      </q-item>

      <q-item :to="'/pessoa/' + sUsuario.detalheUsuarios.codpessoa" v-if="sUsuario.detalheUsuarios.codpessoa">
        <q-item-section avatar top>
          <q-avatar icon="badge" color="grey-2" text-color="primary" />
        </q-item-section>
        <q-item-section top>
          <q-item-label>
            {{ sUsuario.detalheUsuarios.Pessoa.pessoa }}
          </q-item-label>
          <q-item-label caption>
            Pessoa
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sUsuario.detalheUsuarios.codfilial">
        <q-item-section avatar top>
          <q-avatar icon="corporate_fare" color="grey-2" text-color="primary" />
        </q-item-section>
        <q-item-section top>
          <q-item-label v-if="sUsuario.detalheUsuarios.filial">
            {{ sUsuario.detalheUsuarios.filial }}
          </q-item-label>
          <q-item-label caption>
            <span>Filial</span>
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sUsuario.detalheUsuarios.codportador">
        <q-item-section avatar top>
          <q-avatar icon="wallet" color="grey-2" text-color="primary" />
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
          <q-avatar icon="login" color="grey-2" text-color="primary" />
        </q-item-section>
        <q-item-section top>
          <q-item-label>
            {{ moment(sUsuario.detalheUsuarios.ultimoacesso).format("DD/MMM/YYYY") }} -
            {{ moment(sUsuario.detalheUsuarios.ultimoacesso).fromNow() }}
          </q-item-label>
          <q-item-label caption>
            Último acesso
          </q-item-label>
        </q-item-section>
      </q-item>

    </q-list>
  </q-card>
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
  name: "CardUsuarioDetalhes",

  methods: {

    resetarSenha(codusuario) {

      this.$q.dialog({
        title: 'Reset de senha',
        message: 'Tem certeza que deseja resetar a senha desse usuário ?',
        cancel: true,
      }).onOk(async () => {
        try {
          const ret = await this.sUsuario.resetarSenha(codusuario)
          console.log(ret)
          if (ret.data) {

            this.$q.dialog({
              title: 'Senha gerada',
              message: 'A nova senha é: <b class="text-h6">' + ret.data + '</b>',
              html: true
            }).onOk(() => {
              // console.log('OK')
            }).onCancel(() => {
              // console.log('Cancel')
            }).onDismiss(() => {
              // console.log('I am triggered on both OK and Cancel')
            })
          }
        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
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

    editar() {
      this.$router.push('/usuarios/' + this.route.params.codusuario + '/editar')
    },

    excluir(codusuario) {

      this.$q.dialog({
        title: 'Excluir pessoa',
        message: 'Tem certeza que deseja excluir esse usuário ?',
        cancel: true,
      }).onOk(async () => {
        try {
          const ret = await this.sUsuario.excluirUsuario(codusuario)
          if (ret.data.result) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Removido'
            })
            this.$router.push('/usuarios')
          }
        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.message
          })
        }
      })
    },

    async inativar(codusuario) {
      try {
        const ret = await this.sUsuario.inativar(codusuario)
        if (ret.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Inativado!'
          })
        }
      } catch (error) {
        this.$q.notify({
          color: 'green-5',
          textColor: 'white',
          icon: 'done',
          message: error.response.data
        })
      }
    },

    async ativar(codusuario) {
      try {
        const ret = await this.sUsuario.ativar(codusuario)
        if (ret.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Ativado!'
          })
        }
      } catch (error) {
        this.$q.notify({
          color: 'green-5',
          textColor: 'white',
          icon: 'done',
          message: error.response.data
        })
      }

    },

  },

  setup() {

    const $q = useQuasar()
    const sUsuario = usuarioStore()
    const route = useRoute()
    const Documentos = formataDocumetos()
    const user = guardaToken()

    return {
      formapagamento: ref({}),
      sUsuario,
      route,
      Documentos,
      user,
      moment,
      DialogDetalhes: ref(false),
    }
  },
})
</script>

<style scoped></style>
