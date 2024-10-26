<template>
  <!-- DIALOG  NOVO/EDITAR TELEFONE  -->
  <q-dialog v-model="dialogTel">
    <q-card style="min-width: 350px">
      <q-form @submit="telNovo == true ? novoTel(route.params.id) : salvarTel(route.params.id)">
        <q-card-section>
          <div v-if="telNovo" class="text-h6">Novo Telefone</div>
          <div v-else class="text-h6">Editar Telefone</div>
        </q-card-section>

        <q-card-section class="">
          <q-separator spaced></q-separator>
          <small class="text-h8-grey">Tipo:</small>

          <q-radio v-model="modelTel.tipo" checked-icon="task_alt" unchecked-icon="panorama_fish_eye" :val="1"
            label="Fixo" outlined />
          <q-radio v-model="modelTel.tipo" checked-icon="task_alt" unchecked-icon="panorama_fish_eye" :val="2"
            label="Celular" outlined />
          <q-radio v-model="modelTel.tipo" checked-icon="task_alt" unchecked-icon="panorama_fish_eye" :val="9"
            label="Outro" outlined />

          <q-separator spaced></q-separator>

          <q-input outlined v-model="modelTel.pais" mask="(+##)" value="+55" label="País" :rules="[
            val => val && val.length > 0 || 'Pais obrigatório'
          ]" unmasked-value />

          <q-input outlined v-model="modelTel.ddd" mask="(##)" label="DDD" :rules="[
            telNovo == false ? null : val => val && val.length > 0 || 'DDD obrigatório'
          ]" unmasked-value v-if="modelTel.tipo != '9'" />

          <q-input v-if="modelTel.tipo == '2'" outlined v-model="modelTel.telefone" mask="# ####-####" label="Telefone"
            unmasked-value :rules="[
              telNovo == false ? null : val => val && val.length > 0 || 'Telefone obrigatório'
            ]" inputmode="numeric" />

          <q-input v-if="modelTel.tipo == '1'" outlined v-model="modelTel.telefone" mask="####-####" label="Telefone"
            unmasked-value :rules="[
              telNovo == false ? null : val => val && val.length > 0 || 'Telefone obrigatório'
            ]" inputmode="numeric" />

          <q-input v-if="modelTel.tipo == '9'" outlined v-model="modelTel.telefone" label="Telefone" :rules="[
            telNovo == false ? null : val => val && val.length > 0 || 'Telefone obrigatório'
          ]" inputmode="tel" />

          <input-filtered outlined v-model="modelTel.apelido" label="Apelido" :rules="[]" />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-card bordered>
    <q-list>
      <q-item-label header>
        Telefone
        <q-btn v-if="user.verificaPermissaoUsuario('Publico')" flat round icon="add"
          @click="dialogTel = true, modelTel = { tipo: 2, pais: '+55' }, telNovo = true" />
      </q-item-label>


      <div v-for="element in sPessoa.item.PessoaTelefoneS" v-bind:key="element.codpessoatelefone">
        <q-separator inset />
        <q-item>
          <q-item-section avatar top>
            <q-avatar :icon="iconeFone(element.tipo)" color="grey-2" text-color="blue" />
          </q-item-section>
          <q-item-section class="cursor-pointer" lines="1" @click="linkTel(element.ddd, element.telefone)" clickable
            v-ripple>
            <q-item-label v-if="!element.inativo">
              <template v-if="element.ddd">
                ({{ element.ddd }})
              </template>
              {{ formataFone(element.tipo, element.telefone) }}
              <q-icon v-if="element.verificacao" color="blue" name="verified" />
            </q-item-label>
            <q-item-label v-else>
              <s>({{ element.ddd }})
                {{ formataFone(element.tipo, element.telefone) }}</s>
              <q-icon v-if="element.verificacao" color="blue" name="verified" />
            </q-item-label>
            <q-item-label caption>
              {{ element.apelido }}
              <span v-if="element.inativo" class="text-caption text-red-14">
                Inativo desde: {{ formataData(element.inativo) }}
              </span>
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <div class="row">
              <q-btn flat size="sm" dense label="Verificar" color="blue"
                v-if="!element.verificacao && element.tipo === 2 && user.verificaPermissaoUsuario('Publico')"
                @click="enviarSms(element.pais, element.ddd, element.telefone, element.codpessoatelefone)" />

              <q-btn-dropdown dense round flat auto-close v-if="user.verificaPermissaoUsuario('Publico')">
                <q-btn v-if="user.verificaPermissaoUsuario('Publico')" flat icon="edit"
                  @click="editarTel(element.codpessoatelefone, element.ddd, element.telefone, element.apelido, element.tipo, element.verificacao), telNovo = false" />
                <q-btn v-if="user.verificaPermissaoUsuario('Publico')" flat icon="delete"
                  @click="excluirTel(element.codpessoatelefone)" />
                <q-btn v-if="user.verificaPermissaoUsuario('Publico') && !element.inativo" flat icon="pause"
                  @click="inativar(element.codpessoa, element.codpessoatelefone)">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    Inativar
                  </q-tooltip>
                </q-btn>
                <q-btn v-if="user.verificaPermissaoUsuario('Publico') && element.inativo" flat icon="play_arrow"
                  @click="ativar(element.codpessoa, element.codpessoatelefone)">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    Ativar
                  </q-tooltip>
                </q-btn>
                <q-btn v-if="user.verificaPermissaoUsuario('Publico')" flat round icon="expand_less"
                  @click="cima(element.codpessoa, element.codpessoatelefone)">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    Mover para cima
                  </q-tooltip>
                </q-btn>

                <q-btn v-if="user.verificaPermissaoUsuario('Publico')" flat round icon="expand_more"
                  @click="baixo(element.codpessoa, element.codpessoatelefone)">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    Mover para baixo
                  </q-tooltip>
                </q-btn>
                <q-btn flat icon="info">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    <q-item-label class="row">Criado por {{ element.usuariocriacao }} em {{
                      formataData(element.criacao)
                    }}</q-item-label>
                    <q-item-label class="row">Alterado por {{ element.usuarioalteracao }} em {{
                      formataData(element.alteracao) }}</q-item-label>
                  </q-tooltip>
                </q-btn>
              </q-btn-dropdown>
            </div>

          </q-item-section>
        </q-item>
      </div>
    </q-list>
  </q-card>
