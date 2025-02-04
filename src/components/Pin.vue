<template>
  <span :class="['nerdcel-focal-points-pin', { 'nerdcel-focal-points-pin--is-right': parseInt(getCoords['--x']) > 80 }]" :style="getCoords"><i>{{ name }}</i></span>
</template>

<script>
export default {
  props: {
    name: {
      type: String,
      default: '',
    },
    pin: {
      type: String,
      default: '50% 50%',
    },
  },

  computed: {
    getCoords() {
      const [x, y] = this.pin.split(' ');

      return {
        '--x': `${x}`,
        '--y': `${y}`,
      };
    },
  },
};
</script>

<style lang="scss">
.nerdcel-focal-points-pin {
  position: absolute;
  width: 10px;
  height: 10px;
  background-color: var(--color-dark);
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
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
    padding: 0.5rem;
    color: var(--color-light);
    text-shadow: 0 0 0 1px var(--color-dark);
  }

  &--is-right i {
    left: 0%;
    transform: translate(-100%, -50%);
  }
}
</style>
