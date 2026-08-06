// Configuration for your app
// https://v2.quasar.dev/quasar-cli-vite/quasar-config-file

import { defineConfig } from '#q-app/wrappers'
import path from 'node:path'
import { execSync } from 'node:child_process'
import { writeFileSync } from 'node:fs'
import pkg from './package.json' with { type: 'json' }

function gitCommitNumber() {
  if (process.env.COMMIT_NUMBER) return process.env.COMMIT_NUMBER
  try {
    return execSync("git -c safe.directory='*' rev-list --count HEAD -- . ../components", {
      cwd: import.meta.dirname,
    })
      .toString()
      .trim()
  } catch {
    return ''
  }
}

// Avaliados UMA vez, no escopo de módulo: build.env e afterBuild precisam do
// mesmo valor — o BUILD_ID assado no bundle tem que bater com o do version.json.
const BUILD_DATE = new Date().toISOString()
const COMMIT_NUMBER = gitCommitNumber()
const BUILD_ID = `${pkg.version}+${COMMIT_NUMBER}.${BUILD_DATE}`

export default defineConfig((/* ctx */) => {
  return {
    // https://v2.quasar.dev/quasar-cli-vite/prefetch-feature
    // preFetch: true,

    // app boot file (/src/boot)
    boot: ['axios'],

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#css
    css: ['app.scss'],

    // https://github.com/quasarframework/quasar/tree/dev/extras
    extras: [
      // 'ionicons-v4',
      'mdi-v7',
      // 'fontawesome-v6',
      // 'eva-icons',
      // 'themify',
      // 'line-awesome',
      // 'roboto-font-latin-ext',

      'roboto-font',
      'material-icons',
    ],

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#build
    build: {
      target: {
        browser: ['es2022', 'firefox115', 'chrome115', 'safari16'],
        node: 'node22',
      },

      env: {
        APP_NAME: pkg.name.toLowerCase(),
        APP_VERSION: pkg.version,
        BUILD_DATE,
        COMMIT_NUMBER,
        BUILD_ID,
      },

      // Roda DEPOIS do workbox (o build.js do @quasar/app-vite chama afterBuild
      // só quando appBuilder.build() resolve), então version.json não entra no
      // globPatterns ['**/*'] do GenerateSW e nunca é pré-cacheado — é sempre
      // buscado da rede. É a fonte da verdade que pwaAtualizacao.js compara com
      // o BUILD_ID do bundle.
      afterBuild({ quasarConf }) {
        writeFileSync(
          path.join(quasarConf.build.distDir, 'version.json'),
          JSON.stringify({
            buildId: BUILD_ID,
            appName: pkg.name.toLowerCase(),
            appVersion: pkg.version,
            buildDate: BUILD_DATE,
            commitNumber: COMMIT_NUMBER,
          }),
          'utf-8',
        )
      },

      alias: {
        '@components': path.resolve(import.meta.dirname, '../components'),
        'quasar/src': path.resolve(import.meta.dirname, 'node_modules/quasar/src'),
        'vue-router': path.resolve(import.meta.dirname, 'node_modules/vue-router'),
        // Shared stores in ../components importam 'pinia'; como estão fora da raiz do
        // app, o resolver do build (Rolldown) não acha o pinia. Aliasa pro local.
        pinia: path.resolve(import.meta.dirname, 'node_modules/pinia'),
      },

      vueRouterMode: 'history',

      vitePlugins: [
        [
          'vite-plugin-checker',
          {
            eslint: {
              lintCommand: 'eslint -c ./eslint.config.js "./src*/**/*.{js,mjs,cjs,vue}"',
              useFlatConfig: true,
            },
          },
          { server: false },
        ],
      ],
    },

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#devserver
    devServer: {
      https: true,
      port: 8081,
      open: false,
      host: '0.0.0.0',
      client: {
        overlay: {
          warnings: false,
          errors: true,
        },
      },
      hmr: {
        protocol: 'wss',
        host: 'sistema-dev.mgpapelaria.com.br',
        port: 8081,
      },
    },

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#framework
    framework: {
      config: {},

      iconSet: 'material-icons',
      lang: 'pt-BR',

      // components: [],
      // directives: [],

      plugins: ['Notify', 'Dialog', 'Loading', 'LoadingBar'],
    },

    animations: [],

    // https://v2.quasar.dev/quasar-cli-vite/developing-ssr/configuring-ssr
    ssr: {
      prodPort: 3000,
      middlewares: ['render'],
      pwa: false,
    },

    // https://v2.quasar.dev/quasar-cli-vite/developing-pwa/configuring-pwa
    pwa: {
      workboxMode: 'GenerateSW',
      injectPwaMetaTags: true,
      swFilename: 'sw.js',
      manifestFilename: 'manifest.json',
      useCredentialsForManifestTag: false,

      // FASE 2 (deploy SEGUINTE, só depois que a Fase 1 estiver publicada em
      // todos os apps): virar os dois para false. Isso faz o workbox gerar o
      // listener de SKIP_WAITING no sw.js — o template emite self.skipWaiting()
      // OU o listener, nunca os dois — e deixa a aba já aberta servida pelo SW
      // e precache antigos, eliminando o 404 de chunk lazy. Virar agora
      // quebraria quem ainda roda o código antigo: ficaria com um worker em
      // waiting que o reload puro não ativa.
      // (o @quasar/app-vite já aplica true nos dois por padrão)
      extendGenerateSWOptions(cfg) {
        cfg.skipWaiting = true
        cfg.clientsClaim = true
      },
    },

    cordova: {
      // noIosLegacyBuildFlag: true,
    },

    capacitor: {
      hideSplashscreen: true,
    },

    electron: {
      inspectPort: 5858,
      bundler: 'packager',
      packager: {},
      builder: {
        appId: 'pessoas',
      },
    },

    bex: {
      extraScripts: [],
    },
  }
})
