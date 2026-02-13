<script setup>
import { defineAsyncComponent, ref } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { api } from "boot/axios";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { formataData, formataCep } from "src/utils/formatador";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";
import SelectCidade from "components/pessoa/SelectCidade.vue";

// const SelectCidade = defineAsyncComponent(() =>
//   import("components/pessoa/SelectCidade.vue")
// );
const InputFiltered = defineAsyncComponent(() =>
  import("components/InputFiltered.vue")
);

const $q = useQuasar();
const route = useRoute();
const sPessoa = pessoaStore();
const user = guardaToken();

const dialogEndereco = ref(false);
const enderecoNovo = ref(false);
const buscandoCep = ref(false);
const numeroRef = ref(null);
const enderecoRef = ref(null);
const modelEndereco = ref({
  endereco: "",
  numero: "",
  cep: "",
  complemento: "",
  bairro: "",
  codcidade: "",
  entrega: true,
  cobranca: true,
});
const options = ref([]);

function linkMaps(cidade, endereco, numero, cep) {
  return (
    "https://www.google.com/maps/search/?api=1&query=" +
    endereco +
    "," +
    numero +
    "," +
    cidade +
    "," +
    cep
  );
}

function removerAcentos(s) {
  var map = {
    â: "a",
    Â: "A",
    à: "a",
    À: "A",
    á: "a",
    Á: "A",
    ã: "a",
    Ã: "A",
    ê: "e",
    Ê: "E",
    è: "e",
    È: "E",
    é: "e",
    É: "E",
    î: "i",
    Î: "I",
    ì: "i",
    Ì: "I",
    í: "i",
    Í: "I",
    õ: "o",
    Õ: "O",
    ô: "o",
    Ô: "O",
    ò: "o",
    Ò: "O",
    ó: "o",
    Ó: "O",
    ü: "u",
    Ü: "U",
    û: "u",
    Û: "U",
    ú: "u",
    Ú: "U",
    ù: "u",
    Ù: "U",
    ç: "c",
    Ç: "C",
  };
  return s.replace(/[\W\[\] ]/g, function (a) {
    return map[a] || a;
  });
}

async function modalNovoEndereco() {
  dialogEndereco.value = true;
  const cobranca =
    sPessoa.item.PessoaEnderecoS.filter((end) => end.cobranca == true).length ==
    0;
  const entrega =
    sPessoa.item.PessoaEnderecoS.filter((end) => end.entrega == true).length ==
    0;
  const nfe =
    sPessoa.item.PessoaEnderecoS.filter((end) => end.nfe == true).length == 0;
  modelEndereco.value = {
    cobranca: cobranca,
    entrega: entrega,
    nfe: nfe,
  };
  enderecoNovo.value = true;
}

async function novoEndereco() {
  if (modelEndereco.value.endereco !== "") {
    modelEndereco.value.codpessoa = route.params.id;
    try {
      const { data } = await sPessoa.enderecoNovo(
        route.params.id,
        modelEndereco.value
      );
      if (data) {
        dialogEndereco.value = false;
        enderecoNovo.value = false;
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "done",
          message: "Endereço criado.",
        });
      }
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  } else {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: "Campo endereço é obrigatório",
    });
  }
}

async function excluirEndereco(codpessoaendereco) {
  $q.dialog({
    title: "Excluir Endereço",
    message: "Tem certeza que deseja excluir esse endereço?",
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await sPessoa.enderecoExcluir(route.params.id, codpessoaendereco);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Endereço excluido",
      });
      sPessoa.get(route.params.id);
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  });
}

async function editarEndereco(
  codpessoaendereco,
  endereco,
  numero,
  cep,
  complemento,
  bairro,
  codcidade,
  cobranca,
  nfe,
  entrega,
  apelido,
  cidade
) {
  dialogEndereco.value = true;
  enderecoNovo.value = false;
  modelEndereco.value = {
    codpessoaendereco: codpessoaendereco,
    endereco: endereco,
    numero: numero,
    cep: cep,
    complemento: complemento,
    bairro: bairro,
    codcidade: codcidade,
    cobranca: cobranca,
    nfe: nfe,
    entrega: entrega,
    apelido: apelido,
  };
  const ret = await sPessoa.consultaCidade(codcidade);
  options.value = [ret.data[0]];
}

function submit() {
  enderecoNovo.value ? novoEndereco() : salvarEndereco();
}

async function salvarEndereco() {
  try {
    const data = await sPessoa.enderecoSalvar(
      route.params.id,
      modelEndereco.value.codpessoaendereco,
      modelEndereco.value
    );
    if (data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Endereco alterado",
      });
      dialogEndereco.value = false;
      sPessoa.get(route.params.id);
    } else {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Erro ao alterar endereco",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.message,
    });
  }
}

