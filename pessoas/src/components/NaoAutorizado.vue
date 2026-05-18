<template>
  <div class="text-wrapper">
    <div class="title" data-content="404">403 - Não Autorizado!</div>

    <div class="subtitle">Oops, Você não tem permissão para acessar essa página!</div>

    <div class="buttons">
      <a class="button" href="#/">Voltar</a>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'NaoAutorizado',

  setup() {},
})
</script>

<style scoped>
@import url(https://fonts.googleapis.com/css?family=Raleway:700);

*,
*:before,
*:after {
  box-sizing: border-box;
}
html {
  height: 100%;
}
body {
  font-family: 'Raleway', sans-serif;
  background-color: #342643;
  height: 100%;
  padding: 10px;
}

a {
  color: #ee4b5e !important;
  text-decoration: none;
}
a:hover {
  color: #ffffff !important;
  text-decoration: none;
}

.text-wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.title {
  font-size: 5em;
  font-weight: 700;
  color: #ee4b5e;
}

.subtitle {
  font-size: 40px;
  font-weight: 700;
  color: #1fa9d6;
}
.isi {
  font-size: 18px;
  text-align: center;
  margin: 30px;
  padding: 20px;
  color: white;
}
.buttons {
  margin: 30px;
  font-weight: 700;
  border: 2px solid #ee4b5e;
  text-decoration: none;
  padding: 15px;
  text-transform: uppercase;
  color: #ee4b5e;
  border-radius: 26px;
  transition: all 0.2s ease-in-out;
  display: inline-block;

  .buttons:hover {
    background-color: #ee4b5e;
    color: white;
    transition: all 0.2s ease-in-out;
  }
}
</style>
