<template>
  <div class="q-pa-md">
    <div class="text-h6 q-mb-md">Detalhes da Nota</div>

    <q-list bordered separator>
      <q-item>
        <q-item-section>
          <q-item-label overline>Número</q-item-label>
          <q-item-label>000123</q-item-label>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label overline>Série</q-item-label>
          <q-item-label>1</q-item-label>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label overline>Data Emissão</q-item-label>
          <q-item-label>27/12/2024</q-item-label>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label overline>Valor Total</q-item-label>
          <q-item-label class="text-h6 text-positive">R$ 1.500,00</q-item-label>
        </q-item-section>
      </q-item>
    </q-list>

    <q-separator class="q-my-md" />

    <div class="q-gutter-sm">
      <q-btn color="primary" label="Visualizar XML" icon="code" class="full-width" />
      <q-btn color="positive" label="Download PDF" icon="picture_as_pdf" class="full-width" />
      <q-btn color="negative" label="Cancelar Nota" icon="cancel" class="full-width" />
    </div>
  </div>
</template>