</template>

<script>
import { defineComponent, defineAsyncComponent } from 'vue'
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import moment from 'moment'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'


const modelTel = ref({})


export default defineComponent({
  name: "ItemTelefone",

  display: "Transition",
  order: 6,

  components: {
    InputFiltered: defineAsyncComponent(() => import('components/InputFiltered.vue')),
  },

  methods: {

    async cima(codpessoa, codpessoatelefone) {
      try {
        await this.sPessoa.telefoneParaCima(codpessoa, codpessoatelefone)

        this.$q.notify({
          color: 'green-4',
          textColor: 'white',
          icon: 'done',
          message: 'Movido para cima'
        })
        this.sPessoa.get(codpessoa)
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
        this.sPessoa.get(codpessoa)
      }
    },

    async baixo(codpessoa, codpessoatelefone) {
      try {
        await this.sPessoa.telefoneParaBaixo(codpessoa, codpessoatelefone)
        this.$q.notify({
          color: 'green-4',
          textColor: 'white',
          icon: 'done',
          message: 'Movido para baixo'
        })
        this.sPessoa.get(codpessoa)
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
        this.sPessoa.get(codpessoa)
      }
    },

    confirmaSmsCel(ddd, telefone, codpessoatelefone) {

      this.$q.dialog({
        title: 'Verificação via SMS',
        message: 'Digite o código enviado para o número ' + '(' + ddd + ') ' + this.formataCelular(telefone),
        prompt: {
          model: '',
          type: 'number',
          step: '1'
        },
        cancel: true,
        persistent: true
      }).onOk(codverificacao => {
        this.postTelefone(ddd, telefone, codpessoatelefone, codverificacao)
      })
    },

    async postTelefone(ddd, telefone, codpessoatelefone, codverificacao) {

      try {
        const ret = await this.sPessoa.telefoneConfirmaVerificacao(this.route.params.id, codpessoatelefone, codverificacao)
        if (ret.data.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Telefone Verificado!'
          })
        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.response.data.message
        })
        this.confirmaSmsCel(ddd, telefone, codpessoatelefone)
      }

    },

    async enviarSms(pais, ddd, telefone, codpessoatelefone) {

      this.$q.dialog({
        title: 'Verificação via SMS',
        message: 'Deseja enviar o código de verificação para o número ' + '(' + ddd + ') ' + this.formataCelular(telefone) + ' ?',
        cancel: true,
      }).onOk(() => {
        this.sPessoa.telefoneVerificar(this.route.params.id, codpessoatelefone).then((resp) => {

          if (resp.data['situacao'] == 'OK') {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Código SMS enviado'
            })
            this.confirmaSmsCel(ddd, telefone, codpessoatelefone)
          } else {
            this.$q.notify({
              color: 'red-5',
              textColor: 'white',
              icon: 'error',
              message: resp.data['descricao']
            })
          }
        })
      })
    },

    async salvarTel(codpessoa) {
      this.dialogTel = false
      try {
        const ret = await this.sPessoa.telefoneAlterar(codpessoa, this.pessoatelefonecod, this.modelTel)

        this.$q.notify({
          color: 'green-5',
          textColor: 'white',
          icon: 'done',
          message: 'Telefone alterado'
        })
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
      }
    },

    async novoTel(codpessoa) {
      this.dialogTel = false
      try {
        const ret = await this.sPessoa.telefoneNovo(codpessoa, this.modelTel)
        if (ret.data.data) {
          this.telNovo = false
          this.sPessoa.item.PessoaTelefoneS = ret.data.data
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Telefone criado.'
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

    editarTel(codpessoatelefone, ddd, telefone, apelido, tipo, verificacao) {
      this.dialogTel = true
      this.modelTel = { ddd: ddd, telefone: telefone, apelido: apelido, tipo: tipo, verificacao: verificacao, pais: '+55' }
      this.pessoatelefonecod = codpessoatelefone
    },

    async inativar(codpessoa, codpessoatelefone) {
      try {
        const ret = await this.sPessoa.telefoneInativar(codpessoa, codpessoatelefone)

        if (ret.data) {
          const i = this.sPessoa.item.PessoaTelefoneS.findIndex(item => item.codpessoatelefone === codpessoatelefone)
          this.sPessoa.item.PessoaTelefoneS[i] = ret.data.data

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

    async ativar(codpessoa, codpessoatelefone) {

      try {
        const ret = await this.sPessoa.telefoneAtivar(codpessoa, codpessoatelefone)
        if (ret.data) {
          const i = this.sPessoa.item.PessoaTelefoneS.findIndex(item => item.codpessoatelefone === codpessoatelefone)
          this.sPessoa.item.PessoaTelefoneS[i] = ret.data.data
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

    async excluirTel(codpessoatelefone) {
      this.$q.dialog({
        title: 'Excluir Contato',
        message: 'Tem certeza que deseja excluir esse telefone?',
        cancel: true,
      }).onOk(async () => {
        try {
          const ret = await this.sPessoa.telefoneExcluir(this.route.params.id, codpessoatelefone)
          if (ret) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Telefone excluido'
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

    iconeFone(tipo) {
      switch (tipo) {
        case 2:
          return 'smartphone'
        case 1:
          return 'phone'
        default:
          return 'device_unknown'
      }
    },

    formataFone(tipo, fone) {
      switch (tipo) {
        case 2:
          return this.formataCelular(fone);
        case 1:
          return this.formataFixo(fone);
        default:
          return fone;
      }
    },

    formataCelular(cel) {
      if (cel == null) {
        return cel
      }
      cel = cel.toString().padStart(9)
      return cel.slice(0, 1) + " " +
        cel.slice(1, 5) + "-" +
        cel.slice(5, 9)
    },

    formataFixo(fixo) {
      if (fixo == null) {
        return fixo
      }
      fixo = fixo.toString().padStart(9)
      return fixo.slice(0, 1) + "" +
        fixo.slice(1, 5) + "-" +
        fixo.slice(5, 9)
    },

    formataData(data) {
      var dataformatada = moment(data).format('DD/MM/YYYY hh:mm')
      return dataformatada
    },

    linkTel(ddd, telefone) {
      var a = document.createElement('a');
      a.href = "tel:" + ddd + telefone
      a.click();
    },
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

  setup() {
    const $q = useQuasar()
    const route = useRoute()
    const sPessoa = pessoaStore()
    const pessoatelefonecod = ref('')
    const user = guardaToken()

    return {
      sPessoa,
      dialogTel: ref(false),
      telNovo: ref(false),
      modelTel,
      user,
      route,
      pessoatelefonecod,
    }
  },

})
</script>

<style lang="scss">
.button {
  margin-top: 35px;
}

.flip-list-move {
  transition: transform 0.5s;
}

.no-move {
  transition: transform 0s;
}

.ghost {
  opacity: 0.5;
  background: #beff8a;
}

.list-group {
  min-height: 20px;
}

.list-group-item {
  cursor: move;
}

.list-group-item i {
  cursor: pointer;
}
</style>
