<template>
  <div class="nerdcel-focal-points-pins">
    <pin v-for="(pin, name) in pins" :key="name" :pin="pin" :name="name"></pin>
  </div>
</template>

<script>
import Pin from './Pin.vue';

export default {
  components: {
    Pin,
  },
  props: {
    pins: {
      type: Array,
      default: () => []
    },
  },
}
</script>

<style lang="scss">
.nerdcel-focal-points-pins {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  z-index: 1;
}
</style>
