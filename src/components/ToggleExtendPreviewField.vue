<template>
  <div class="k-toggle-extend-preview-field">
    <k-text-field-preview>{{ value ? '✓' : '⨯' }}</k-text-field-preview>
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
