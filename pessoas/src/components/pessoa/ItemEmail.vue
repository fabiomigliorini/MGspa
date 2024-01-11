<template>
  <!-- DIALOG NOVO EMAIL/EDITAR EMAIL  -->
  <q-dialog v-model="dialogEmail">
    <q-card style="min-width: 350px">
      <q-form @submit="emailNovo == true ? novoEmail(route.params.id) : salvarEmail(route.params.id)">
        <q-card-section>
          <div v-if="emailNovo" class="text-h6">Novo Email</div>
          <div v-else class="text-h6">Editar Email</div>
        </q-card-section>
        <q-card-section class="">
          <q-input outlined v-model="modelEmail.email" autofocus label="Email" :rules="[
            val => val && val.length > 0 || 'Email obrigatório'
          ]" class="" />
          <q-input outlined v-model="modelEmail.apelido" label="Apelido" class="" />
          <q-toggle v-model="modelEmail.cobranca" label="Cobrança" />
          <q-toggle v-model="modelEmail.nfe" label="Envio de NFe" />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>


  <q-card bordered>
    <q-list class="">
      <q-item-label header>Email
        <q-btn v-if="user.verificaPermissaoUsuario('Financeiro')" flat round icon="add" @click="modalNovoEmail()" />
      </q-item-label>

      <!-- DRAG AND DROP EMAILS -->
      <draggable class="list-group" item-key="id"
        :component-data="{ tag: 'q-list', name: 'flip-list', type: 'transition' }" :move="alteraOrdem"
        v-model="sPessoa.item.PessoaEmailS" v-bind="dragOptions" @start="isDragging = true" @end="isDragging = false">

        <template #item="{ element }">

          <div>
            <q-separator inset />
            <q-item>
              <q-item-section avatar top>
                <q-avatar icon="email" color="grey-2" text-color="blue" />
              </q-item-section>
              <q-item-section>

                <q-item-label lines="2" @click="linkEmail(element.email)" clickable v-ripple class="cursor-pointer">
                  <s v-if="element.inativo">
                    {{ element.email }}
                  </s>
                  <span v-else>
                    {{ element.email }}
                  </span>
                  <q-icon v-if="element.verificacao" class="" color="blue" name="verified" />
                </q-item-label>

                <q-item-label caption>
                  {{ element.apelido }}
                  <span v-if="element.inativo" class="text-caption text-red-14">
                    Inativo desde: {{ formataData(element.inativo) }}
                  </span>
                  <q-icon v-if="element.cobranca" class="" color="green" name="paid" />
                  <q-icon v-if="element.nfe" class="" color="green" name="description" />
                </q-item-label>

              </q-item-section>
              <q-item-section side>
                <div class="row">

                  <q-btn v-if="!element.verificacao && user.verificaPermissaoUsuario('Financeiro')" label="Verificar" color="blue" flat size="sm" dense
                    @click="enviarEmail(element.email, element.codpessoaemail)" />

                  <q-btn-dropdown flat auto-close dense v-if="user.verificaPermissaoUsuario('Financeiro')">
                    <q-btn v-if="user.verificaPermissaoUsuario('Financeiro')" flat round icon="edit"
                      @click="editarEmail(element.codpessoaemail, element.email, element.apelido, element.verificacao, element.nfe, element.cobranca), emailNovo = false">
                    </q-btn>

                    <q-btn v-if="user.verificaPermissaoUsuario('Financeiro')" flat round icon="delete"
                      @click="excluirEmail(element.codpessoaemail)">
                    </q-btn>

                    <q-btn v-if="user.verificaPermissaoUsuario('Financeiro') && !element.inativo" flat round
                      icon="pause" @click="inativar(element.codpessoa, element.codpessoaemail)">
                      <q-tooltip transition-show="scale" transition-hide="scale">
                        Inativar
                      </q-tooltip>
                    </q-btn>

                    <q-btn v-if="user.verificaPermissaoUsuario('Financeiro') && element.inativo" flat round
                      icon="play_arrow" @click="ativar(element.codpessoa, element.codpessoaemail)">
                      <q-tooltip transition-show="scale" transition-hide="scale">
                        Ativar
                      </q-tooltip>
                    </q-btn>

                    <q-btn flat round icon="info">
                      <q-tooltip transition-show="scale" transition-hide="scale">
                        <q-item-label class="row">Criado por: {{ element.usuariocriacao }} em {{
                          formataData(element.criacao)
                        }}</q-item-label>
                        <q-item-label class="row">Alterado por: {{ element.usuarioalteracao }} em {{
                          formataData(element.alteracao) }}</q-item-label>
                      </q-tooltip>
                    </q-btn>
                  </q-btn-dropdown>
                </div>
              </q-item-section>
            </q-item>
          </div>
        </template>
      </draggable>
    </q-list>
  </q-card>
</template>

<script>
import { defineComponent, onMounted } from 'vue'
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import draggable from 'vuedraggable'
import moment from 'moment'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'



