<template>
  <k-panel-inside
    :data-has-tabs="false"
    data-id="responsive-images"
    class="k-user-view"
  >
    <k-header
      class="k-user-view-header"
    >
      <template v-if="hasChanges" #buttons>
        <k-button-group
          layout="collapsed"
          class="k-form-buttons"
        >
          <k-button
            theme="notice"
            icon="undo"
            size="sm"
            variant="filled"
            :text="$t('revert')"
            @click.prevent="reset"
            :responsive="true"
          />
          <k-button
            theme="notice"
            icon="check"
            size="sm"
            variant="filled"
            :text="$t('save')"
            @click.prevent="submit"
            :responsive="true"
          />
        </k-button-group>
      </template>
    </k-header>

    <k-fieldset
      :fields="fields"
      :value="model"
      @input="input"
    />
  </k-panel-inside>
</template>

<script>
import Store from '../store';

export default {
  data () {
    return {
      model: null,
      storage: null,
    };
  },

  props: {
    endpoints: {
      type: Object,
      default: () => ({}),
    },
    fields: {
      type: Array,
      default: () => [],
    },
    value: {
      type: Object,
      default: () => ({
        breakpoints: [],
        settings: [],
      }),
    },
  },

  created () {
    this.$panel.events.on('keydown.cmd.s', this.hasChanges ? this.submit : null);
    this.storage = new Store('responsive-images');
    this.model = this.storage.hasState() ? this.storage.getState() : JSON.parse(JSON.stringify(this.value));
  },

  destroyed () {
    this.$panel.events.off('keydown.cmd.s', this.hasChanges ? this.submit : null);
  },

  methods: {
    input (value) {
      this.model = this.storage.setState(value);
    },

    async submit (e) {
      e && e.preventDefault();

      await this.$api.post(this.endpoints.field, this.model);

      this.storage.clearState();
      window.location.reload();
    },

    reset () {
      this.storage.clearState();
      this.model = JSON.parse(JSON.stringify(this.value));
    },
  },

  computed: {
    hasChanges () {
      return JSON.stringify(this.model) !== JSON.stringify(this.value);
    },
  },
};
</script>
