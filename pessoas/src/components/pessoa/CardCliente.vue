<script setup>
import { defineAsyncComponent, ref } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { formataDocumetos } from "src/stores/formataDocumentos";

const SelectGrupoCliente = defineAsyncComponent(() =>
  import("components/pessoa/SelectGrupoCliente.vue")
);
const SelectFormaPagamento = defineAsyncComponent(() =>
  import("components/pessoa/SelectFormaPagamento.vue")
);

const $q = useQuasar();
const sPessoa = pessoaStore();
const route = useRoute();
const user = guardaToken();
const Documentos = formataDocumetos();

const modelDialogCliente = ref(false);
const modelEditar = ref([]);

function editarCliente() {
  modelDialogCliente.value = true;
  modelEditar.value = {
    credito: sPessoa.item.credito,
    consumidor: sPessoa.item.consumidor,
    creditobloqueado: sPessoa.item.creditobloqueado,
    mensagemvenda: sPessoa.item.mensagemvenda,
    desconto: sPessoa.item.desconto,
    notafiscal: sPessoa.item.notafiscal,
    codgrupocliente: sPessoa.item.GrupoCliente.codgrupocliente,
    toleranciaatraso: sPessoa.item.toleranciaatraso,
    codformapagamento: sPessoa.item.codformapagamento,
  };
}

async function salvarCliente() {
  try {
    const ret = await sPessoa.clienteSalvar(route.params.id, modelEditar.value);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Alterado",
      });
      sPessoa.item = ret.data.data;
      modelDialogCliente.value = false;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
  }
}

function linkTitulosAbertos() {
  return (
    process.env.MGSIS_URL +
    "index.php?r=titulo/index&Titulo[status]=A&Titulo[codpessoa]=" +
    sPessoa.item.codpessoa
  );
}

function linkRelatorioTitulosAbertos() {
  return (
    process.env.API_URL +
    "v1/titulo/relatorio-pdf?codpessoa=" +
    sPessoa.item.codpessoa
  );
}
</script>

