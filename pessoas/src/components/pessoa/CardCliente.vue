<template>
  <q-card bordered v-if="sPessoa.item.cliente">
    <q-list>
      <q-item-label header>
        Dados do Cliente e Crédito
        <q-btn flat round icon="edit" @click="editarCliente()" v-if="user.verificaPermissaoUsuario('Financeiro')" />
      </q-item-label>

      <q-separator inset />

      <div class="row">
        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="payments" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label v-if="sPessoa.item.aberto.quantidade > 0">
                {{ sPessoa.item.aberto.quantidade }} Titulos totalizando
                <br>
                {{ new Intl.NumberFormat('pt-BR', {
                  style: 'currency', currency: 'BRL'
                }).format(sPessoa.item.aberto.saldo) }}
              </q-item-label>
              <q-item-label v-if="sPessoa.item.aberto.quantidade > 0">
                <span v-if="Documentos.verificaPassadoFuturo(sPessoa.item.aberto.vencimento)" class="text-red-14">Mais
                  atrasado vencido {{ Documentos.formataFromNow(sPessoa.item.aberto.vencimento) }}</span>
                <span v-else>
                  Primeiro Vencimento {{ Documentos.formataFromNow(sPessoa.item.aberto.vencimento) }}
                </span>
              </q-item-label>
              <q-item-label v-if="sPessoa.item.aberto.quantidade == 0">
                Nenhum titulo em aberto
              </q-item-label>
              <q-item-label caption class="text-grey-8">Saldo em aberto</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-btn-group flat>
                <q-btn flat dense color="primary" icon="list" :href="linkTitulosAbertos()" class="float-right"
                  target="_blank">
                  <q-tooltip class="bg-primary" :offset="[10, 10]">
                    Ver títulos em aberto!
                  </q-tooltip>
                </q-btn>
                <q-btn flat dense color="primary" icon="print" :href="linkRelatorioTitulosAbertos()" class="float-right"
                  target="_blank">
                  <q-tooltip class="bg-primary" :offset="[10, 10]">
                    Relatório de Títulos em aberto!
                  </q-tooltip>
                </q-btn>
              </q-btn-group>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="credit_card" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(sPessoa.item.credito)
                }}
              </q-item-label>
              <q-item-label caption class="text-grey-8">Limite de Cŕedito</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="shopping_cart" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                <span v-if="sPessoa.item.consumidor">Sim</span>
                <span v-else>Não</span>
              </q-item-label>
              <q-item-label caption class="text-grey-8">Consumidor Final</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top v-if="sPessoa.item.GrupoCliente">
              <q-avatar icon="groups" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section v-if="sPessoa.item.GrupoCliente">
              <q-item-label>
                {{ sPessoa.item.GrupoCliente.grupocliente }}
              </q-item-label>
              <q-item-label caption class="text-grey-8">Grupo Cliente</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="schedule_send" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ sPessoa.item.toleranciaatraso }} Dia(s)
              </q-item-label>
              <q-item-label caption class="text-grey-8">Tolerância Atraso</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="people" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                <span v-if="sPessoa.item.cliente">Sim </span>
                <span v-else>Não</span>
              </q-item-label>
              <q-item-label caption class="text-grey-8">Cliente</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="credit_card_off" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                <span v-if="sPessoa.item.creditobloqueado">Sim</span>
                <span v-else>Não</span>
              </q-item-label>
              <q-item-label caption class="text-grey-8">Crédito Bloqueado</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="payments" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                <span v-if="sPessoa.item.desconto">{{ sPessoa.item.desconto }}%</span>
                <span v-else>0,00%</span>
              </q-item-label>
              <q-item-label caption class="text-grey-8">Desconto</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="sell" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label lines="5">
                <span v-if="sPessoa.item.vendedor">Sim</span>
                <span v-else>Não</span>
              </q-item-label>
              <q-item-label caption class="text-grey-8">Vendedor</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="attach_money" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label lines="2" v-if="sPessoa.item.codformapagamento">
                {{ sPessoa.item.FormaPagamento.formapagamento }}
              </q-item-label>
              <q-item-label caption class="text-grey-8">Forma de Pagamento</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="receipt_long" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label lines="5">
                <span v-if="sPessoa.item.notafiscal == 0">Tratamento Padrão</span>
                <span v-if="sPessoa.item.notafiscal == 1">Sempre</span>
                <span v-if="sPessoa.item.notafiscal == 2">Somente Fechamento</span>
                <span v-if="sPessoa.item.notafiscal == 9">Nunca</span>
                <span v-if="sPessoa.item.notafiscal == null">Vazio</span>
              </q-item-label>
              <q-item-label caption class="text-grey-8">Nota Fiscal</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="switch_account" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                <span v-if="sPessoa.item.fornecedor">Sim</span>
                <span v-else>Não</span>
              </q-item-label>
              <q-item-label caption class="text-grey-8">Fornecedor</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>

        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="schedule_send" color="grey-2" text-color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label lines="10" style="white-space: pre">
                {{ sPessoa.item.mensagemvenda }}
                <q-tooltip>
                  {{ sPessoa.item.mensagemvenda }}
                </q-tooltip>
              </q-item-label>
              <q-item-label caption class="text-grey-8">Mensagem de Venda</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
        </div>
      </div>
    </q-list>
  </q-card>

  <!-- Dialog Editar Cliente / Crédito -->
  <q-dialog v-model="modelDialogCliente">
    <q-card>
      <q-form ref="formCliente" @submit="salvarCliente()">
        <q-card-section>

          <q-input outlined autogrow v-model="modelEditar.mensagemvenda" label="Mensagem de Venda" type="textarea"
            class="q-mb-md" autofocus />

          <select-grupo-cliente v-model="modelEditar.codgrupocliente" class="q-mb-md">
          </select-grupo-cliente>

          <div class="row">
            <div class="col-9">
              <select-forma-pagamento v-model="modelEditar.codformapagamento" class="q-pr-md">
              </select-forma-pagamento>
            </div>
            <div class="col-3">
              <q-input outlined class="" v-model="modelEditar.desconto" label="Desconto" type="number" min=".1" max="50"
                step="0.1" unmasked-value input-class="text-right">
                <template v-slot:append>
                  <span class="text-caption">%</span>
                </template>

              </q-input>
            </div>
          </div>

          <q-toggle class="" outlined v-model="modelEditar.creditobloqueado" label="Crédito Bloqueado" />

          <div class="row" v-if="!modelEditar.creditobloqueado">
            <div class="col-9" v-if="user.verificaPermissaoUsuario('Financeiro')">
              <q-input outlined v-model="modelEditar.credito" label="Limite de Crédito" type="number" step="0"
                input-class="text-right" class="q-pr-md">
                <template v-slot:prepend>
                  <span class="text-body2">R$</span>
                </template>
              </q-input>

            </div>
            <div :class="user.verificaPermissaoUsuario('Financeiro') ? 'col-3' : 'col-9 q-pr-md'">
              <q-input outlined v-model="modelEditar.toleranciaatraso" label="Tolerância a Atraso" type="number"
                class="q-mb-md" step="0" input-class="text-right">
                <template v-slot:append>
                  <span class="text-caption">Dias</span>
                </template>
              </q-input>
            </div>
          </div>

          <q-select outlined v-model="modelEditar.notafiscal" label="Nota Fiscal" class="" :options="[
            { label: 'Tratamento Padrão', value: 0 },
            { label: 'Sempre', value: 1 },
            { label: 'Somente Fechamento', value: 2 },
            { label: 'Nunca', value: 9 }]" map-options emit-value clearable />

          <q-toggle class="" outlined v-model="modelEditar.consumidor" label="Consumidor Final" /> &nbsp;

        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" v-close-popup />
          <q-btn flat label="Salvar" color="primary" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script>
