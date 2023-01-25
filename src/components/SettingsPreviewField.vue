<template>
  <div class="k-settings-preview-field">
    <k-table
      empty="—"
      :columns="{
        breakpoint: {
          label: 'Breakpoint',
          type: 'text',
        },
        width: {
          label: 'Width',
          type: 'number',
          after: 'px',
        },
        cropwidth: {
          label: 'Crop?',
          type: 'toggle-extend'
        },
        height: {
          label: 'Height',
          type: 'number',
          after: 'px',
        },
        cropheight: {
          label: 'Crop?',
          type: 'toggle-extend',
        },
        retina: {
          label: 'Retina?',
          type: 'toggle-extend',
        },
      }"
      :rows="value"
    ></k-table>
  </div>
</template>

<script>
export default {
  props: {
    value: String,
    column: Object,
    field: Object,
  },
};
</script>