<template>
  <q-card bordered v-if="sPessoa.item.cliente">
    <q-card-section class="bg-yellow text-grey-9 q-py-sm">
      <div class="row items-center no-wrap q-gutter-x-sm">
        <q-icon name="shopping_cart" size="sm" />
        <span class="text-subtitle1 text-weight-medium"
          >Dados do Cliente e Crédito</span
        >
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="edit"
          size="sm"
          color="grey-9"
          v-if="user.verificaPermissaoUsuario('Financeiro')"
          @click="editarCliente()"
        />
        <q-btn
          flat
          round
          dense
          icon="list"
          size="sm"
          color="grey-9"
          :href="linkTitulosAbertos()"
          target="_blank"
        >
          <q-tooltip>Ver títulos em aberto!</q-tooltip>
        </q-btn>
        <q-btn
          flat
          round
          dense
          icon="print"
          size="sm"
          color="grey-9"
          :href="linkRelatorioTitulosAbertos()"
          target="_blank"
        >
          <q-tooltip>Relatório de Títulos em aberto!</q-tooltip>
        </q-btn>
      </div>
    </q-card-section>

    <div class="row q-col-gutter-sm q-pa-md">
      <div class="col-6">
        <div class="text-overline text-grey-7">Saldo em Aberto</div>
        <div class="text-body2" v-if="sPessoa.item.aberto.quantidade > 0">
          {{ sPessoa.item.aberto.quantidade }} Títulos totalizando
          {{
            new Intl.NumberFormat("pt-BR", {
              style: "currency",
              currency: "BRL",
            }).format(sPessoa.item.aberto.saldo)
          }}
          <div>
            <span
              v-if="
                Documentos.verificaPassadoFuturo(
                  sPessoa.item.aberto.vencimento
                )
              "
              class="text-red-14"
            >
              Mais atrasado vencido
              {{ Documentos.formataFromNow(sPessoa.item.aberto.vencimento) }}
            </span>
            <span v-else>
              Primeiro Vencimento
              {{ Documentos.formataFromNow(sPessoa.item.aberto.vencimento) }}
            </span>
          </div>
        </div>
        <div class="text-body2" v-else>Nenhum titulo em aberto</div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Limite de Crédito</div>
        <div class="text-body2">
          {{
            new Intl.NumberFormat("pt-BR", {
              style: "currency",
              currency: "BRL",
            }).format(sPessoa.item.credito)
          }}
        </div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Consumidor Final</div>
        <div class="text-body2">
          {{ sPessoa.item.consumidor ? "Sim" : "Não" }}
        </div>
      </div>

      <div class="col-6" v-if="sPessoa.item.GrupoCliente">
        <div class="text-overline text-grey-7">Grupo Cliente</div>
        <div class="text-body2">
          {{ sPessoa.item.GrupoCliente.grupocliente }}
        </div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Tolerância Atraso</div>
        <div class="text-body2">
          {{ sPessoa.item.toleranciaatraso }} Dia(s)
        </div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Crédito Bloqueado</div>
        <div class="text-body2">
          {{ sPessoa.item.creditobloqueado ? "Sim" : "Não" }}
        </div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Desconto</div>
        <div class="text-body2">
          {{ sPessoa.item.desconto ? sPessoa.item.desconto + "%" : "0,00%" }}
        </div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Vendedor</div>
        <div class="text-body2">
          {{ sPessoa.item.vendedor ? "Sim" : "Não" }}
        </div>
      </div>

      <div class="col-6" v-if="sPessoa.item.codformapagamento">
        <div class="text-overline text-grey-7">Forma de Pagamento</div>
        <div class="text-body2">
          {{ sPessoa.item.FormaPagamento.formapagamento }}
        </div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Nota Fiscal</div>
        <div class="text-body2">
          <span v-if="sPessoa.item.notafiscal == 0">Tratamento Padrão</span>
          <span v-if="sPessoa.item.notafiscal == 1">Sempre</span>
          <span v-if="sPessoa.item.notafiscal == 2">Somente Fechamento</span>
          <span v-if="sPessoa.item.notafiscal == 9">Nunca</span>
          <span v-if="sPessoa.item.notafiscal == null">Vazio</span>
        </div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Fornecedor</div>
        <div class="text-body2">
          {{ sPessoa.item.fornecedor ? "Sim" : "Não" }}
        </div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Cliente</div>
        <div class="text-body2">
          {{ sPessoa.item.cliente ? "Sim" : "Não" }}
        </div>
      </div>

      <div class="col-12" v-if="sPessoa.item.mensagemvenda">
        <div class="text-overline text-grey-7">Mensagem de Venda</div>
        <div
          class="text-body2 bg-grey-2 rounded-borders q-pa-sm"
          style="white-space: pre-line"
        >
          {{ sPessoa.item.mensagemvenda }}
        </div>
      </div>
    </div>
  </q-card>

  <!-- Dialog Editar Cliente / Crédito -->
  <q-dialog v-model="modelDialogCliente">
    <q-card>
      <q-form @submit="salvarCliente()">
        <q-card-section>
          <q-input
            outlined
            autogrow
            v-model="modelEditar.mensagemvenda"
            label="Mensagem de Venda"
            type="textarea"
            class="q-mb-md"
            autofocus
          />

          <select-grupo-cliente
            v-model="modelEditar.codgrupocliente"
            class="q-mb-md"
          />

          <div class="row">
            <div class="col-9">
              <select-forma-pagamento
                v-model="modelEditar.codformapagamento"
                class="q-pr-md"
              />
            </div>
            <div class="col-3">
              <q-input
                outlined
                v-model="modelEditar.desconto"
                label="Desconto"
                type="number"
                min=".1"
                max="50"
                step="0.1"
                unmasked-value
                input-class="text-right"
              >
                <template v-slot:append>
                  <span class="text-caption">%</span>
                </template>
              </q-input>
            </div>
          </div>

          <q-toggle
            outlined
            v-model="modelEditar.creditobloqueado"
            label="Crédito Bloqueado"
          />

          <div class="row" v-if="!modelEditar.creditobloqueado">
            <div
              class="col-9"
              v-if="user.verificaPermissaoUsuario('Financeiro')"
            >
              <q-input
                outlined
                v-model="modelEditar.credito"
                label="Limite de Crédito"
                type="number"
                step="0"
                input-class="text-right"
                class="q-pr-md"
              >
                <template v-slot:prepend>
                  <span class="text-body2">R$</span>
                </template>
              </q-input>
            </div>
            <div
              :class="
                user.verificaPermissaoUsuario('Financeiro')
                  ? 'col-3'
                  : 'col-9 q-pr-md'
              "
            >
              <q-input
                outlined
                v-model="modelEditar.toleranciaatraso"
                label="Tolerância a Atraso"
                type="number"
                class="q-mb-md"
                step="0"
                input-class="text-right"
              >
                <template v-slot:append>
                  <span class="text-caption">Dias</span>
                </template>
              </q-input>
            </div>
          </div>

          <q-select
            outlined
            v-model="modelEditar.notafiscal"
            label="Nota Fiscal"
            :options="[
              { label: 'Tratamento Padrão', value: 0 },
              { label: 'Sempre', value: 1 },
              { label: 'Somente Fechamento', value: 2 },
              { label: 'Nunca', value: 9 },
            ]"
            map-options
            emit-value
            clearable
          />

          <q-toggle
            outlined
            v-model="modelEditar.consumidor"
            label="Consumidor Final"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" v-close-popup />
          <q-btn flat label="Salvar" color="primary" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<style scoped></style>
