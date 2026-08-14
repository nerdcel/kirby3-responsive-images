<template>
    <div>
        <template v-if="fileType === 'image'">
            <k-field :name="name" :label="label" :help="help" :input="value" :endpoints="endpoints" type="focalpoints">
                <nerdcel-focal-points-dialog :model="fieldModel" :breakpoints="breakpoints" :focal-model="value"
                                             :ai-hint="aiHint" :image-width="imageWidth" :image-height="imageHeight"
                                             @input="updateModel($event)"></nerdcel-focal-points-dialog>
            </k-field>
        </template>
        <k-field v-else>
            <k-box theme="warning" :text="$t('nerdcel.responsive-images.focalpoints.field-not-supported')"></k-box>
        </k-field>
    </div>
</template>

<script>
export default {
    props: {
        name: {
            type: String,
            default: 'focalpoints',
        },
        endpoints: {
            type: Object,
            default: () => ({}),
        },
        focalpoints: {
            type: Array,
            default: () => [],
        },
        label: {
            type: String,
            default: 'Focal Points',
        },
        help: {
            type: String,
            default: '',
        },
        fieldModel: {
            type: Object,
            default: () => ({}),
        },
        breakpoints: {
            type: Array,
            default: () => [],
        },
        fileType: {
            type: String,
            default: '',
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
        value: {
            type: Object,
            default: () => ({}),
        },
    },
    methods: {
        updateModel (value) {
            this.$emit('input', value);
        },
    },
};
</script>


