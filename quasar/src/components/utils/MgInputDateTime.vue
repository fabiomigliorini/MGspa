<template>
  <div>
    <q-input options outlined v-model="model" :label="label" mask="##/##/#### ##:##:##" :error-message="errorMessage" :error="error" @change="change()">
      <template v-slot:prepend>
        <q-icon name="event" class="cursor-pointer">
          <q-popup-proxy transition-show="scale" transition-hide="scale">
            <q-date v-model="model" mask="DD/MM/YYYY HH:mm:ss" @input='change()'>
              <div class="row items-center justify-end">
                <q-btn v-close-popup label="Close" color="primary" flat />
              </div>
            </q-date>
          </q-popup-proxy>
        </q-icon>
      </template>
      <template v-slot:append>
        <q-icon name="access_time" class="cursor-pointer">
          <q-popup-proxy transition-show="scale" transition-hide="scale">
            <q-time v-model="model" mask="DD/MM/YYYY HH:mm:ss" format24h @input='change()' with-seconds>
              <div class="row items-center justify-end">
                <q-btn v-close-popup label="Close" color="primary" flat />
              </div>
            </q-time>
          </q-popup-proxy>
        </q-icon>
      </template>
      <template v-slot:error>
          <slot name="error"/>
      </template>
    </q-input>
  </div>
</template>

<script>
import { date } from 'quasar'

export default {
  name: 'mg-input-date-time',
  props: {
    label: {
      type: String,
      required: false,
      default: 'Data e Hora'
    },
    value: {
      required: false,
      default: null
    },
    errorMessage: {
      type: String,
      required: false,
      default: null
    },
    error: {
      type: Boolean,
      required: false,
      default: false
    },
    filtroDfe: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  data () {
    return {
      model: null,
      options: [],
    }
  },
  methods: {

    // ao selecionar retorna value
    change () {
      try {
        var timeStamp = date.extractDate(this.model, 'DD/MM/YYYY HH:mm:ss');
        this.$emit('input', date.formatDate(timeStamp, 'YYYY-MM-DD HH:mm:ss'));
      }
      catch (exception) {
        this.$emit('input', null)
        console.log(exception)
      }
    },

  },
  created() {
  },
  mounted() {
    if (!this.value) {
      this.model = null;
      return;
    }
    var timeStamp = date.extractDate(this.value, 'YYYY-MM-DD HH:mm:ss');
    this.model = date.formatDate(timeStamp, 'DD/MM/YYYY HH:mm:ss');
  }
}
</script>

<style>
</style>