async function BuscaCep() {
  buscandoCep.value = true;
  if (modelEndereco.value.cep.length == 8) {
    const { data } = await api.get(
      "https://viacep.com.br/ws/" + modelEndereco.value.cep + "/json/"
    );
    if (data.logradouro) {
      modelEndereco.value.endereco = data.logradouro;
      modelEndereco.value.bairro = data.bairro;
      const cidadeapicep = data.localidade.toLowerCase();
      const buscarcidade = await api.get(
        "v1/select/cidade?cidade=" + removerAcentos(cidadeapicep)
      );
      modelEndereco.value.codcidade = buscarcidade.data[0].value;
    }
    buscandoCep.value = false;
    setTimeout(() => {
      if (data.logradouro) {
        numeroRef.value?.focus();
      } else {
        enderecoRef.value?.$el?.querySelector("input")?.focus();
      }
    }, 500);
  }
}

async function inativar(codpessoaendereco) {
  try {
    const ret = await sPessoa.enderecoInativar(
      route.params.id,
      codpessoaendereco
    );
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Inativado!",
      });
    } else {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Erro ao inativar",
      });
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

async function ativar(codpessoaendereco) {
  try {
    const ret = await sPessoa.enderecoAtivar(
      route.params.id,
      codpessoaendereco
    );
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Ativado!",
      });
    } else {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Erro ao ativar",
      });
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

async function cima(codpessoa, codpessoaendereco) {
  try {
    await sPessoa.enderecoParaCima(codpessoa, codpessoaendereco);
    $q.notify({
      color: "green-4",
      textColor: "white",
      icon: "done",
      message: "Movido para cima",
    });
    sPessoa.get(codpessoa);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
    sPessoa.get(codpessoa);
  }
}

async function baixo(codpessoa, codpessoaendereco) {
  try {
    await sPessoa.enderecoParaBaixo(codpessoa, codpessoaendereco);
    $q.notify({
      color: "green-4",
      textColor: "white",
      icon: "done",
      message: "Movido para baixo",
    });
    sPessoa.get(codpessoa);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
    sPessoa.get(codpessoa);
  }
}
</script>

