<script setup>
import { onBeforeUnmount, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { ref } from "vue";
import { guardaToken } from "src/stores";
import { pessoaStore } from "stores/pessoa";
import { api } from "src/boot/axios";
import { formataFromNow, formataDataCompleta } from "src/utils/formatador";

const $q = useQuasar();
const sPessoa = pessoaStore();
const user = guardaToken();
const permitido = user.verificaPermissaoUsuario("Financeiro");

const filtro = ref("ativos");

const files = ref(null);
const uploadProgress = ref([]);
const uploading = ref(null);

const dialogEditar = ref(false);
const dialogUpload = ref(false);
const model = ref({});

const arquivos = ref({
  ativos: [],
  inativos: [],
});

const fileSize = (bytes) => {
  if (bytes == 0) {
    return "0.00 B";
  }
  var e = Math.floor(Math.log(bytes) / Math.log(1024));
  return (bytes / Math.pow(1024, e)).toFixed(2) + " " + " KMGTP".charAt(e) + "B";
};

const cleanUp = () => {
  clearTimeout(uploading.value);
  buscarListagem();
};

const cancelFile = (index) => {
  uploadProgress.value[index] = {
    ...uploadProgress.value[index],
    error: true,
    color: "orange-2",
  };
};

const updateFiles = (newFiles) => {
  files.value = newFiles;
  uploadProgress.value = (newFiles || []).map((file) => ({
    error: false,
    color: "green-2",
    percent: 0,
    icon:
      file.type.indexOf("video/") === 0
        ? "movie"
        : file.type.indexOf("image/") === 0
          ? "mdi-file-image"
          : file.type.indexOf("application/pdf") === 0
            ? "mdi-file-pdf"
            : "mdi-file",
  }));
};

const getBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
  });
};

const uploadFile = async (index) => {
  const file = files.value[index];
  const progress = uploadProgress.value[index];
  try {
    const base64 = await getBase64(file);
    const payload = {
      nome: file.name,
      base64: base64,
    };
    const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo`;
    const ret = await api.post(url, payload);
    arquivos.value = ret.data.data;
    progress.percent = 0.5;
    setTimeout(() => {
      progress.color = "green-2";
      progress.percent = 1;
      progress.error = false;
    }, 1000);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: "Falha ao carregar os anexos!",
    });
    setTimeout(() => {
      progress.color = "red-2";
      progress.percent = 1;
      progress.error = true;
    }, 1000);
  }
};

const upload = async () => {
  cleanUp();
  const promises = [];
  for (let index = 0; index < files.value.length; index++) {
    const progress = uploadProgress.value[index];
    progress.color = "yellow-8";
    progress.percent = 0;
    progress.error = false;
    promises.push(uploadFile(index));
  }
  await Promise.all(promises);
  const temErro = uploadProgress.value.some((p) => p.error);
  if (!temErro) {
    dialogUpload.value = false;
  }
};

const icone = (arquivo) => {
  switch (arquivo.tipo) {
    case "pdf":
      return "mdi-file-pdf";
    case "jpg":
    case "jpeg":
    case "png":
      return "mdi-file-image";
    default:
      return "mdi-file";
  }
};

const abrir = async (arquivo) => {
  const status = filtro.value;
  const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/${status}/${arquivo.nome}`;
  const response = await api.get(url, {
    responseType: "blob",
  });
  window.open(URL.createObjectURL(response.data));
};

const editar = (arquivo) => {
  model.value = { ...arquivo };
  dialogEditar.value = true;
};

const salvar = async (evt) => {
  evt.preventDefault();

  $q.dialog({
    title: "Salvar",
    message: "Tem certeza que deseja salvar as alterações?",
    cancel: true,
  }).onOk(async () => {
    try {
      const payload = {
        label: model.value.label,
        observacoes: model.value.observacoes,
      };
      const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/ativo/${model.value.nome}`;
      const ret = await api.put(url, payload);
      arquivos.value = ret.data.data;
      dialogEditar.value = false;
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Falha ao salvar!",
      });
    }
  });
};

const inativar = (arquivo) => {
  $q.dialog({
    title: "Excluir",
    message: "Tem certeza que deseja mover o arquivo para lixeira?",
    cancel: true,
  }).onOk(async () => {
    try {
      const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/ativo/${arquivo.nome}`;
      const ret = await api.delete(url);
      arquivos.value = ret.data.data;
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Falha ao mover o arquivo para lixeira!",
      });
    }
  });
};

