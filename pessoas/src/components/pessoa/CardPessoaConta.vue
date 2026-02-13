<script setup>
import { defineAsyncComponent, ref } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { formataDocumetos } from "src/stores/formataDocumentos";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";

const SelectBanco = defineAsyncComponent(() =>
  import("components/pessoa/SelectBanco.vue")
);
const InputFiltered = defineAsyncComponent(() =>
  import("components/InputFiltered.vue")
);

const $q = useQuasar();
const sPessoa = pessoaStore();
const route = useRoute();
const user = guardaToken();
const Documentos = formataDocumetos();

const dialogNovaConta = ref(false);
const modelContaBancaria = ref({});
const editarConta = ref(false);
const codpessoaconta = ref([]);
const optionsContaEditar = ref([]);

async function novaContaBancaria() {
  modelContaBancaria.value.codpessoa = route.params.id;
  try {
    const ret = await sPessoa.novaContaBancaria(
      route.params.id,
      modelContaBancaria.value
    );
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Conta Criada!",
      });
      dialogNovaConta.value = false;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "warning",
      message: error.response.data.message,
    });
  }
}

async function editarContaBancaria(
  cod,
  codbanco,
  cnpj,
  agencia,
  conta,
  pixcpf,
  pixcnpj,
  pixtelefone,
  pixemail,
  pixaleatoria,
  tipo,
  titular,
  observacoes
) {
  dialogNovaConta.value = true;
  editarConta.value = true;
  codpessoaconta.value = cod;

  modelContaBancaria.value = {
    banco: codbanco,
    cnpj: cnpj,
    agencia: agencia,
    conta: conta,
    pixcpf: pixcpf ? pixcpf.toString().padStart(11, "0") : null,
    pixcnpj: pixcnpj ? pixcnpj.toString().padStart(14, "0") : null,
    pixtelefone: pixtelefone,
    pixemail: pixemail,
    pixaleatoria: pixaleatoria,
    tipo: tipo,
    titular: titular,
    observacoes: observacoes,
  };

  if (modelContaBancaria.value.agencia)
    modelContaBancaria.value.radio = "bancaria";
  if (modelContaBancaria.value.pixcpf)
    modelContaBancaria.value.radio = "pixcpf";
  if (modelContaBancaria.value.pixcnpj)
    modelContaBancaria.value.radio = "pixcnpj";
  if (modelContaBancaria.value.pixtelefone)
    modelContaBancaria.value.radio = "pixtelefone";
  if (modelContaBancaria.value.pixemail)
    modelContaBancaria.value.radio = "pixemail";
  if (modelContaBancaria.value.pixaleatoria)
    modelContaBancaria.value.radio = "pixaleatoria";

  const ret = await sPessoa.selectBanco({ codbanco: codbanco });
  optionsContaEditar.value = [ret.data[0]];
}

async function salvarConta() {
  try {
    const ret = await sPessoa.salvarEdicaoContaBancaria(
      route.params.id,
      codpessoaconta.value,
      modelContaBancaria.value
    );
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Conta Bancária alterada!",
      });
      const i = sPessoa.item.PessoaContaS.findIndex(
        (item) => item.codpessoaconta === codpessoaconta.value
      );
      sPessoa.item.PessoaContaS[i] = ret.data.data;
      dialogNovaConta.value = false;
      editarConta.value = false;
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

