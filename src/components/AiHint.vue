<template>
    <div>
        <template v-if="fileType === 'image'">
            <k-field :label="label" :help="help" class="nerdcel-ai-hint">
                <k-field :label="$t('nerdcel.responsive-images.aihint.enable')">
                    <k-toggle-input
                        :value="model.enabled"
                        @input="update('enabled', $event)"
                    />
                </k-field>

                <div v-if="model.enabled" class="nerdcel-ai-hint__options">
                    <k-grid variant="columns">
                        <k-column style="--width: 12/12">
                            <k-field :label="$t('nerdcel.responsive-images.aihint.text')">
                                <k-text-input
                                    :value="model.text"
                                    :placeholder="text"
                                    @input="update('text', $event)"
                                />
                            </k-field>
                        </k-column>

                        <k-column style="--width: 2/12">
                            <k-select-field
                                :label="$t('nerdcel.responsive-images.aihint.position')"
                                :options="positionOptions"
                                :empty="false"
                                :value="model.position || position"
                                @input="update('position', $event)"
                            />
                        </k-column>

                        <k-column style="--width: 2/12">
                            <k-color-field
                                :label="$t('nerdcel.responsive-images.aihint.color')"
                                :value="model.color || color"
                                @input="update('color', $event)"
                            />
                        </k-column>

                        <k-column style="--width: 2/12">
                            <k-field :label="$t('nerdcel.responsive-images.aihint.size')">
                                <k-range-input
                                    :min="1"
                                    :max="15"
                                    :step="0.5"
                                    :tooltip="{ after: '%' }"
                                    :value="model.size ?? size"
                                    @input="update('size', $event)"
                                />
                            </k-field>
                        </k-column>

                        <k-column style="--width: 2/12">
                            <k-field :label="$t('nerdcel.responsive-images.aihint.opacity')">
                                <k-range-input
                                    :min="0"
                                    :max="100"
                                    :step="5"
                                    :tooltip="{ after: '%' }"
                                    :value="model.opacity ?? opacity"
                                    @input="update('opacity', $event)"
                                />
                            </k-field>
                        </k-column>

                        <k-column style="--width: 2/12">
                            <k-field :label="$t('nerdcel.responsive-images.aihint.margin')">
                                <k-range-input
                                    :min="0"
                                    :max="3"
                                    :step="0.1"
                                    :tooltip="{ after: 'em' }"
                                    :value="model.margin ?? margin"
                                    @input="update('margin', $event)"
                                />
                            </k-field>
                        </k-column>

                        <k-column style="--width: 2/12">
                            <k-field :label="$t('nerdcel.responsive-images.aihint.padding')">
                                <k-range-input
                                    :min="0"
                                    :max="2"
                                    :step="0.1"
                                    :tooltip="{ after: 'em' }"
                                    :value="model.padding ?? padding"
                                    @input="update('padding', $event)"
                                />
                            </k-field>
                        </k-column>
                    </k-grid>

                    <div v-if="imageUrl" class="nerdcel-ai-hint__preview" :style="previewContainerStyle">
                        <img :src="imageUrl" alt="">
                        <span
                            :class="['nerdcel-ai-hint', `nerdcel-ai-hint--${model.position || position}`]"
                            :style="hintStyle"
                        >{{ model.text || text }}</span>
                    </div>
                </div>
            </k-field>
        </template>
        <k-field v-else>
            <k-box theme="warning" :text="$t('nerdcel.responsive-images.aihint.field-not-supported')" />
        </k-field>
    </div>
</template>


<script>
import { contrastBackdropColor } from '../utils/contrast';

export default {
    props: {
        label: {
            type: String,
            default: 'AI Hint',
        },
        help: {
            type: String,
            default: '',
        },
        text: {
            type: String,
            default: '',
        },
        position: {
            type: String,
            default: 'bottom-right',
        },
        color: {
            type: String,
            default: '#ffffff',
        },
        size: {
            type: Number,
            default: 5,
        },
        opacity: {
            type: Number,
            default: 100,
        },
        margin: {
            type: Number,
            default: 0.6,
        },
        padding: {
            type: Number,
            default: 0.5,
        },
        fileType: {
            type: String,
            default: '',
        },
        imageUrl: {
            type: String,
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
        value: {
            type: Object,
            default: () => ({
                enabled: false, text: null, position: null, color: null, size: null, opacity: null, margin: null, padding: null,
            }),
        },
    },
    data() {
        return {
            model: { ...this.value },
        };
    },
    computed: {
        positionOptions() {
            return [
                { value: 'top-left', text: this.$t('nerdcel.responsive-images.aihint.position.top-left') },
                { value: 'top-right', text: this.$t('nerdcel.responsive-images.aihint.position.top-right') },
                { value: 'bottom-left', text: this.$t('nerdcel.responsive-images.aihint.position.bottom-left') },
                { value: 'bottom-right', text: this.$t('nerdcel.responsive-images.aihint.position.bottom-right') },
            ];
        },
        /**
         * Gives the preview image its real aspect ratio, so the `cqb`
         * (container query block-size) unit used for the hint's font size
         * matches the proportions actually rendered on the frontend.
         */
        previewContainerStyle() {
            return {
                aspectRatio: this.imageWidth && this.imageHeight
                    ? `${this.imageWidth} / ${this.imageHeight}`
                    : undefined,
            };
        },
        /**
         * Live preview styling, reacting instantly to every input change
         * (text, color, size, opacity, margin, padding) without needing
         * to save the field. The backdrop colour automatically contrasts
         * with the chosen font colour.
         */
        hintStyle() {
            const size = this.model.size ?? this.size;
            const margin = this.model.margin ?? this.margin;
            const padding = this.model.padding ?? this.padding;
            const color = this.model.color || this.color;

            return {
                color,
                opacity: (this.model.opacity ?? this.opacity) / 100,
                fontSize: `clamp(0.4rem, ${size}cqb, 4rem)`,
                padding: `${padding}em`,
                backgroundColor: contrastBackdropColor(color),
                '--nerdcel-ai-hint-margin': `${margin}em`,
            };
        },
    },
    methods: {
        update(key, value) {
            this.model = { ...this.model, [key]: value };
            this.$emit('input', this.model);
        },
    },
};
</script>

<style lang="scss">
.nerdcel-ai-hint {
    &__options {
        margin-top: var(--spacing-3);
    }

    &__preview {
        position: relative;
        margin-top: var(--spacing-3);
        overflow: hidden;
        border-radius: var(--rounded);
        background: var(--pattern);
        container-type: size;

        img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    }

    /**
     * The overlay itself. This is panel-only preview markup - the hint is
     * never rendered as HTML/CSS on the frontend, only burned into the
     * generated image itself (see ImageStamper).
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
        /* Padding and backdrop colour are set per-instance via the
           `hintStyle` computed property above (padding is editor
           configurable, backdrop colour auto-contrasts with the font
           colour). */
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

    /* Kept for backwards compatibility with content saved before the
       "center" option was removed from the position dropdown. */
    .nerdcel-ai-hint--center {
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        white-space: normal;
        text-align: center;
    }
}
</style>
