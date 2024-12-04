<template>
  <div class="nerdcel-focal-points-pins">
    <span v-for="(pin, index) in pins" :style="getCoords(pin)" :key="index"><i>{{ index }}</i></span>
  </div>
</template>

<script>
export default {
  props: {
    pins: {
      type: Array,
      default: () => []
    },
  },

  methods: {
    getCoords(pin) {
      const [x, y] = pin.split(' ');

      return {
        '--x': `${x}`,
        '--y': `${y}`,
      };
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

.nerdcel-focal-points-pins span {
  position: absolute;
  width: 10px;
  height: 10px;
  background-color: var(--color-purple-600);
  box-shadow: 0 0 0 2px var(--color-purple-200);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
  top: var(--y);
  left: var(--x);

  i {
    position: absolute;
    top: 50%;
    left: 100%;
    transform: translate(0%, -50%);
    color: var(--color-light);
    text-shadow: 0 0 0 1px var(--color-dark);
  }
}
</style>
