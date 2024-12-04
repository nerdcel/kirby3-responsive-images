<template>
  <div class="nerdcel-focal-points">
    <k-grid variant="columns" style="--grid-inline-gap: 0">
      <k-column theme="passive" style="--width: 8/12">
        <div class="nerdcel-focal-points__image">
          <k-coords-input :class="{ 'nerdcel-focal-points__image-coords': canSet }" :value="coords" @input="updateCoords"
                          :disabled="!canSet" style="--opacity-disabled: 1">
            <img :src="model.url" :alt="model.content?.alt">
            <nerdcel-pins v-if="!selectedBreakpoint" :pins="focalModel" />
          </k-coords-input>
        </div>
      </k-column>
      <k-column theme="passive" style="--width: 4/12">
        <div class="nerdcel-focal-points__details">
          <k-field input="breakpointOptions" :label="$t('nerdcel.responsove-images.focalpoints.label-breakpoints')"
                   :help="$t('nerdcel.responsove-images.focalpoints.help-breakpoints')">
            <k-select-input
              id="breakpointOptions"
              name="select"
              :options="breakpointOptions"
              :value="selectedBreakpoint"
              @input="selectedBreakpoint = $event"
            />
          </k-field>
          <k-field v-if="selectedBreakpoint">
            <k-button v-if="focalModel[selectedBreakpoint]" @click="removeFocal(selectedBreakpoint)" theme="light"
                      variant="filled" icon="trash" size="sm">{{ focalModel[selectedBreakpoint] }}
            </k-button>
            <k-button v-else @click="setFocal(selectedBreakpoint)" variant="filled" icon="preview" size="sm">{{
                $t('nerdcel.responsive-images.field.set-focal-point')
              }}
            </k-button>
          </k-field>
        </div>
      </k-column>
    </k-grid>
  </div>
</template>

<script>
export default {
  data () {
    return {
      selectedBreakpoint: null,
      store: null,
    };
  },

  props: {
    model: {
      type: Array,
      default: () => [],
    },
    focalModel: {
      type: Object,
      default: () => ({}),
    },
    breakpoints: {
      type: Array,
      default: () => [],
    },
  },
  computed: {
    canSet () {
      return this.selectedBreakpoint && this.focalModel[this.selectedBreakpoint];
    },
    coords () {
      return this.transformCoords(this.focalModel[this.selectedBreakpoint]);
    },
    breakpointOptions () {
      return this.breakpoints.map((breakpoint) => ({
        text: `${breakpoint.name} - ${breakpoint.width}`,
        value: breakpoint.name,
      }));
    },
  },
  methods: {
    transformCoords (value) {
      if (!value) {
        return null;
      }

      const [x, y] = value.split(' ');
      return {
        x: parseFloat(x),
        y: parseFloat(y),
      };
    },
    updateCoords (value) {
      this.setFocal(this.selectedBreakpoint, `${value.x.toFixed(1)}% ${value.y.toFixed(1)}%`);
    },
    setFocal (breakpoint, value = '50% 50%') {
      this.$emit('input', {
        ...this.focalModel,
        [breakpoint]: `${value}`,
      });
    },
    removeFocal (breakpoint) {
      this.$emit('input', {
        ...this.focalModel,
        [breakpoint]: null,
      });
    },
  }
};
</script>

<style lang="scss">
.nerdcel-focal-points {
  background: var(--file-preview-back);

  .nerdcel-focal-points__image {
    aspect-ratio: 1/1;
    background: var(--pattern);
    overflow: hidden;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: var(--spacing-10);
    container-type: size;

    .nerdcel-focal-points__image-coords {
      cursor: crosshair;
    }
  }

  .nerdcel-focal-points__details {
    padding: var(--spacing-6);
  }
}
</style>