export default defineComponent({
  name: "ItemEmail",

  components: {
    draggable,
  },

  computed: {
    dragOptions() {
      return {
        animation: 500,
        group: "description",
        disabled: false,
        ghostClass: "ghost"
      };
    }
  },

  methods: {
    formataData(data) {
      var dataformatada = moment(data).format('DD/MM/YYYY hh:mm')
      return dataformatada
    },

    alteraOrdem: async function (e) {
      try {
        if (e.willInsertAfter === true) {
          await this.sPessoa.emailParaBaixo(e.draggedContext.element.codpessoa, e.draggedContext.element.codpessoaemail)
        } else {
          await this.sPessoa.emailParaCima(e.draggedContext.element.codpessoa, e.draggedContext.element.codpessoaemail)
        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
        this.sPessoa.get(e.draggedContext.element.codpessoa)
        return
      }
    },

    async modalNovoEmail() {
      this.dialogEmail = true;
      const cobranca = (this.sPessoa.item.PessoaEmailS.filter(email => email.cobranca == true).length == 0);
      const nfe = (this.sPessoa.item.PessoaEmailS.filter(email => email.nfe == true).length == 0);
      this.modelEmail = {
        cobranca: cobranca,
        nfe: nfe
      };
      this.emailNovo = true;
    },

    async novoEmail() {

      if (this.modelEmail.email !== '') {
        this.modelEmail.codpessoa = this.route.params.id
        try {
          const ret = await this.sPessoa.emailNovo(this.route.params.id, this.modelEmail)
          if (ret.data.data) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Email criado.'
            })
            this.emailNovo = false
            this.dialogEmail = false
          }
        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.response.data.message
          })
        }
      } else {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: 'Campo Email é obrigatório!'
        })
      }

    },

    async excluirEmail(codpessoaemail) {

      this.$q.dialog({
        title: 'Excluir Email',
        message: 'Tem certeza que deseja excluir esse email?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          const ret = await this.sPessoa.emailExcluir(this.route.params.id, codpessoaemail)
          if (ret) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Email excluido'
            })
            this.sPessoa.get(this.route.params.id)
          }
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

    editarEmail(codpessoaemail, email, apelido, verificacao, nfe, cobranca) {
      this.dialogEmail = true
      this.modelEmail = { codpessoaemail: codpessoaemail, email: email, apelido: apelido, verificacao: verificacao, nfe: nfe, cobranca: cobranca }
    },

    async salvarEmail(codpessoa) {

      try {
        const ret = await this.sPessoa.emailSalvar(codpessoa, this.modelEmail.codpessoaemail, this.modelEmail)
        if (ret.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Email alterado'
          })
          this.dialogEmail = false
          this.sPessoa.get(this.route.params.id)
        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.response.data.message
        })
      }
    },

    async inativar(codpessoa, codpessoaemail) {
      try {
        const ret = await this.sPessoa.emailInativar(codpessoa, codpessoaemail)
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
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
      }
    },

    async ativar(codpessoa, codpessoaemail) {

      try {
        const ret = await this.sPessoa.emailAtivar(codpessoa, codpessoaemail)
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
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
      }
    },

    linkEmail(email) {
      var a = document.createElement('a');
      a.href = "mailto:" + email
      a.click();
    },

    enviarEmail(email, codpessoaemail) {

      this.$q.dialog({
        title: 'Verificação de E-mail',
        message: 'Deseja enviar o código de verificação para o e-mail  ' + email + ' ?',
        cancel: true,
      }).onOk(() => {
        this.sPessoa.emailVerificar(this.route.params.id, codpessoaemail).then((resp) => {
          if (resp.data) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Código enviado para o e-mail'
            })
          }
        })
        this.confirmaEmail(email, codpessoaemail)

      })
    },

    confirmaEmail(email, codpessoaemail) {

      this.$q.dialog({
        title: 'Verificação de E-mail',
        message: 'Digite o código enviado para o e-mail ' + email,
        prompt: {
          model: '',
          type: 'number',
          step: '1'
        },
        cancel: true,
        persistent: true
      }).onOk(codverificacao => {
        this.postEmail(email, codpessoaemail, codverificacao)
      })
    },


    async postEmail(email, codpessoaemail, codverificacao) {

      try {
        const ret = await this.sPessoa.emailConfirmaVerificacao(this.route.params.id, codpessoaemail, codverificacao)
        if (ret.data.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'E-mail Verificado!'
          })

        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.response.data.message
        })
        this.confirmaEmail(email, codpessoaemail)
      }
    }
  },

  setup() {

    const route = useRoute()
    const $q = useQuasar()
    const sPessoa = pessoaStore()
    const modelEmail = ref({ codpessoa: '', email: '', apelido: '', verificacao: '', nfe: '', cobranca: '' })
    const user = guardaToken(

    )
    return {
      sPessoa,
      route,
      user,
      modelEmail,
      emailNovo: ref(false),
      dialogEmail: ref(false),
    }
  },
})
</script>

<style scoped></style>
