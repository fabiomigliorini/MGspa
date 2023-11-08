<template>
  <q-card class="no-shadow q-ma-sm" bordered>
    <q-toolbar class="text-black ">
      <q-btn round flat class="q-pa-sm">
        <q-avatar color="primary" size="80px" text-color="white">{{ primeiraletra }}</q-avatar>
      </q-btn>

      <q-item class="q-subtitle-1 q-pl-md">
        <q-item-section>
          <q-item-label lines="1">{{ detalhes_pessoa.pessoa }}</q-item-label>
          <q-item-label caption lines="2">
            <span class="text-weight-bold">{{ detalhes_pessoa.fantasia }}</span>
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-space />
      <q-btn round flat icon="edit" />

    </q-toolbar>
    <q-separator></q-separator>

    <div v-for="detail, detail_index in detalhes_lista" v-bind:key="detail_index">
      <q-item v-ripple>
        <!-- ITEMS DA COLUNA 1 -->
        <q-item-section avatar top>
          <q-avatar :icon="detail.icon" color="grey-2" :text-color="detail.text_color" />
        </q-item-section>

        <q-item-section>

          <q-item-label lines="1" v-if="detail['field'] === 'cnpj' && detalhes_pessoa.fisica == true">{{
            formataCPF(detalhes_pessoa[detail['field']]) }}</q-item-label>
          <q-item-label lines="1" v-if="detail['field'] === 'cnpj' && detalhes_pessoa.fisica == false">{{
            formataCNPJ(detalhes_pessoa[detail['field']]) }}</q-item-label>
          <q-item-label lines="1" v-if="detail['field'] === 'ie'">{{ formataie(detalhes_pessoa[detail['field']])
          }}</q-item-label>

          <q-item-label lines="5"
            v-if="detalhes_pessoa[detail['field']] !== null && detalhes_pessoa[detail['field']] !== false
              && detalhes_pessoa[detail['field']] !== true && detail['field'] !== 'codformapagamento' && detail['field'] !== 'cnpj' && detail['field'] !== 'ie'">{{
    detalhes_pessoa[detail['field']] }}
          </q-item-label>

          <q-item-label lines="1" v-if="detalhes_pessoa[detail['field']] === null">Vazio</q-item-label>

          <q-item-label lines="1" v-if="detail['field'] === 'codformapagamento'">{{ formapagamento }}</q-item-label>

          <q-item-label lines="1" v-if="detalhes_pessoa[detail['field']] === false">Não</q-item-label>

          <q-item-label lines="1" v-if="detalhes_pessoa[detail['field']] === true">Sim</q-item-label>

          <q-item-label caption class="text-grey-8">{{ detail.label }}</q-item-label>

        </q-item-section>

      </q-item>
      <q-separator inset="item" v-if="detail_index != detalhes_lista.length - 1"></q-separator>
    </div>
  </q-card>
  <q-separator />
  <br><br><br>
  <q-card class="no-shadow" bordered>
    <q-toolbar class="bg-primary text-white shadow-2">
      <q-toolbar-title>Cliente</q-toolbar-title>
    </q-toolbar>

    <q-separator></q-separator>

    <div v-for="detail, detail_index in detalhes_lista" v-bind:key="detail_index">
      <q-item v-ripple>

        <!-- ITENS DA COLUNA 2 -->
        <q-item-section avatar top>
          <q-avatar :icon="detalhes_coluna2[detail_index].icon" color="grey-2" :text-color="detail.text_color" />
        </q-item-section>
        <q-item-section>

          <q-item-label lines="2"
            v-if="detalhes_coluna2[detail_index].field === 'notafiscal' && detalhes_pessoa.notafiscal === 0">Tratamento
            Padrão</q-item-label>
          <q-item-label lines="2"
            v-if="detalhes_coluna2[detail_index].field === 'notafiscal' && detalhes_pessoa.notafiscal === 1">Sempre</q-item-label>
          <q-item-label lines="2"
            v-if="detalhes_coluna2[detail_index].field === 'notafiscal' && detalhes_pessoa.notafiscal === 2">Somente
            Fechamento</q-item-label>
          <q-item-label lines="2"
            v-if="detalhes_coluna2[detail_index].field === 'notafiscal' && detalhes_pessoa.notafiscal === 9">Nunca</q-item-label>

          <q-item-label lines="1"
            v-if="detalhes_pessoa[detalhes_coluna2[detail_index].field] === false">Não</q-item-label>
          <q-item-label lines="1"
            v-if="detalhes_pessoa[detalhes_coluna2[detail_index].field] === null">Vazio</q-item-label>

          <q-item-label lines="1" v-if="detalhes_pessoa[detalhes_coluna2[detail_index].field] === true">Sim</q-item-label>

          <q-item-label lines="1" v-if="detalhes_coluna2[detail_index].field === 'toleranciaatraso'">{{
            detalhes_pessoa[detalhes_coluna2[detail_index].field] }} Dias</q-item-label>

          <q-item-label lines="5"
            v-if="detalhes_pessoa[detalhes_coluna2[detail_index].field] !== true && detalhes_pessoa[detalhes_coluna2[detail_index].field] !== false
              && detalhes_coluna2[detail_index].field !== 'toleranciaatraso' && detalhes_coluna2[detail_index].field !== 'notafiscal'">
            {{ detalhes_pessoa[detalhes_coluna2[detail_index].field] }}
          </q-item-label>

          <q-item-label caption class="text-grey-8">{{ detalhes_coluna2[detail_index].label }}</q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset="item" v-if="detail_index != detalhes_lista.length - 1"></q-separator>
    </div>
  </q-card>
