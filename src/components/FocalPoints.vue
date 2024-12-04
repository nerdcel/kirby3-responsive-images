<template>
  <div>
    <template v-if="fileType === 'image'">
      <k-field :label="label">
        <nerdcel-focal-points-dialog :model="fieldModel" :breakpoints="breakpoints" :focal-model="value" @input="updateModel($event)"></nerdcel-focal-points-dialog>
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
    focalpoints: {
      type: Array,
      default: () => [],
    },
    label: {
      type: String,
      default: 'Focal Points',
    },
    fieldModel: {
      type: Array,
      default: () => [],
    },
    breakpoints: {
      type: Array,
      default: () => [],
    },
    fileType: {
      type: String,
      default: '',
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


