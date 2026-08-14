<template>
  <div class="nerdcel-focal-points">
    <k-grid variant="columns" style="--grid-inline-gap: 0">
      <k-column style="--width: 8/12">
        <div class="nerdcel-focal-points__image">
          <k-coords-input :class="['nerdcel-focal-points__image-coords', { 'nerdcel-focal-points__image-coords-set': canSet }]" :value="coords"
                          @input="updateCoords"
                          :disabled="!canSet" :style="imageCoordsStyle">
            <img :src="model.url" :alt="model.content?.alt">
            <nerdcel-pins :pins="focalModel"/>
            <span
              v-if="aiHint"
              :class="['nerdcel-ai-hint', `nerdcel-ai-hint--${aiHint.position}`]"
              :style="aiHintStyle"
            >{{ aiHint.text }}</span>
          </k-coords-input>
        </div>
      </k-column>
      <k-column style="--width: 4/12">
        <div class="nerdcel-focal-points__details">
          <k-field input="breakpointOptions"
                   :label="$t('nerdcel.responsive-images.focalpoints.label-breakpoints')"
                   :help="$t('nerdcel.responsive-images.focalpoints.help-breakpoints')">
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
import { contrastBackdropColor } from '../utils/contrast';

export default {
  data () {
    return {
      selectedBreakpoint: null,
      store: null,
    };
  },

  props: {
    model: {
        type: Object,
        default: () => ({}),
    },
    focalModel: {
      type: Object,
      default: () => ({}),
    },
    breakpoints: {
      type: Array,
      default: () => [],
    },
    aiHint: {
      type: Object,
      default: null,
    },
    imageWidth: {
      type: Number,
      default: null,
    },
    imageHeight: {
      type: Number,
      default: null,
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
    /**
     * Gives the coords wrapper the same aspect ratio as the real image, so
     * the AI hint preview (sized with `cqb` container query block-size
     * units) matches the proportions actually rendered on the frontend.
     */
    imageCoordsStyle () {
      return {
        '--opacity-disabled': 1,
        aspectRatio: this.imageWidth && this.imageHeight
          ? `${this.imageWidth} / ${this.imageHeight}`
          : undefined,
      };
    },
    aiHintStyle () {
      if (!this.aiHint) {
        return {};
      }

      return {
        color: this.aiHint.color,
        opacity: (this.aiHint.opacity ?? 100) / 100,
        fontSize: `clamp(0.4rem, ${this.aiHint.size ?? 5}cqb, 4rem)`,
        padding: `${this.aiHint.padding ?? 0.5}em`,
        backgroundColor: contrastBackdropColor(this.aiHint.color),
        '--nerdcel-ai-hint-margin': `${this.aiHint.margin ?? 0.6}em`,
      };
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
      position: relative;
      height: 100%;
      /* Enables `cqb` (container query block-size) units below, so the AI
       * hint preview text is sized relative to the real image height,
       * exactly like the frontend overlay. */
      container-type: size;

      img {
        height: 100%;
      }
    }
  }

  .nerdcel-focal-points__details {
    padding: var(--spacing-6);
  }

  /**
   * AI hint preview overlay. This is panel-only: the hint is never
   * rendered as HTML/CSS on the frontend, only burned into the generated
   * image itself (see ImageStamper).
   */
  .nerdcel-ai-hint {
    position: absolute;
    z-index: 1;
    display: inline-block;
    line-height: 1.2;
    font-family: inherit;
    padding: 0.5em;
    border-radius: 0.25em;
    background: rgba(0, 0, 0, 0.45);
    white-space: nowrap;
    pointer-events: none;
    /* Font size, color, opacity, padding and backdrop colour are set
       per-instance via an inline style (see the `aiHintStyle` computed
       property above). */
  }

  .nerdcel-ai-hint--top-left {
    top: var(--nerdcel-ai-hint-margin, 0.6em);
    left: var(--nerdcel-ai-hint-margin, 0.6em);
  }

  .nerdcel-ai-hint--top-right {
    top: var(--nerdcel-ai-hint-margin, 0.6em);
    right: var(--nerdcel-ai-hint-margin, 0.6em);
  }

  .nerdcel-ai-hint--bottom-left {
    bottom: var(--nerdcel-ai-hint-margin, 0.6em);
    left: var(--nerdcel-ai-hint-margin, 0.6em);
  }

  .nerdcel-ai-hint--bottom-right {
    bottom: var(--nerdcel-ai-hint-margin, 0.6em);
    right: var(--nerdcel-ai-hint-margin, 0.6em);
  }

  .nerdcel-ai-hint--center {
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    white-space: normal;
    text-align: center;
  }
}
</style>
