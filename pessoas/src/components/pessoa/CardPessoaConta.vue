<template>
  <q-card bordered>
    <q-list>
      <q-item-label header>
        Contas Bancárias
        <q-btn flat round icon="add" v-if="user.verificaPermissaoUsuario('Publico')"
          @click="dialogNovaConta = true, editarConta = false, modelContaBancaria = {}" />

      </q-item-label>

      <div v-for="contas in sPessoa.item.PessoaContaS" v-bind:key="contas.codpessoaconta">
        <q-separator inset />
        <q-item>
          <q-item-section avatar>
            <q-avatar>
              <q-icon :name="contas.agencia !== null ? 'account_balance' : 'pix'" color="blue" />
            </q-avatar>
          </q-item-section>

          <q-item-section>
            <q-item-label lines="5" :class="contas.inativo ? 'text-strike text-red-14' : null">
              <span v-if="contas.agencia && contas.conta">{{ contas.tipo == 1 ? 'Corrente' : 'Poupança' }},
                {{ contas.nomeBanco }}, {{ contas.banco }}, {{ contas.agencia }}, {{ contas.conta }}</span>
            </q-item-label>

            <q-item-label :class="contas.inativo ? 'text-strike text-red-14' : null">
              <span v-if="contas.pixcpf">
                {{ Documentos.formataCPF(contas.pixcpf.toString().padStart(11, '0')) }}
              </span>
              <span v-if="contas.pixcnpj">
                {{ Documentos.formataCNPJ(contas.pixcnpj.toString().padStart(14, '0')) }}
              </span>
              <span v-if="contas.pixtelefone">{{ Documentos.formataCelularComDDD(contas.pixtelefone) }}</span>
              <span v-if="contas.pixemail">{{ contas.pixemail }}</span>
              <span v-if="contas.pixaleatoria">{{ contas.pixaleatoria }}</span>
            </q-item-label>

            <q-item-label caption v-if="contas.cnpj">
              {{ Documentos.formataCnpjEcpf(contas.cnpj) }}
            </q-item-label>
            <q-item-label caption v-if="contas.titular">
              {{ contas.titular }}
            </q-item-label>

            <q-item-label caption v-if="contas.observacoes">
             {{ contas.observacoes }}
            </q-item-label>

            <q-item-label v-if="contas.inativo" class="text-red-14">
              Inativo
              {{ Documentos.formataFromNow(contas.inativo) }}
            </q-item-label>
          </q-item-section>
          <q-btn-dropdown flat auto-close dense v-if="user.verificaPermissaoUsuario('Publico')">
            <q-btn flat round icon="edit" v-if="user.verificaPermissaoUsuario('Publico')" @click="editarContaBancaria(contas.codpessoaconta, contas.banco, contas.cnpj, contas.agencia,
              contas.conta, contas.pixcpf, contas.pixcnpj, contas.pixtelefone, contas.pixemail,
              contas.pixaleatoria, contas.tipo, contas.titular, contas.observacoes)" />

            <q-btn flat round icon="delete" v-if="user.verificaPermissaoUsuario('Publico')"
              @click="excluirConta(contas.codpessoaconta)" />

            <q-btn v-if="user.verificaPermissaoUsuario('Publico') && !contas.inativo" flat round icon="pause"
              @click="inativar(contas.codpessoaconta)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Inativar
              </q-tooltip>
            </q-btn>

            <q-btn v-if="user.verificaPermissaoUsuario('Publico') && contas.inativo" flat round icon="play_arrow"
              @click="ativar(contas.codpessoaconta)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Ativar
              </q-tooltip>
            </q-btn>


            <q-btn round flat icon="info">
              <q-tooltip>
                <q-item-label class="row">Criado por {{ contas.usuariocriacao }} em {{
                  Documentos.formataData(contas.criacao)
                }}</q-item-label>
                <q-item-label class="row">Alterado por {{ contas.usuarioalteracao }} em {{
                  Documentos.formataData(contas.alteracao) }}</q-item-label>
              </q-tooltip>
            </q-btn>

          </q-btn-dropdown>
        </q-item>
      </div>
      <q-separator inset="item" />
    </q-list>
  </q-card>

  <!-- Dialog nova conta -->
  <q-dialog v-model="dialogNovaConta">
    <q-card style="min-width: 350px">
      <q-form @submit="editarConta == false ? novoRegistroSpc() : salvarConta()">
        <q-card-section>
          <div v-if="editarConta" class="text-h6">Editar Conta Bancária</div>
          <div v-else class="text-h6">Nova Conta Bancária</div>
        </q-card-section>
        <q-card-section>
          <div class="q-col-gutter-md">

            <q-radio v-model="modelContaBancaria.radio" checked-icon="task_alt" unchecked-icon="panorama_fish_eye"
              val="bancaria" label="Conta Bancária" />

            <q-radio v-model="modelContaBancaria.radio" checked-icon="task_alt" unchecked-icon="panorama_fish_eye"
              val="pixcpf" label="Pix Cpf" />
            <q-radio v-model="modelContaBancaria.radio" checked-icon="task_alt" unchecked-icon="panorama_fish_eye"
              val="pixcnpj" label="Pix Cnpj" />
            <q-radio v-model="modelContaBancaria.radio" checked-icon="task_alt" unchecked-icon="panorama_fish_eye"
              val="pixtelefone" label="Pix Telefone" />
            <q-radio v-model="modelContaBancaria.radio" checked-icon="task_alt" unchecked-icon="panorama_fish_eye"
              val="pixemail" label="Pix Email" />
            <q-radio v-model="modelContaBancaria.radio" checked-icon="task_alt" unchecked-icon="panorama_fish_eye"
              val="pixaleatoria" label="Pix Aleatória" />


            <select-banco v-model="modelContaBancaria.banco" v-if="modelContaBancaria.radio === 'bancaria'"
              :model-select-banco="modelContaBancaria.codbanco" :banco-editar="optionsContaEditar" :rules="[
                val => modelContaBancaria.radio === 'bancaria' && val !== null && val !== undefined || 'Banco Obrigatório',
              ]">
            </select-banco>

            <q-select outlined v-model="modelContaBancaria.tipo" v-if="modelContaBancaria.radio === 'bancaria'" :options="[
              { label: 'Conta Corrente', value: 1 },
              { label: 'Conta Poupança', value: 2 }]" map-options emit-value option-value="value" option-label="label"
              label="Tipo" :rules="[
                val => modelContaBancaria.radio === 'bancaria' && val !== null && val !== undefined || 'Tipo Obrigatório',
              ]" />


            <div class="row">
              <div class="col-6 q-pr-md" v-if="modelContaBancaria.radio === 'bancaria'">
                <q-input outlined v-model="modelContaBancaria.agencia" label="Agência" step="any" :rules="[
                  val => modelContaBancaria.radio === 'bancaria' && val !== null && val !== undefined || 'Agência Obrigatória',
                ]" />
              </div>
              <div class="col-6" v-if="modelContaBancaria.radio === 'bancaria'">
                <q-input outlined v-model="modelContaBancaria.conta" label="Conta" step="any" :rules="[
                  val => modelContaBancaria.radio === 'bancaria' && val !== null && val !== undefined || 'Conta Obrigatória',
                ]" />
              </div>


              <div class="col-6 q-pr-md q-pt-md" v-if="modelContaBancaria.radio === 'pixcpf'">
                <q-input outlined v-model="modelContaBancaria.pixcpf" label="Pix CPF" mask="###.###.###-##" type="text"
                  unmasked-value reactive-rules :rules="[
                    val => modelContaBancaria.radio === 'pixcpf' && val !== null && val !== undefined || 'Pix Cpf Obrigatório',
                  ]" />
              </div>


              <div class="col-6 q-pt-md" v-if="modelContaBancaria.radio === 'pixcnpj'">
                <q-input outlined v-model="modelContaBancaria.pixcnpj" label="Pix cnpj" mask="##.###.###/####-##"
                  type="text" unmasked-value :rules="[
                    val => modelContaBancaria.radio === 'pixcnpj' && val !== null && val !== undefined || 'Pix Cnpj Obrigatório',
                  ]" />
              </div>
              <div class="col-6 q-pr-md q-pt-md" v-if="modelContaBancaria.radio === 'pixtelefone'">
                <q-input outlined v-model="modelContaBancaria.pixtelefone" label="Pix telefone" type="text"
                  mask="(##) # ####-####" :rules="[
                    val => modelContaBancaria.radio === 'pixtelefone' && val !== null && val !== undefined || 'Pix telefone Obrigatório',
                  ]" unmasked-value />
              </div>
              <div class="col-6 q-pt-md" v-if="modelContaBancaria.radio === 'pixemail'">
                <q-input outlined v-model="modelContaBancaria.pixemail" label="Pix email" type="text" :rules="[
                  val => modelContaBancaria.radio === 'pixemail' && val !== null && val !== undefined || 'Pix Email Obrigatório',
                ]" />
              </div>
            </div>
            <q-input v-if="modelContaBancaria.radio === 'pixaleatoria'" outlined v-model="modelContaBancaria.pixaleatoria"
              label="Pix chave aleatória" :rules="[
                val => modelContaBancaria.radio === 'pixaleatoria' && val !== null && val !== undefined || 'Pix Aleatória Obrigatório',
              ]" type="text" />


            <q-input outlined v-model="modelContaBancaria.cnpj" v-if="modelContaBancaria.radio === 'bancaria'"
              label="Cnpj/Cpf" step="any" />
            <input-filtered outlined v-model="modelContaBancaria.titular" label="Titular" step="any" />

            <q-input outlined autogrow bordeless v-model="modelContaBancaria.observacoes" class="q-pt-md"
              label="Observações" type="textarea" />

          </div>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script>
