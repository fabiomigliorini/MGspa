<script setup>
import { ref } from "vue";
import { negocioStore } from "stores/negocio";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");
import MgSlim from "../../utils/pqina/slim/MgSlim.vue";
import { Dialog, Notify } from "quasar";

const sNegocio = negocioStore();

const arquivosPdf = ref([]);

const urlAnexo = (pasta, anexo) => {
  const url =
    process.env.API_BASE_URL +
    "/api/v1/pdv/negocio/" +
    sNegocio.negocio.codnegocio +
    "/anexo/" +
    pasta +
    "/" +
    anexo;
  return url;
};

const excluirAnexo = (pasta, anexo) => {
  Dialog.create({
    title: "Excluir",
    message: "Tem certeza que você deseja excluir esse anexo?",
    cancel: true,
  }).onOk(() => {
    sNegocio.deleteAnexo(pasta, anexo);
  });
};

const getBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
  });
};

const anexarPdf = async () => {
  arquivosPdf.value.forEach(async (arquivo) => {
    const base64 = await getBase64(arquivo);
    await sNegocio.uploadAnexo("pdf", base64);
  });
  arquivosPdf.value = [];
};

const onRejected = (rejectedEntries) => {
  Notify.create({
    type: "negative",
    message: `${rejectedEntries.length} file(s) did not pass validation constraints`,
  });
};
</script>
<template>
  <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
    <div class="row">
      <q-card>
        <div style="height: 100px">
          <mg-slim />
        </div>
      </q-card>
    </div>
    <div class="row">
      <q-card>
        <div style="height: 100px">
          <mg-slim />
        </div>
      </q-card>
    </div>
    <div class="row">
      <q-file
        style="max-width: 100%"
        v-model="arquivosPdf"
        outlined
        label="Anexar PDF"
        multiple
        use-chips
        accept=".pdf"
        max-file-size="1024000"
        @rejected="onRejected"
        counter
      >
        <template v-slot:append>
          <q-btn
            round
            dense
            flat
            icon="cloud_upload"
            color="primary"
            v-if="arquivosPdf.length > 0"
            @click="anexarPdf"
          />
        </template>
      </q-file>
    </div>
  </div>

  <!-- ASSINATURA CONFISSAO DE DIVIDA -->
  <div
    v-for="anexo in sNegocio.negocio.anexos.confissao"
    :key="anexo"
    class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
  >
    <q-card>
      <q-item :href="urlAnexo('confissao', anexo)" target="_blank">
        <q-item-section avatar>
          <q-avatar
            icon="mdi-signature-freehand"
            color="primary"
            text-color="white"
          />
        </q-item-section>

        <q-item-section>
          <q-item-label class="ellipsis">Assinatura</q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />
      <q-item
        :href="urlAnexo('confissao', anexo)"
        target="_blank"
        style="height: 250px"
        class="q-pa-none"
      >
        <q-img :src="urlAnexo('confissao', anexo)" fit="cover" />
      </q-item>
      <q-card-actions>
        <q-btn-group flat>
          <q-btn
            dense
            flat
            round
            icon="delete"
            color="negative"
            @click="excluirAnexo('confissao', anexo)"
          >
            <q-tooltip class="bg-accent">Excluir</q-tooltip>
          </q-btn>
        </q-btn-group>
      </q-card-actions>
    </q-card>
  </div>

  <!-- OUTRAS IMAGENS -->
  <div
    v-for="anexo in sNegocio.negocio.anexos.imagem"
    :key="anexo"
    class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
  >
    <q-card>
      <q-item :href="urlAnexo('imagem', anexo)" target="_blank">
        <q-item-section avatar>
          <q-avatar icon="mdi-file-image" color="primary" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label class="ellipsis">Imagem</q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />
      <q-item
        :href="urlAnexo('imagem', anexo)"
        target="_blank"
        style="height: 250px"
        class="q-pa-none"
      >
        <q-img :src="urlAnexo('imagem', anexo)" fit="cover" />
      </q-item>
      <q-card-actions>
        <q-btn-group flat>
          <q-btn
            dense
            flat
            round
            icon="delete"
            color="negative"
            @click="excluirAnexo('imagem', anexo)"
          >
            <q-tooltip class="bg-accent">Excluir</q-tooltip>
          </q-btn>
        </q-btn-group>
      </q-card-actions>
    </q-card>
  </div>

  <!-- PDF -->
  <div
    v-for="anexo in sNegocio.negocio.anexos.pdf"
    :key="anexo"
    class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
  >
    <q-card>
      <q-item :href="urlAnexo('pdf', anexo)" target="_blank">
        <q-item-section avatar>
          <q-avatar
            icon="mdi-file-document"
            color="primary"
            text-color="white"
          />
        </q-item-section>

        <q-item-section>
          <q-item-label class="ellipsis">PDF</q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />
      <q-item
        :href="urlAnexo('pdf', anexo)"
        target="_blank"
        style="height: 250px"
        class="q-pa-none"
      >
        <!-- <q-img :src="urlAnexo('pdf', anexo)" fit="cover" /> -->
        <q-icon
          name="picture_as_pdf"
          color="grey-7"
          size="150px"
          style="margin: auto"
        />
      </q-item>
      <q-card-actions>
        <q-btn-group flat>
          <q-btn
            dense
            flat
            round
            icon="delete"
            color="negative"
            @click="excluirAnexo('pdf', anexo)"
          >
            <q-tooltip class="bg-accent">Excluir</q-tooltip>
          </q-btn>
        </q-btn-group>
      </q-card-actions>
    </q-card>
  </div>
  <!--
  <div
    v-for="anexo in sNegocio.negocio.anexos.imagem"
    :key="anexo"
    class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
  >
    <q-card>
      <q-img :src="urlAnexo('imagem', anexo)" fit="fit">
        <div class="absolute-bottom-right text-right q-gutter-sm">
          <q-btn
            round
            color="primary"
            icon="visibility"
            :href="urlAnexo('imagem', anexo)"
            target="_blank"
          />
          <q-btn
            round
            color="negative"
            icon="delete"
            @click="excluirAnexo('imagem', anexo)"
          />
        </div>
      </q-img>
    </q-card>
    <div
      v-for="anexo in sNegocio.negocio.anexos.pdf"
      :key="anexo"
      class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
    >
      <q-card>
         <q-img>
          <div class="absolute-bottom-right text-right q-gutter-sm">
            <q-btn
              round
              color="primary"
              icon="visibility"
              :href="urlAnexo('pdf', anexo)"
              target="_blank"
            />
            <q-btn
              round
              color="negative"
              icon="delete"
              @click="excluirAnexo('pdf', anexo)"
            />
          </div>
        </q-img>
      </q-card>
    </div>
  </div> -->
</template>
