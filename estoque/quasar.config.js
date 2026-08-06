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
    // --> boot files are part of "main.js"
    // https://v2.quasar.dev/quasar-cli-vite/boot-files
    boot: ['axios', 'errorHandler'],

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#css
    css: ['app.scss'],

    // https://github.com/quasarframework/quasar/tree/dev/extras
    extras: [
      // 'ionicons-v4',
      // 'mdi-v7',
      // 'fontawesome-v6',
      // 'eva-icons',
      // 'themify',
      // 'line-awesome',
      // 'roboto-font-latin-ext', // this or either 'roboto-font', NEVER both!

      'roboto-font', // optional, you are not bound to it
      'material-icons', // optional, you are not bound to it
    ],

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#build
    build: {
      target: {
        browser: ['es2022', 'firefox115', 'chrome115', 'safari16'],
        node: 'node20',
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

      vueRouterMode: 'history', // available values: 'hash', 'history'
      // vueRouterBase,
      // vueDevtools,
      // vueOptionsAPI: false,

      // rebuildCache: true, // rebuilds Vite/linter/etc cache on startup

      // publicPath: '/',
      // analyze: true,
      // env: {},
      // rawDefine: {}
      // ignorePublicFolder: true,
      // minify: false,
      // polyfillModulePreload: true,
      // distDir

      // extendViteConf (viteConf) {},
      // viteVuePluginOptions: {},

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

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#devserver
    devServer: {
      https: true,
      port: 8087,
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
        port: 8087,
      },
    },

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#framework
    framework: {
      config: {
        loadingBar: {
          color: 'deep-orange',
          size: '4px',
          position: 'top',
        },
      },
      lang: 'pt-BR',
      // iconSet: 'material-icons', // Quasar icon set
      // lang: 'en-US', // Quasar language pack

      // For special cases outside of where the auto-import strategy can have an impact
      // (like functional components as one of the examples),
      // you can manually specify Quasar components/directives to be available everywhere:
      //
      // components: [],
      // directives: [],

      // Quasar plugins
      plugins: ['Notify', 'LoadingBar', 'Dialog'],
    },

    // animations: 'all', // --- includes all animations
    // https://v2.quasar.dev/options/animations
    animations: [],

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#sourcefiles
    // sourceFiles: {
    //   rootComponent: 'src/App.vue',
    //   router: 'src/router/index',
    //   store: 'src/store/index',
    //   pwaRegisterServiceWorker: 'src-pwa/register-service-worker',
    //   pwaServiceWorker: 'src-pwa/custom-service-worker',
    //   pwaManifestFile: 'src-pwa/manifest.json',
    //   electronMain: 'src-electron/electron-main',
    //   electronPreload: 'src-electron/electron-preload'
    //   bexManifestFile: 'src-bex/manifest.json
    // },

    // https://v2.quasar.dev/quasar-cli-vite/developing-ssr/configuring-ssr
    ssr: {
      prodPort: 3000, // The default port that the production server should use
      // (gets superseded if process.env.PORT is specified at runtime)

      middlewares: [
        'render', // keep this as last one
      ],

      // extendPackageJson (json) {},
      // extendSSRWebserverConf (esbuildConf) {},

      // manualStoreSerialization: true,
      // manualStoreSsrContextInjection: true,
      // manualStoreHydration: true,
      // manualPostHydrationTrigger: true,

      pwa: false,
      // pwaOfflineHtmlFilename: 'offline.html', // do NOT use index.html as name!

      // pwaExtendGenerateSWOptions (cfg) {},
      // pwaExtendInjectManifestOptions (cfg) {}
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

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/developing-cordova-apps/configuring-cordova
    cordova: {
      // noIosLegacyBuildFlag: true, // uncomment only if you know what you are doing
    },

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/developing-capacitor-apps/configuring-capacitor
    capacitor: {
      hideSplashscreen: true,
    },

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/developing-electron-apps/configuring-electron
    electron: {
      // extendElectronMainConf (esbuildConf) {},
      // extendElectronPreloadConf (esbuildConf) {},

      // extendPackageJson (json) {},

      // Electron preload scripts (if any) from /src-electron, WITHOUT file extension
      preloadScripts: ['electron-preload'],

      // specify the debugging port to use for the Electron app when running in development mode
      inspectPort: 5858,

      bundler: 'packager', // 'packager' or 'builder'

      packager: {
        // https://github.com/electron-userland/electron-packager/blob/master/docs/api.md#options
        // OS X / Mac App Store
        // appBundleId: '',
        // appCategoryType: '',
        // osxSign: '',
        // protocol: 'myapp://path',
        // Windows only
        // win32metadata: { ... }
      },

      builder: {
        // https://www.electron.build/configuration/configuration

        appId: '-',
      },
    },

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/developing-browser-extensions/configuring-bex
    bex: {
      // extendBexScriptsConf (esbuildConf) {},
      // extendBexManifestJson (json) {},

      /**
       * The list of extra scripts (js/ts) not in your bex manifest that you want to
       * compile and use in your browser extension. Maybe dynamic use them?
       *
       * Each entry in the list should be a relative filename to /src-bex/
       *
       * @example [ 'my-script.ts', 'sub-folder/my-other-script.js' ]
       */
      extraScripts: [],
    },
  }
})