<template>
  <!-- DIALOG NOVO/EDITAR ENDEREÇO -->
  <q-dialog v-model="dialogEndereco">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row">
        <template v-if="enderecoNovo"> NOVO ENDEREÇO </template>
        <template v-else> EDITAR ENDEREÇO </template>
      </q-card-section>

      <q-form @submit="submit()">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-xs-12 col-sm-3">
              <q-input
                outlined
                autofocus
                v-model="modelEndereco.cep"
                label="CEP"
                mask="#####-###"
                @change="BuscaCep()"
                unmasked-value
                reactive-rules
                :rules="[(val) => (val && val.length > 7) || 'CEP inválido']"
                required
              />
            </div>

            <div class="col-xs-9 col-sm-7">
              <input-filtered
                ref="enderecoRef"
                outlined
                v-model="modelEndereco.endereco"
                label="Endereço"
                :rules="[
                  (val) =>
                    (val && val.length >= 5) ||
                    'Endereço deve ter no mínimo 5 caracteres',
                  (val) =>
                    (val && val.length <= 60) ||
                    'Endereço não pode ter mais que 60 caracteres',
                ]"
                required
                :disable="buscandoCep"
                maxlength="60"
              />
            </div>
            <div class="col-xs-3 col-sm-2">
              <q-input
                ref="numeroRef"
                outlined
                v-model="modelEndereco.numero"
                label="Numero"
                :rules="[
                  (val) =>
                    (val && val.length >= 0) || 'Número deve ser preenchido',
                  (val) =>
                    (val && val.length <= 10) ||
                    'Número não pode ter mais que 10 caracteres',
                ]"
                required
                :disable="buscandoCep"
                maxlength="10"
              />
            </div>
            <div class="col-xs-12 col-sm-6">
              <input-filtered
                outlined
                v-model="modelEndereco.bairro"
                label="Bairro"
                :rules="[
                  (val) =>
                    (val && val.length >= 2) ||
                    'Bairro deve ter no mínimo 2 caracteres',
                  (val) =>
                    (val && val.length <= 60) ||
                    'Bairro não pode ter mais que 50 caracteres',
                ]"
                required
                :disable="buscandoCep"
                maxlength="50"
              />
            </div>
            <div class="col-xs-12 col-sm-6">
              <input-filtered
                outlined
                v-model="modelEndereco.complemento"
                label="Complemento"
                :disable="buscandoCep"
                :rules="[
                  (val) =>
                    !val ||
                    val.length <= 50 ||
                    'Complemento não pode ter mais que 60 caracteres',
                ]"
                maxlength="50"
              />
            </div>
            <div class="col-xs-12 col-sm-6">
              <select-cidade
                v-model="modelEndereco.codcidade"
                :model-select-cidade="modelEndereco.codcidade"
                :cidadeEditar="options"
                :disable="buscandoCep"
              />
            </div>
            <div class="col-xs-12 col-sm-6">
              <input-filtered
                outlined
                v-model="modelEndereco.apelido"
                label="Apelido"
              />
            </div>
            <div class="col-12">
              <q-toggle v-model="modelEndereco.cobranca" label="Cobrança" />
              <q-toggle v-model="modelEndereco.entrega" label="Entrega" />
              <q-toggle v-model="modelEndereco.nfe" label="Endereço Fiscal" />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            color="grey-8"
            tabindex="-1"
          />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-card bordered flat>
    <q-card-section class="text-grey-9 text-overline row">
      ENDEREÇOS
      <q-space />
      <q-btn
        flat
        round
        dense
        icon="add"
        size="sm"
        color="primary"
        v-if="user.verificaPermissaoUsuario('Publico')"
        @click="modalNovoEndereco()"
      />
    </q-card-section>

    <q-banner
      rounded
      inline-actions
      class="text-white bg-red q-ma-sm"
      v-if="sPessoa.item?.PessoaEnderecoS?.length == 0"
    >
      Sem informar ao menos um endereço, não será possivel emitir Nota Fiscal.
    </q-banner>

    <q-list>
      <template
        v-for="(element, i) in sPessoa.item?.PessoaEnderecoS"
        v-bind:key="element.codpessoaendereco"
      >
        <q-separator inset />
        <q-item class="q-my-sm">
          <!-- BOTAO MAPA -->
          <q-item-section avatar>
            <q-btn
              round
              icon="place"
              color="red"
              flat
              :href="
                linkMaps(
                  element.cidade,
                  element.endereco,
                  element.numero,
                  element.cep
                )
              "
              target="_blank"
            />
          </q-item-section>

          <q-item-section>
            <!-- ENDERECO -->
            <q-item-label :class="element.inativo ? 'text-strike' : null">
              {{ element.endereco }}, {{ element.numero }},
              {{ element.bairro }},
              <template v-if="element.complemento">
                {{ element.complemento }},
              </template>
              {{ element.cidade }}/{{ element.uf }},
              {{ formataCep(element.cep) }}
              <q-icon
                v-if="element.cobranca"
                color="green"
                name="paid"
                class="q-ml-xs"
              >
                <q-tooltip>Cobrança</q-tooltip>
              </q-icon>
              <q-icon
                v-if="element.nfe"
                color="green"
                name="description"
                class="q-ml-xs"
              >
                <q-tooltip>Endereço Fiscal</q-tooltip>
              </q-icon>
              <q-icon
                v-if="element.entrega"
                color="green"
                name="local_shipping"
                class="q-ml-xs"
              >
                <q-tooltip>Entrega</q-tooltip>
              </q-icon>

              <!-- INFO -->
              <icone-info-criacao
                :usuariocriacao="element.usuariocriacao"
                :criacao="element.criacao"
                :usuarioalteracao="element.usuarioalteracao"
                :alteracao="element.alteracao"
              />
            </q-item-label>

            <!-- INATIVO -->
            <q-item-label caption class="text-red-14" v-if="element.inativo">
              Inativo desde: {{ formataData(element.inativo) }}
            </q-item-label>

            <!-- APELIDO -->
            <q-item-label caption v-if="element.apelido">
              {{ element.apelido }}
            </q-item-label>
          </q-item-section>

          <q-item-section side>
            <!-- BOTOES -->
            <q-item-label
              caption
              v-if="user.verificaPermissaoUsuario('Publico')"
            >
              <template v-if="sPessoa.item?.PessoaEnderecoS.length > 1">
                <!-- CIMA -->
                <q-btn
                  flat
                  dense
                  round
                  icon="north"
                  size="sm"
                  color="grey-7"
                  @click="cima(element.codpessoa, element.codpessoaendereco)"
                  v-if="i != 0"
                >
                  <q-tooltip>Mover para cima</q-tooltip>
                </q-btn>

                <!-- BAIXO -->
                <q-btn
                  flat
                  dense
                  round
                  icon="south"
                  size="sm"
                  color="grey-7"
                  @click="baixo(element.codpessoa, element.codpessoaendereco)"
                  v-else
                >
                  <q-tooltip>Mover para baixo</q-tooltip>
                </q-btn>
              </template>

              <!-- EDITAR -->
              <q-btn
                flat
                dense
                round
                icon="edit"
                size="sm"
                color="grey-7"
                @click="
                  editarEndereco(
                    element.codpessoaendereco,
                    element.endereco,
                    element.numero,
                    element.cep,
                    element.complemento,
                    element.bairro,
                    element.codcidade,
                    element.cobranca,
                    element.nfe,
                    element.entrega,
                    element.apelido,
                    element.cidade
                  ),
                    (enderecoNovo = false)
                "
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>

              <!-- INATIVAR -->
              <q-btn
                v-if="!element.inativo"
                flat
                dense
                round
                icon="pause"
                size="sm"
                color="grey-7"
                @click="inativar(element.codpessoaendereco)"
              >
                <q-tooltip>Inativar</q-tooltip>
              </q-btn>

              <!-- ATIVAR -->
              <q-btn
                v-if="element.inativo"
                flat
                dense
                round
                icon="play_arrow"
                size="sm"
                color="grey-7"
                @click="ativar(element.codpessoaendereco)"
              >
                <q-tooltip>Ativar</q-tooltip>
              </q-btn>

              <!-- EXCLUIR -->
              <q-btn
                flat
                dense
                round
                icon="delete"
                size="sm"
                color="grey-7"
                @click="excluirEndereco(element.codpessoaendereco)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>
  </q-card>
</template>

<style scoped></style>