const restaurar = (arquivo) => {
  $q.dialog({
    title: "Restaurar",
    message: "Deseja restaurar o arquivo?",
    cancel: true,
  }).onOk(async () => {
    try {
      const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/inativo/${arquivo.nome}`;
      const ret = await api.patch(url);
      arquivos.value = ret.data.data;
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Falha ao restaurar o arquivo!",
      });
    }
  });
};

const excluir = (arquivo) => {
  $q.dialog({
    title: "Excluir",
    message:
      "Tem certeza que deseja excluir permantentemente o arquivo? Essa operação não poderá ser desfeita!",
    cancel: true,
  }).onOk(async () => {
    try {
      const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/inativo/${arquivo.nome}`;
      const ret = await api.delete(url);
      arquivos.value = ret.data.data;
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Falha ao excluir o arquivo!",
      });
    }
  });
};

const isUploading = computed(() => uploading.value !== null);

const canUpload = computed(() => files.value !== null);

const abrirUpload = () => {
  files.value = null;
  uploadProgress.value = [];
  dialogUpload.value = true;
};

onBeforeUnmount(() => {
  clearTimeout(uploading.value);
});

onMounted(() => {
  buscarListagem();
});

watch(
  () => sPessoa.item,
  () => {
    buscarListagem();
  }
);

