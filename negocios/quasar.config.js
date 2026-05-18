// Configuration for your app
// https://v2.quasar.dev/quasar-cli-vite/quasar-config-file

import { defineConfig } from '#q-app/wrappers'
import path from 'node:path'
import { execSync } from 'node:child_process'
import pkg from './package.json' with { type: 'json' }

function gitCommitNumber() {
  if (process.env.COMMIT_NUMBER) return process.env.COMMIT_NUMBER
  try {
    return execSync("git -c safe.directory='*' rev-list --count HEAD -- .", {
      cwd: import.meta.dirname,
    })
      .toString()
      .trim()
  } catch {
    return ''
  }
}

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
        node: 'node20',
      },

      env: {
        APP_NAME: pkg.name.toLowerCase(),
        APP_VERSION: pkg.version,
        BUILD_DATE: new Date().toISOString(),
        COMMIT_NUMBER: gitCommitNumber(),
      },

      alias: {
        '@components': path.resolve(import.meta.dirname, '../components'),
        'quasar/src': path.resolve(import.meta.dirname, 'node_modules/quasar/src'),
      },

      vueRouterMode: 'history',
      // vueRouterBase,
      // vueDevtools,
      // vueOptionsAPI: false,

      // rebuildCache: true,
      // publicPath: '/',
      // analyze: true,
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

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#devserver
    devServer: {
      https: true,
      port: 9900,
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
        host: 'negocios-dev.mgpapelaria.com.br',
        port: 9900,
      },
    },

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-file#framework
    framework: {
      config: {},

      // iconSet: 'material-icons',
      lang: 'pt-BR',

      // components: [],
      // directives: [],

      plugins: ['Notify', 'LoadingBar', 'Dialog'],
    },

    animations: 'all',

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
        appId: 'negocios',
      },
    },

    bex: {
      extraScripts: [],
    },
  }
})