import { defineComponent, defineAsyncComponent } from 'vue'
import { useQuasar } from "quasar"
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'
import { formataDocumetos } from 'src/stores/formataDocumentos'

export default defineComponent({
  name: "CardDetalhesPessoa",

  components: {
    SelectGrupoCliente: defineAsyncComponent(() => import('components/pessoa/SelectGrupoCliente.vue')),
    SelectFormaPagamento: defineAsyncComponent(() => import('components/pessoa/SelectFormaPagamento.vue')),
  },

  methods: {

    async buscaPagamento() {
      if (this.sPessoa.item.codformapagamento) {
        const pagamento = await this.sPessoa.buscaFormaPagamento(this.sPessoa.item.codformapagamento)
        this.formapagamento = pagamento.data
      }
    },

    editarCliente() {
      this.modelDialogCliente = true
      this.modelEditar = {
        credito: this.sPessoa.item.credito,
        consumidor: this.sPessoa.item.consumidor,
        creditobloqueado: this.sPessoa.item.creditobloqueado,
        mensagemvenda: this.sPessoa.item.mensagemvenda,
        desconto: this.sPessoa.item.desconto,
        notafiscal: this.sPessoa.item.notafiscal,
        codgrupocliente: this.sPessoa.item.GrupoCliente.codgrupocliente,
        toleranciaatraso: this.sPessoa.item.toleranciaatraso,
        codformapagamento: this.sPessoa.item.codformapagamento
      }
    },

    async salvarCliente() {
      try {
        const ret = await this.sPessoa.clienteSalvar(this.route.params.id, this.modelEditar)
        if (ret.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Alterado'
          })
          this.sPessoa.item = ret.data.data
          this.modelDialogCliente = false;
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

    linkTitulosAbertos() {
      return process.env.MGSIS_URL + "index.php?r=titulo/index&Titulo[status]=A&Titulo[codpessoa]=" + this.sPessoa.item.codpessoa
    },

    linkRelatorioTitulosAbertos() {
      return process.env.API_URL + "v1/titulo/relatorio-pdf?codpessoa=" + this.sPessoa.item.codpessoa
    },
  },

  setup() {

    const $q = useQuasar()
    const sPessoa = pessoaStore()
    const modelEditar = ref([])
    const route = useRoute()
    const user = guardaToken()
    const Documentos = formataDocumetos()

    return {
      formapagamento: ref({}),
      sPessoa,
      user,
      modelDialogCliente: ref(false),
      modelEditar,
      route,
      Documentos,
    }
  }
})
</script>

<style scoped></style>
