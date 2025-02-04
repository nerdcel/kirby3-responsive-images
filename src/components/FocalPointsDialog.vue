<template>
  <div class="nerdcel-focal-points">
    <k-grid variant="columns" style="--grid-inline-gap: 0">
      <k-column style="--width: 8/12">
        <div class="nerdcel-focal-points__image">
          <k-coords-input :class="['nerdcel-focal-points__image-coords', { 'nerdcel-focal-points__image-coords-set': canSet }]" :value="coords"
                          @input="updateCoords"
                          :disabled="!canSet" style="--opacity-disabled: 1">
            <img :src="model.url" :alt="model.content?.alt">
            <nerdcel-pins :pins="focalModel"/>
          </k-coords-input>
        </div>
      </k-column>
      <k-column style="--width: 4/12">
        <div class="nerdcel-focal-points__details">
          <k-field input="breakpointOptions"
                   :label="$t('nerdcel.responsove-images.focalpoints.label-breakpoints')"
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
            <k-button v-if="focalModel[selectedBreakpoint]" @click="removeFocal(selectedBreakpoint)"
                      theme="light"
                      variant="filled" icon="trash" size="sm">{{ focalModel[selectedBreakpoint] }}
            </k-button>
            <k-button v-else @click="setFocal(selectedBreakpoint)" variant="filled" icon="preview"
                      size="sm">{{
                $t('nerdcel.responsive-images.field.label.set-focal-point')
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
  background: var(--color-gray-900);
  color: var(--color-gray-100);

  .k-select-input {
    border: 1px solid var(--color-gray-700);
  }

  .nerdcel-focal-points__image {
    aspect-ratio: 16/9;
    background: var(--pattern);
    overflow: hidden;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: var(--spacing-10);
    container-type: size;

    .nerdcel-focal-points__image-coords-set {
      cursor: crosshair;
    }

    .nerdcel-focal-points__image-coords {
      height: 100%;

      img {
        height: 100%;
      }
    }
  }

  .nerdcel-focal-points__details {
    padding: var(--spacing-6);
  }
}
</style>