import { defineComponent, defineAsyncComponent } from 'vue'
import { useQuasar, debounce } from "quasar"
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'
import { formataDocumetos } from 'src/stores/formataDocumentos'

export default defineComponent({
  name: "CardPessoaConta",

  components: {
    SelectBanco: defineAsyncComponent(() => import('components/pessoa/SelectBanco.vue')),
    InputFiltered: defineAsyncComponent(() => import('components/InputFiltered.vue')),
  },

  methods: {

    async novoRegistroSpc() {
      this.modelContaBancaria.codpessoa = this.route.params.id

      try {
        const ret = await this.sPessoa.novaContaBancaria(this.route.params.id, this.modelContaBancaria)
        if (ret.data.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Conta Criada!'
          })
          this.sPessoa.get(this.route.params.id)
          this.dialogNovaConta = false
        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'warning',
          message: error.response.data.message
        })
      }
    },

    async editarContaBancaria(codpessoaconta, codbanco, cnpj, agencia, conta, pixcpf, pixcnpj, pixtelefone, pixemail, pixaleatoria, tipo, titular, observacoes) {
      this.dialogNovaConta = true
      this.editarConta = true
      this.codpessoaconta = codpessoaconta



      this.modelContaBancaria = {
        banco: codbanco,
        cnpj: cnpj,
        agencia: agencia,
        conta: conta,
        pixcpf: pixcpf ? pixcpf.toString().padStart(11, '0') : null,
        pixcnpj: pixcnpj ? pixcnpj.toString().padStart(14, '0') : null,
        pixtelefone: pixtelefone,
        pixemail: pixemail,
        pixaleatoria: pixaleatoria,
        tipo: tipo,
        titular: titular,
        observacoes: observacoes
      }

      if (this.modelContaBancaria.agencia) {
        this.modelContaBancaria.radio = 'bancaria'
      }
      if (this.modelContaBancaria.pixcpf) {
        this.modelContaBancaria.radio = 'pixcpf'
      }
      if (this.modelContaBancaria.pixcnpj) {
        this.modelContaBancaria.radio = 'pixcnpj'
      }
      if (this.modelContaBancaria.pixtelefone) {
        this.modelContaBancaria.radio = 'pixtelefone'
      }
      if (this.modelContaBancaria.pixemail) {
        this.modelContaBancaria.radio = 'pixemail'
      }
      if (this.modelContaBancaria.pixaleatoria) {
        this.modelContaBancaria.radio = 'pixaleatoria'
      }

      const ret = await this.sPessoa.selectBanco({ codbanco: codbanco })
      this.optionsContaEditar = [ret.data[0]]

    },

    async salvarConta() {

      try {
        const ret = await this.sPessoa.salvarEdicaoContaBancaria(this.route.params.id, this.codpessoaconta, this.modelContaBancaria)
        if (ret.data.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Conta Bancária alterada!'
          })
          const i = this.sPessoa.item.PessoaContaS.findIndex(item => item.codpessoaconta === this.codpessoaconta)
          this.sPessoa.item.PessoaContaS[i] = ret.data.data
          this.dialogNovaConta = false
          this.editarConta = false
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

    async excluirConta(codpessoaconta) {
      this.$q.dialog({
        title: 'Excluir Conta Bancária',
        message: 'Tem certeza que deseja excluir essa conta?',
        cancel: true,
      }).onOk(async () => {
        try {
          const ret = await this.sPessoa.excluirContaBancaria(this.route.params.id, codpessoaconta)
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Conta excluida'
          })
          this.sPessoa.get(this.route.params.id)
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

    async inativar(codpessoaconta) {
      try {
        const ret = await this.sPessoa.contaBancariaInativar(codpessoaconta)
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

    async ativar(codpessoaconta) {
      try {
        const ret = await this.sPessoa.contaBancariaAtivar(codpessoaconta)
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
  },

  setup() {

    const $q = useQuasar()
    const sPessoa = pessoaStore()
    const loading = ref(true)
    const route = useRoute()
    const dialogNovaConta = ref(false)
    const modelContaBancaria = ref({})
    const editarConta = ref(false)
    const user = guardaToken()
    const Documentos = formataDocumetos()
    const codpessoaconta = ref([])
    const optionsContaEditar = ref([])


    return {
      sPessoa,
      Documentos,
      codpessoaconta,
      route,
      optionsContaEditar,
      user,
      loading,
      dialogNovaConta,
      modelContaBancaria,
      editarConta,
    }
  },
})
</script>

<style scoped></style>