const buscarListagem = async () => {
  if (!sPessoa.item) return;
  try {
    const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo`;
    const ret = await api.get(url);
    arquivos.value = ret.data.data;
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: "Falha ao buscar a listagem dos anexos!",
    });
  }
};
</script>

<template>
  <!-- Dialog Upload -->
  <q-dialog v-model="dialogUpload">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        NOVO ANEXO
      </q-card-section>

      <q-separator inset />

      <q-card-section>
        <q-file
          :model-value="files"
          @update:model-value="updateFiles"
          label="Selecione os arquivos"
          outlined
          multiple
          :clearable="!isUploading"
          style="width: 100%; max-width: 100%"
          counter
        >
          <template v-slot:file="{ index, file }">
            <q-chip
              class="full-width q-my-xs"
              :removable="isUploading && uploadProgress[index].percent < 1"
              square
              @remove="cancelFile(index)"
            >
              <q-linear-progress
                class="absolute-full full-height"
                :value="uploadProgress[index].percent"
                :color="uploadProgress[index].color"
                track-color="grey-2"
              />

              <q-avatar>
                <q-icon :name="uploadProgress[index].icon" />
              </q-avatar>

              <div class="ellipsis relative-position">
                {{ file.name }}
              </div>

              <q-tooltip>
                {{ file.name }}
              </q-tooltip>
            </q-chip>
          </template>
        </q-file>
      </q-card-section>

      <q-separator inset />

      <q-card-actions align="right" class="text-primary">
        <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
        <q-btn
          flat
          label="Enviar"
          @click="upload"
          :disable="!canUpload"
          :loading="isUploading"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <!-- Dialog Editar -->
  <q-dialog v-model="dialogEditar">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-form @submit="salvar">
        <q-card-section class="text-grey-9 text-overline row items-center">
          DETALHES DO ARQUIVO
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <q-input
            outlined
            v-model="model.label"
            autofocus
            label="Nome"
            :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
          />
          <q-input
            outlined
            v-model="model.observacoes"
            label="Observações"
            type="textarea"
          />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- Card Principal -->
  <q-card bordered flat>
    <q-card-section class="text-grey-9 text-overline row items-center">
      ANEXOS
      <q-space />
      <q-btn-toggle
        v-model="filtro"
        color="grey-3"
        toggle-color="primary"
        text-color="grey-7"
        toggle-text-color="grey-3"
        unelevated
        dense
        no-caps
        size="sm"
        :options="[
          { label: 'Ativos', value: 'ativos' },
          { label: 'Lixeira', value: 'inativos' },
        ]"
      />
      <q-btn
        flat
        round
        dense
        icon="add"
        size="sm"
        color="primary"
        v-if="permitido"
        @click="abrirUpload()"
      >
        <q-tooltip>Novo anexo</q-tooltip>
      </q-btn>
    </q-card-section>

    <!-- ATIVOS -->
    <template v-if="filtro === 'ativos'">
      <q-list v-if="arquivos.ativos.length > 0">
        <template v-for="arquivo in arquivos.ativos" :key="arquivo.nome">
          <q-separator inset />
          <q-item>
            <q-item-section avatar>
              <q-btn
                round
                flat
                :icon="icone(arquivo)"
                color="primary"
                @click="abrir(arquivo)"
              />
            </q-item-section>

            <q-item-section class="cursor-pointer" @click="abrir(arquivo)">
              <q-item-label>{{ arquivo.label }}</q-item-label>
              <q-item-label
                caption
                v-if="arquivo.observacoes"
                style="white-space: pre-wrap"
              >
                {{ arquivo.observacoes }}
              </q-item-label>
              <q-item-label caption>
                {{ fileSize(arquivo.size) }} |
                {{ formataFromNow(arquivo.lastModified) }} |
                {{ formataDataCompleta(arquivo.lastModified) }}
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-item-label caption v-if="permitido">
                <q-btn
                  flat
                  dense
                  round
                  icon="edit"
                  size="sm"
                  color="grey-7"
                  @click="editar(arquivo)"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  icon="delete"
                  size="sm"
                  color="grey-7"
                  @click="inativar(arquivo)"
                >
                  <q-tooltip>Mover para lixeira</q-tooltip>
                </q-btn>
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
      </q-list>
      <div v-else class="q-pa-md text-center text-grey">
        Nenhum anexo
      </div>
    </template>

    <!-- INATIVOS -->
    <template v-if="filtro === 'inativos'">
      <q-list v-if="arquivos.inativos.length > 0">
        <template v-for="arquivo in arquivos.inativos" :key="arquivo.nome">
          <q-separator inset />
          <q-item>
            <q-item-section avatar>
              <q-btn
                round
                flat
                :icon="icone(arquivo)"
                color="primary"
                @click="abrir(arquivo)"
              />
            </q-item-section>

            <q-item-section class="cursor-pointer" @click="abrir(arquivo)">
              <q-item-label>{{ arquivo.label }}</q-item-label>
              <q-item-label
                caption
                v-if="arquivo.observacoes"
                style="white-space: pre-wrap"
              >
                {{ arquivo.observacoes }}
              </q-item-label>
              <q-item-label caption>
                {{ fileSize(arquivo.size) }} |
                {{ formataFromNow(arquivo.lastModified) }} |
                {{ formataDataCompleta(arquivo.lastModified) }}
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-item-label caption v-if="permitido">
                <q-btn
                  flat
                  dense
                  round
                  icon="mdi-restore"
                  size="sm"
                  color="grey-7"
                  @click="restaurar(arquivo)"
                >
                  <q-tooltip>Restaurar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  icon="mdi-delete-forever"
                  size="sm"
                  color="grey-7"
                  @click="excluir(arquivo)"
                >
                  <q-tooltip>Excluir permanentemente</q-tooltip>
                </q-btn>
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
      </q-list>
      <div v-else class="q-pa-md text-center text-grey">
        Lixeira vazia
      </div>
    </template>
  </q-card>
</template>

<style scoped></style>
