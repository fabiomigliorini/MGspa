<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Pessoa - Nova</span>
    </template>

    <template #botaoVoltar>
      <q-btn flat dense round :to="{ name: 'pessoa' }" icon="arrow_back" aria-label="Voltar">
      </q-btn>
    </template>

    <template #content>

      <div id="q-app" style="min-height: 100vh;">

        <div class="row">
          <div class="col-11 q-pa-md relative-position flex flex-center">
            <q-stepper v-model="step" ref="stepperRef" color="primary" animated header-nav>

              <!-- TIPO -->
              <q-step :name="0" title="Tipo" :done="step > 0">

                <p>
                  Selecione o Tipo de pessoa, Física para CPF ou Jurídica para CNPJ.
                </p>

                <q-btn-toggle outlined v-model="model.fisica" label="Pessoa Física"
                  :options="[{ label: 'Fisica', value: true }, { label: 'Jurídica', value: false }]"
                  @update:model-value="step = step + 1" />

              </q-step>

              <!-- CNPJ -->
              <q-step :name="1" title="CNPJ/CPF" :done="step > 1">

                <p>
                  Informe o número do documento.
                </p>

                <q-input autofocus outlined v-model="model.cnpj" label="Cnpj" v-if="model.fisica == false"
                  mask="##.###.###/####-##" unmasked-value required :rules="[validaObrigatorio, validaCpfCnpj]"
                  ref="inputCnpjRef" @update:model-value="continuaSeCnpjValido()" />

                <q-input autofocus outlined v-model="model.cnpj" v-if="model.fisica == true" label="CPF"
                  mask="###.###.###-##" unmasked-value required :rules="[validaObrigatorio, validaCpfCnpj]"
                  @update:model-value="continuaSeCnpjValido()" ref="inputCnpjRef" />

              </q-step>

              <!-- CADASTROS DUPLICADOS -->
              <q-step :name="2" title="Duplicidade" :done="step > 2">

                <template v-if="cadastrosEncontrados.length > 0">
                  <p>
                    Já existe cadastro com esse mesmo CNPJ/CPF. Verifique com atenção para evitar duplicidade de cadastro!
                  </p>

                  <div class="row q-pa-md q-col-gutter-md">
                    <div class="col-md-6 col-sm-12 col-xs-12 col-lg-4 col-xl-4"
                      v-for="listagempessoas in cadastrosEncontrados" v-bind:key="listagempessoas.codpessoa">
                      <!-- CARD AQUI -->
                      <card-pessoas :listagempessoas="listagempessoas"></card-pessoas>
                    </div>
                  </div>

                </template>
                <template v-else>
                  Não existe nenhum cadastro com esse mesmo CNPJ/CPF, você pode prosseguir sem medo de duplicar o
                  cadastro!
                </template>

              </q-step>

              <!-- INSCRICAO SEFAZ -->
              <q-step :name="3" title="Inscrição Estadual" :done="step > 3">

                <p>
                  Essas sãos as incrições estaduais que a SEFAZ retornou como válidas. Por favor selecione uma ou clique
                  em sem inscrição para não vincular o cadastro à nenhuma delas.
                </p>

                <div v-for="ie in sefazCadastro.filter((item) => { return (item.cSit == 1) })" v-bind:key="ie.IE">
                  <q-item tag="label" v-ripple>
                    <q-item-section avatar>
                      <q-radio v-model="model.ie" :val="ie.IE" color="teal" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>
                        {{ formataIe(ie.UF, ie.IE) }}
                      </q-item-label>
                      <q-item-label v-if="ie.xFant">
                        {{ ie.xFant }}
                      </q-item-label>
                      <q-item-label v-if="ie.ender" caption>
                        {{ ie.ender.xLgr }},
                        {{ ie.ender.nro }} -
                        <template v-if="ie.ender.xCpl">
                          {{ ie.ender.xCpl }} -
                        </template>
                        {{ ie.ender.xBairro }} -
                        {{ ie.ender.xMun }}/{{ ie.UF }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </div>

                <q-item tag="label" v-ripple>
                  <q-item-section avatar>
                    <q-radio v-model="model.ie" :val="null" color="teal" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Sem inscrição</q-item-label>
                  </q-item-section>
                </q-item>

                <q-input outlined v-model="model.ie" label="Informar Inscrição Estadual" unmasked-value
                  ref="step3Ref"></q-input>
              </q-step>


              <!-- CONCLUIR -->
              <q-step :name="4" title="Finalizar Cadastro" :done="step > 4">

                <q-input outlined v-model="model.fantasia" label="Fantasia" :rules="[
                  val => val && val.length > 0 || 'Nome Fantasia é Obrigatório'
                ]" autofocus ref="step4Ref" />

                <q-input outlined v-model="model.pessoa" label="Razão Social" :rules="[
                  val => val && val.length > 0 || 'Razão Social é Obrigatório'
                ]" ref="step4Ref" />
              </q-step>

              <template v-slot:navigation>
                <q-stepper-navigation>
                  <q-btn @click="step = step + 1" color="primary" :label="step === 4 ? 'Salvar' : 'Continuar'"></q-btn>
                  <q-btn v-if="step > 0" flat color="primary" @click="step = step - 1" label="Voltar"
                    class="q-ml-sm"></q-btn>
                </q-stepper-navigation>
              </template>
            </q-stepper>
          </div>
        </div>
      </div>
    </template>

  </MGLayout>