</template>

<script>
import { defineComponent, defineAsyncComponent } from 'vue'
import { useQuasar } from "quasar"
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from 'boot/axios'

const primeiraletra = ref('')

const detalhes_lista = [
  { icon: 'label', label: '#', field: 'codpessoa', text_color: 'blue' },
  { icon: 'local_mall', label: 'CNPJ', field: 'cnpj', text_color: 'blue' },
  { icon: 'local_shipping', label: 'RNTRC', field: 'rntrc', text_color: 'blue' },
  { icon: 'app_registration', label: 'Inscrição Estadual', field: 'ie', text_color: 'blue' },
  { icon: 'local_shipping', label: 'Tipo Transportador', field: 'tipotransportador', text_color: 'blue' },
  { icon: 'mark_email_unread', label: 'Mensagem de Venda', field: 'mensagemvenda', text_color: 'blue' },
  { icon: 'attach_money', label: 'Forma de Pagamento', field: 'codformapagamento', text_color: 'blue' },
  { icon: 'money_off', label: 'Desconto', field: 'desconto', text_color: 'blue' },
  { icon: 'sell', label: 'Vendedor', field: 'vendedor', text_color: 'blue' },
  { icon: 'comment', label: 'Observações', field: 'observacoes', text_color: 'blue' },
];

const detalhes_coluna2 = [
  { icon: 'person_pin_circle', label: 'Cliente', field: 'cliente', text_color: 'blue' },
  { icon: 'groups', label: 'Grupo Cliente', field: 'GrupoCliente', text_color: 'blue' },
  { icon: 'shopping_cart', label: 'Consumidor Final', field: 'consumidor', text_color: 'blue' },
  { icon: 'receipt_long', label: 'Nota Fiscal', field: 'notafiscal', text_color: 'blue' },
  { icon: 'money_off', label: 'Crédito Bloqueado', field: 'creditobloqueado', text_color: 'blue' },
  { icon: 'payments', label: 'Saldo em Aberto', field: 'teste', text_color: 'blue' },
  { icon: 'price_change', label: 'Limite de Crédito', field: 'credito', text_color: 'blue' },
  { icon: 'calendar_month', label: 'Primeiro Vencimento', field: 'testess', text_color: 'blue' },
  { icon: 'schedule_send', label: 'Tolerância de Atraso', field: 'toleranciaatraso', text_color: 'blue' },
  { icon: 'switch_account', label: 'Fornecedor', field: 'fornecedor', text_color: 'blue' },
]

export default defineComponent({
  name: "CardDetalhesPessoa",
  props: ['icon', 'text_color', 'value', 'label'],

  methods: {

    formataCPF(cpf) {
      if (cpf == null) {
        return cpf
      }
      cpf = cpf.toString().padStart(11, '0')
      return cpf.slice(0, 3) + "." +
        cpf.slice(3, 6) + "." +
        cpf.slice(6, 9) + "-" +
        cpf.slice(9, 11)
    },

    formataie(ie) {
      if (ie == null) {
        return ie
      }
      ie = ie.toString().padStart(9, '0')
      return ie.slice(0, 2) + "." +
        ie.slice(2, 5) + "." +
        ie.slice(5, 8) + "-" +
        ie.slice(8, 9)
    },

    formataCNPJ(cnpj) {
      if (cnpj == null) {
        return cnpj
      }
      cnpj = cnpj.toString().padStart(14, '0')
      return cnpj.slice(0, 2) + "." +
        cnpj.slice(2, 5) + "." +
        cnpj.slice(5, 8) + "/" +
        cnpj.slice(8, 12) + "-" +
        cnpj.slice(12, 14)
    },
  },

  setup() {

    const $q = useQuasar()

    return {
      detalhes_pessoa: ref({}),
      formapagamento: ref({}),
      detalhes_lista,
      primeiraletra,
      detalhes_coluna2,
    }
  },
  async mounted() {

    const route = useRoute()
    const { data } = await api.get('v1/pessoa/' + route.params.id)
    const consultaformapagamento = await api.get('v1/pessoa/formadepagamento?codformapagamento=' + data.data.codformapagamento)


    this.detalhes_pessoa = data.data
    this.detalhes_pessoa.GrupoCliente = data.data.GrupoCliente.grupocliente
    this.formapagamento = consultaformapagamento.data

    // PEGA A PRIMEIRA LETRA DO NOME PARA PREENCHER NO AVATAR
    if (data.data.fantasia.charAt(0) == ' ') {
      primeiraletra.value = data.data.fantasia.charAt(1)
    } else {
      primeiraletra.value = data.data.fantasia.charAt(0)
    }
  },
})
</script>

<style scoped></style>