async function excluirConta(cod) {
  $q.dialog({
    title: "Excluir Conta Bancária",
    message: "Tem certeza que deseja excluir essa conta?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sPessoa.excluirContaBancaria(route.params.id, cod);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Conta excluida",
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

async function inativar(cod) {
  try {
    const ret = await sPessoa.contaBancariaInativar(cod);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Inativado!",
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

async function ativar(cod) {
  try {
    const ret = await sPessoa.contaBancariaAtivar(cod);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Ativado!",
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
</script>

<template>
  <div>
  <q-card bordered>
    <q-card-section class="bg-yellow text-grey-9 q-py-sm">
      <div class="row items-center no-wrap q-gutter-x-sm">
        <q-icon name="account_balance" size="sm" />
        <span class="text-subtitle1 text-weight-medium">Contas Bancárias</span>
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="add"
          size="sm"
          color="grey-9"
          v-if="user.verificaPermissaoUsuario('Publico')"
          @click="
            (dialogNovaConta = true),
              (editarConta = false),
              (modelContaBancaria = {})
          "
        />
      </div>
    </q-card-section>

    <q-list separator>
      <template
        v-for="contas in sPessoa.item?.PessoaContaS"
        v-bind:key="contas.codpessoaconta"
      >
        <q-item>
          <q-item-section avatar>
            <q-btn
              round
              flat
              :icon="contas.agencia !== null ? 'account_balance' : 'pix'"
              color="primary"
            />
          </q-item-section>

          <q-item-section>
            <q-item-label :class="contas.inativo ? 'text-strike' : null">
              <span v-if="contas.agencia && contas.conta">
                {{ contas.tipo == 1 ? "Corrente" : "Poupança" }},
                {{ contas.nomeBanco }}, {{ contas.banco }},
                {{ contas.agencia }}, {{ contas.conta }}
              </span>
              <span v-if="contas.pixcpf">
                {{
                  Documentos.formataCPF(
                    contas.pixcpf.toString().padStart(11, "0")
                  )
                }}
              </span>
              <span v-if="contas.pixcnpj">
                {{
                  Documentos.formataCNPJ(
                    contas.pixcnpj.toString().padStart(14, "0")
                  )
                }}
              </span>
              <span v-if="contas.pixtelefone">
                {{ Documentos.formataCelularComDDD(contas.pixtelefone) }}
              </span>
              <span v-if="contas.pixemail">{{ contas.pixemail }}</span>
              <span v-if="contas.pixaleatoria">{{ contas.pixaleatoria }}</span>

              <!-- INFO -->
              <icone-info-criacao
                :usuariocriacao="contas.usuariocriacao"
                :criacao="contas.criacao"
                :usuarioalteracao="contas.usuarioalteracao"
                :alteracao="contas.alteracao"
              />
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
            <q-item-label caption class="text-red-14" v-if="contas.inativo">
              Inativo {{ Documentos.formataFromNow(contas.inativo) }}
            </q-item-label>
          </q-item-section>

          <q-item-section side>
            <q-item-label
              caption
              v-if="user.verificaPermissaoUsuario('Publico')"
            >
              <!-- EDITAR -->
              <q-btn
                flat
                dense
                round
                icon="edit"
                size="sm"
                color="grey-7"
                @click="
                  editarContaBancaria(
                    contas.codpessoaconta,
                    contas.banco,
                    contas.cnpj,
                    contas.agencia,
                    contas.conta,
                    contas.pixcpf,
                    contas.pixcnpj,
                    contas.pixtelefone,
                    contas.pixemail,
                    contas.pixaleatoria,
                    contas.tipo,
                    contas.titular,
                    contas.observacoes
                  )
                "
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>

              <!-- INATIVAR -->
              <q-btn
                v-if="!contas.inativo"
                flat
                dense
                round
                icon="pause"
                size="sm"
                color="grey-7"
                @click="inativar(contas.codpessoaconta)"
              >
                <q-tooltip>Inativar</q-tooltip>
              </q-btn>

              <!-- ATIVAR -->
              <q-btn
                v-if="contas.inativo"
                flat
                dense
                round
                icon="play_arrow"
                size="sm"
                color="grey-7"
                @click="ativar(contas.codpessoaconta)"
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
                @click="excluirConta(contas.codpessoaconta)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>
  </q-card>

  <!-- Dialog nova conta -->
  <q-dialog v-model="dialogNovaConta">
    <q-card style="min-width: 350px">
      <q-form
        @submit="editarConta == false ? novaContaBancaria() : salvarConta()"
      >
        <q-card-section>
          <div v-if="editarConta" class="text-h6">Editar Conta Bancária</div>
          <div v-else class="text-h6">Nova Conta Bancária</div>
        </q-card-section>
        <q-card-section>
          <div class="q-col-gutter-md">
            <q-radio
              v-model="modelContaBancaria.radio"
              checked-icon="task_alt"
              unchecked-icon="panorama_fish_eye"
              val="bancaria"
              label="Conta Bancária"
            />
            <q-radio
              v-model="modelContaBancaria.radio"
              checked-icon="task_alt"
              unchecked-icon="panorama_fish_eye"
              val="pixcpf"
              label="Pix Cpf"
            />
            <q-radio
              v-model="modelContaBancaria.radio"
              checked-icon="task_alt"
              unchecked-icon="panorama_fish_eye"
              val="pixcnpj"
              label="Pix Cnpj"
            />
            <q-radio
              v-model="modelContaBancaria.radio"
              checked-icon="task_alt"
              unchecked-icon="panorama_fish_eye"
              val="pixtelefone"
              label="Pix Telefone"
            />
            <q-radio
              v-model="modelContaBancaria.radio"
              checked-icon="task_alt"
              unchecked-icon="panorama_fish_eye"
              val="pixemail"
              label="Pix Email"
            />
            <q-radio
              v-model="modelContaBancaria.radio"
              checked-icon="task_alt"
              unchecked-icon="panorama_fish_eye"
              val="pixaleatoria"
              label="Pix Aleatória"
            />

            <select-banco
              v-model="modelContaBancaria.banco"
              v-if="modelContaBancaria.radio === 'bancaria'"
              :model-select-banco="modelContaBancaria.codbanco"
              :banco-editar="optionsContaEditar"
              :rules="[
                (val) =>
                  (modelContaBancaria.radio === 'bancaria' &&
                    val !== null &&
                    val !== undefined) ||
                  'Banco Obrigatório',
              ]"
            />

            <q-select
              outlined
              v-model="modelContaBancaria.tipo"
              v-if="modelContaBancaria.radio === 'bancaria'"
              :options="[
                { label: 'Conta Corrente', value: 1 },
                { label: 'Conta Poupança', value: 2 },
              ]"
              map-options
              emit-value
              option-value="value"
              option-label="label"
              label="Tipo"
              :rules="[
                (val) =>
                  (modelContaBancaria.radio === 'bancaria' &&
                    val !== null &&
                    val !== undefined) ||
                  'Tipo Obrigatório',
              ]"
            />

            <div class="row">
              <div
                class="col-6 q-pr-md"
                v-if="modelContaBancaria.radio === 'bancaria'"
              >
                <q-input
                  outlined
                  v-model="modelContaBancaria.agencia"
                  label="Agência"
                  step="any"
                  :rules="[
                    (val) =>
                      (modelContaBancaria.radio === 'bancaria' &&
                        val !== null &&
                        val !== undefined) ||
                      'Agência Obrigatória',
                  ]"
                />
              </div>
              <div
                class="col-6"
                v-if="modelContaBancaria.radio === 'bancaria'"
              >
                <q-input
                  outlined
                  v-model="modelContaBancaria.conta"
                  label="Conta"
                  step="any"
                  :rules="[
                    (val) =>
                      (modelContaBancaria.radio === 'bancaria' &&
                        val !== null &&
                        val !== undefined) ||
                      'Conta Obrigatória',
                  ]"
                />
              </div>

              <div
                class="col-6 q-pr-md q-pt-md"
                v-if="modelContaBancaria.radio === 'pixcpf'"
              >
                <q-input
                  outlined
                  v-model="modelContaBancaria.pixcpf"
                  label="Pix CPF"
                  mask="###.###.###-##"
                  type="text"
                  unmasked-value
                  reactive-rules
                  :rules="[
                    (val) =>
                      (modelContaBancaria.radio === 'pixcpf' &&
                        val !== null &&
                        val !== undefined) ||
                      'Pix Cpf Obrigatório',
                  ]"
                />
              </div>

              <div
                class="col-6 q-pt-md"
                v-if="modelContaBancaria.radio === 'pixcnpj'"
              >
                <q-input
                  outlined
                  v-model="modelContaBancaria.pixcnpj"
                  label="Pix cnpj"
                  mask="##.###.###/####-##"
                  type="text"
                  unmasked-value
                  :rules="[
                    (val) =>
                      (modelContaBancaria.radio === 'pixcnpj' &&
                        val !== null &&
                        val !== undefined) ||
                      'Pix Cnpj Obrigatório',
                  ]"
                />
              </div>
              <div
                class="col-6 q-pr-md q-pt-md"
                v-if="modelContaBancaria.radio === 'pixtelefone'"
              >
                <q-input
                  outlined
                  v-model="modelContaBancaria.pixtelefone"
                  label="Pix telefone"
                  type="text"
                  mask="(##) # ####-####"
                  :rules="[
                    (val) =>
                      (modelContaBancaria.radio === 'pixtelefone' &&
                        val !== null &&
                        val !== undefined) ||
                      'Pix telefone Obrigatório',
                  ]"
                  unmasked-value
                />
              </div>
              <div
                class="col-6 q-pt-md"
                v-if="modelContaBancaria.radio === 'pixemail'"
              >
                <q-input
                  outlined
                  v-model="modelContaBancaria.pixemail"
                  label="Pix email"
                  type="text"
                  :rules="[
                    (val) =>
                      (modelContaBancaria.radio === 'pixemail' &&
                        val !== null &&
                        val !== undefined) ||
                      'Pix Email Obrigatório',
                  ]"
                />
              </div>
            </div>
            <q-input
              v-if="modelContaBancaria.radio === 'pixaleatoria'"
              outlined
              v-model="modelContaBancaria.pixaleatoria"
              label="Pix chave aleatória"
              :rules="[
                (val) =>
                  (modelContaBancaria.radio === 'pixaleatoria' &&
                    val !== null &&
                    val !== undefined) ||
                  'Pix Aleatória Obrigatório',
              ]"
              type="text"
            />

            <q-input
              outlined
              v-model="modelContaBancaria.cnpj"
              v-if="modelContaBancaria.radio === 'bancaria'"
              label="Cnpj/Cpf"
              step="any"
            />
            <input-filtered
              outlined
              v-model="modelContaBancaria.titular"
              label="Titular"
              step="any"
            />

            <q-input
              outlined
              autogrow
              bordeless
              v-model="modelContaBancaria.observacoes"
              class="q-pt-md"
              label="Observações"
              type="textarea"
            />
          </div>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
  </div>
</template>

<style scoped></style>