</template>

<script>
import { ref, defineAsyncComponent } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { useRouter } from 'vue-router'
import { guardaToken } from 'src/stores'
import { pessoaStore } from 'src/stores/pessoa'
import { isCnpjCpfValido } from 'src/utils/validador'
import { formataIe } from 'src/utils/formatador'

export default {
  components: {
    MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
    CardPessoas: defineAsyncComponent(() => import('components/pessoa/CardPessoas.vue')),
  },

  methods: {

    validaObrigatorio(value) {
      if (!value) {
        return "Preenchimento Obrigatório!";
      }
      return true;
    },

    validaCpfCnpj(value) {
      if (!isCnpjCpfValido(value)) {
        return "Número de Documento Inválido!";
      }
      return true;
    },

    async continuaSeCnpjValido() {
      if (!this.model.cnpj) {
        return;
      }
      if (isCnpjCpfValido(this.model.cnpj)) {
        await this.buscarCadastrosCnpj();
        if (this.cadastrosEncontrados.length == 0) {
          this.step = 3;
        } else {
          this.step = 2;
        }
      }
    },

    async buscarCadastrosCnpj() {
      try {
        this.cnpjConsultado = this.model.cnpj;
        const ret = await this.sPessoa.VerificaExisteCnpjCpf({ cnpj: this.model.cnpj })
        if (ret.data.data) {
          this.cadastrosEncontrados = ret.data.data
        }
      } catch (error) {
        console.log(error);
      }
    },

    async verificaIeSefaz() {
      try {
        var codfilial = 101;
        // var codfilial = this.user.usuarioLogado.codfilial;
        const ret = await this.sPessoa.verificaIeSefaz(codfilial, this.model.fisica, this.model.cnpj);
        this.sefazCadastro = ret.data.retSefaz
        this.receitaWsCadastro = ret.data.retReceita
      } catch (error) {
        console.log(error);
        // $q.notify({
        //   color: 'red-5',
        //   textColor: 'white',
        //   icon: 'error',
        //   message: 'Nenhuma IE encontrada na Sefaz'
        // })
      }

    }

  },

  watch: {
    step: function (newVal) {
      this.onContinueStep()
    }
  },


  setup() {
    const cnpjConsultado = ref(null);
    const $q = useQuasar()
    const sPessoa = pessoaStore()
    const router = useRouter()
    const stepperRef = ref(null)
    const inputCnpjRef = ref(null)
    const step2Ref = ref(null)
    const step3Ref = ref(null)
    const step4Ref = ref(null)
    const step = ref(0)
    const model = ref({
      fisica: null,
      cnpj: null
    })


    const cadastrosEncontrados = ref([])
    const count = ref("0")
    const sefazCadastro = ref([])
    const receitaWsCadastro = ref([])
    const user = guardaToken()

    async function onContinueStep() {

      switch (step.value) {
        case 2:
          if (this.model.cnpj != this.cnpjConsultado) {
            this.buscarCadastrosCnpj();
          }
          break;

        case 3:
          this.verificaIeSefaz();
          break;

        case 4:

          if (receitaWsCadastro.value !== null) {

            if (receitaWsCadastro.value.fantasia !== "") {
              model.value.fantasia = receitaWsCadastro.value.fantasia
            }

            model.value.fantasia = receitaWsCadastro.value.fantasia
            model.value.pessoa = receitaWsCadastro.value.nome

          }

          if (sefazCadastro.value !== null && receitaWsCadastro.value == null) {
            model.value.fantasia = sefazCadastro.value[0].xNome
            model.value.pessoa = sefazCadastro.value[0].xNome
          }

          // stepperRef.value.next()

          break;
        case 5:
          await step4Ref.value.validate()

          let post = {
            notafiscal: 0,
            consumidor: true,
            vendedor: false,
            creditobloqueado: true,
            fornecedor: false,
            cliente: false,
            uf: sefazCadastro.value[0] ? sefazCadastro.value[0].UF : null
          }

          const modelPessoa = Object.assign(model.value, post)

          try {
            const ret = await sPessoa.criarPessoa(modelPessoa)

            if (ret.data.data) {
              $q.notify({
                color: 'green-5',
                textColor: 'white',
                icon: 'done',
                message: 'Cadastro criado!'
              })
              router.push('/pessoa/' + ret.data.data.codpessoa)
            }
          } catch (error) {
            $q.notify({
              color: 'red-5',
              textColor: 'white',
              icon: 'error',
              message: error.response.data.message
            })
          }
          break;

        default:
          break;
      }
    }


    return {
      step,
      model,
      stepperRef,
      inputCnpjRef,
      step2Ref,
      step3Ref,
      step4Ref,
      onContinueStep,
      sPessoa,
      sefazCadastro,
      cadastrosEncontrados,
      receitaWsCadastro,
      count,
      formataIe
    }
  }
}
</script>