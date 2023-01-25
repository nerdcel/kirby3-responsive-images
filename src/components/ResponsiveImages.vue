<template>
  <k-inside class="k-responsive-images-panel">

    <k-view>
      <k-header><h2>{{ $t('nerdcel.responsive-images.title') }}</h2></k-header>
    </k-view>

    <k-view>
      <k-grid gutter="medium">
        <k-column width="1/3">
          <div class="k-section k-responsive-images-breakpoints">
            <k-structure-field
              :columns="columnsBreakpoints"
              :required="true"
              :label="$t('nerdcel.responsive-images.breakpoints')"
              name="responsivebreakpoints"
              :help="$t('nerdcel.responsive-images.breakpointsinfo')"
              :fields="fieldsBreakpoints"
              :endpoints="endpointsBreakpoints"
              v-model="responsivebreakpointsModel"
              :duplicate="false"
              @input="input"
            >
            </k-structure-field>
          </div>
        </k-column>
        <k-column width="2/3">
          <div class="k-section">
            <k-info-field :label="$t('nerdcel.responsive-images.info')"
              text="responsiveImage('key-settings', $image, $optionalClasses)"
            ></k-info-field>
          </div>
          <div class="k-section k-responsive-images-view">
            <k-structure-field
              :disabled="!this.breakpoints.length"
              :columns="columns"
              :required="true"
              :label="$t('nerdcel.responsive-images.settings')"
              name="responsivesettings"
              :help="$t('nerdcel.responsive-images.info')"
              :fields="fields"
              :endpoints="endpoints"
              v-model="responsivesettingsModel"
              :duplicate="false"
              @input="input"
            >
            </k-structure-field>
          </div>
        </k-column>
      </k-grid>
    </k-view>

    <template #footer>
        <nav class="k-form-buttons" data-theme="notice" v-if="hasChanges">
          <k-view>
            <k-button
              class="k-form-button"
              icon="undo"
              @click="reset"
              type="button"
            >
              {{ $t('nerdcel.responsive-images.undo') }}
            </k-button>
            <k-button
              class="k-form-button"
              icon="check"
              @click="submit"
              type="button"
            >
              {{ $t('nerdcel.responsive-images.save') }}
            </k-button>
          </k-view>
        </nav>
      </template>
  </k-inside>
</template>

<script>
export default {
  data () {
    return {
      ready: false,
      columns: {
        name: {
          label: 'Name',
          type: 'text',
          width: '1/4',
        },
        breakpointoptions: {
          label: 'Settings',
          type: 'settings',
          width: '3/4',
        },
      },

      columnsBreakpoints: {
        mediaquery: {
          label: 'Mediaquery',
          type: 'select',
        },
        name: {
          label: 'Name',
          type: 'text',
        },
        width: {
          label: 'Width',
          type: 'number',
          after: 'px',
        },
      },

      endpoints: {
        field: 'responsive-images',
        section: 'responsive-images',
        model: 'responsive-images',
      },

      endpointsBreakpoints: {
        field: 'responsive-images-breakpoints',
        section: 'responsive-images-breakpoints',
        model: 'responsive-images-breakpoints',
      },

      responsivesettingsModel: [],
      responsivebreakpointsModel: [],
      hasChanges: false,
    };
  },

  props: {
    config: {
      type: Object,
      default: () => ({
        breakpoints: [],
        settings: [],
      }),
    },
  },

  created () {
    this.$events.$on('keydown.cmd.s', this.submit);
  },
  destroyed () {
    this.$events.$off('keydown.cmd.s', this.submit);
  },

  mounted () {
    this.responsivesettingsModel = Object.assign([], this.settings);
    this.responsivebreakpointsModel = Object.assign(
      [],
      this.breakpoints,
    );

    this.$nextTick(() => (this.ready = true));
  },

  computed: {
    fields () {
      return {
        name: {
          label: 'Name',
          type: 'slug',
          required: true,
        },
        breakpointoptions: {
          label: 'Options',
          type: 'structure',
          columns: {
            breakpoint: {
              type: 'select',
              label: 'Name',
              required: true,
            },
            width: {
              type: 'number',
              label: 'Width',
              default: null,
              after: 'px',
            },
            cropwidth: {
              type: 'toggle',
              default: false,
              label: 'Crop width',
            },
            height: {
              type: 'number',
              label: 'Height',
              default: null,
              after: 'px',
            },
            cropheight: {
              type: 'toggle',
              default: false,
              label: 'Crop height',
            },
            retina: {
              type: 'toggle',
              default: true,
              label: 'Retina',
            },
          },
          fields: {
            breakpoint: {
              label: 'Breakpoint',
              type: 'select',
              options: this.breakpointOptions,
              width: '1/6',
            },
            width: {
              label: 'Width',
              type: 'number',
              min: 0,
              width: '1/6',
            },
            cropwidth: {
              label: 'Crop width',
              type: 'toggle',
              default: 0,
              width: '1/6',
            },
            height: {
              label: 'Height',
              type: 'number',
              min: 0,
              width: '1/6',
            },
            cropheight: {
              label: 'Crop height',
              type: 'toggle',
              default: 0,
              width: '1/6',
            },
            retina: {
              label: 'Retina support',
              type: 'toggle',
              default: true,
              width: '1/6',
            },
          },
        },
      };
    },

    breakpointOptions () {
      const opt = this.breakpoints.map((item) =>
        Object.assign(
          {},
          {
            text: item.name,
            value: item.name,
          },
        ),
      );

      return opt;
    },

    fieldsBreakpoints () {
      return {
        mediaquery: {
          label: 'Mediaquery',
          type: 'select',
          default: 'min-width',
          options: [
            {
              text: 'Min width',
              value: 'min-width',
            },
            {
              text: 'Max width',
              value: 'max-width',
            },
          ],
        },
        name: {
          label: 'Name',
          type: 'text',
        },
        width: {
          label: 'Width',
          type: 'number',
          after: 'px',
        },
      };
    },

    settings () {
      if (this.config && this.config.hasOwnProperty('settings')) {
        return this.config.settings;
      }
      return [];
    },

    breakpoints () {
      if (this.config && this.config.hasOwnProperty('breakpoints')) {
        return this.config.breakpoints;
      }
      return [];
    },
  },

  methods: {
    async submit (e) {
      e && e.preventDefault();

      await this.$api.post(this.endpoints.field, {
        breakpoints: this.responsivebreakpointsModel,
        settings: this.responsivesettingsModel,
      });

      this.$reload();
      this.hasChanges = false;
    },

    reset () {
      this.responsivesettingsModel = Object.assign([], this.settings);
      this.responsivebreakpointsModel = Object.assign(
        [],
        this.breakpoints,
      );

      this.hasChanges = false;
    },

    input () {
      if (
        this.ready &&
        (JSON.stringify(this.settings) !==
          JSON.stringify(this.responsivesettingsModel) ||
          JSON.stringify(this.breakpoints) !==
          JSON.stringify(this.responsivebreakpointsModel))
      ) {
        return (this.hasChanges = true);
      }
      return (this.hasChanges = false);
    },
  },
};
</script>

<style lang="scss">
.k-responsive-images-panel {
  .k-section {
    padding-bottom: 3rem;
  }
}
</style>
