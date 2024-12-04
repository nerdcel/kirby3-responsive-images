(function() {
  "use strict";
  class Store {
    /**
     * Constructor
     * @param {string} name Name of the store
     * @param initialState
     */
    constructor(name, initialState = {}) {
      this.name = name;
      this.state = this.loadState(initialState);
    }
    /**
     * Load the state from the local storage
     * @param {object} initialState Initial state of the store
     * @returns {object} State of the store
     */
    loadState(initialState = {}) {
      try {
        const serializedState = localStorage.getItem(this.name);
        if (serializedState === null) {
          return initialState;
        }
        return JSON.parse(serializedState);
      } catch (error) {
        return initialState;
      }
    }
    hasState() {
      return Object.keys(this.state).length > 0;
    }
    /**
     * Save the state to the local storage
     * @param {object} state State of the store
     */
    saveState(state) {
      try {
        const serializedState = JSON.stringify(state);
        localStorage.setItem(this.name, serializedState);
      } catch (error) {
      }
    }
    /**
     * Get the state of the store
     * @returns {object} State of the store
     */
    getState() {
      return this.state;
    }
    /**
     * Update the state of the store
     * @param {object} state State of the store
     * @returns {object} Updated state of the store
     */
    setState(state) {
      this.state = state;
      this.saveState(state);
      return state;
    }
    /**
     * Clear the state of the store
     */
    clearState() {
      this.state = {};
      this.saveState({});
    }
  }
  function normalizeComponent(scriptExports, render, staticRenderFns, functionalTemplate, injectStyles, scopeId, moduleIdentifier, shadowMode) {
    var options = typeof scriptExports === "function" ? scriptExports.options : scriptExports;
    if (render) {
      options.render = render;
      options.staticRenderFns = staticRenderFns;
      options._compiled = true;
    }
    if (functionalTemplate) {
      options.functional = true;
    }
    if (scopeId) {
      options._scopeId = "data-v-" + scopeId;
    }
    var hook;
    if (moduleIdentifier) {
      hook = function(context) {
        context = context || // cached call
        this.$vnode && this.$vnode.ssrContext || // stateful
        this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext;
        if (!context && typeof __VUE_SSR_CONTEXT__ !== "undefined") {
          context = __VUE_SSR_CONTEXT__;
        }
        if (injectStyles) {
          injectStyles.call(this, context);
        }
        if (context && context._registeredComponents) {
          context._registeredComponents.add(moduleIdentifier);
        }
      };
      options._ssrRegister = hook;
    } else if (injectStyles) {
      hook = shadowMode ? function() {
        injectStyles.call(
          this,
          (options.functional ? this.parent : this).$root.$options.shadowRoot
        );
      } : injectStyles;
    }
    if (hook) {
      if (options.functional) {
        options._injectStyles = hook;
        var originalRender = options.render;
        options.render = function renderWithStyleInjection(h, context) {
          hook.call(context);
          return originalRender(h, context);
        };
      } else {
        var existing = options.beforeCreate;
        options.beforeCreate = existing ? [].concat(existing, hook) : [hook];
      }
    }
    return {
      exports: scriptExports,
      options
    };
  }
  const _sfc_main$5 = {
    data() {
      return {
        model: null,
        storage: null
      };
    },
    props: {
      endpoints: {
        type: Object,
        default: () => ({})
      },
      fields: {
        type: Array,
        default: () => []
      },
      value: {
        type: Object,
        default: () => ({
          breakpoints: [],
          settings: []
        })
      }
    },
    created() {
      this.$panel.events.on("keydown.cmd.s", this.hasChanges ? this.submit : null);
      this.storage = new Store("responsive-images");
      this.model = this.storage.hasState() ? this.storage.getState() : JSON.parse(JSON.stringify(this.value));
    },
    destroyed() {
      this.$panel.events.off("keydown.cmd.s", this.hasChanges ? this.submit : null);
    },
    methods: {
      input(value) {
        this.model = this.storage.setState(value);
      },
      async submit(e) {
        e && e.preventDefault();
        await this.$api.post(this.endpoints.field, this.model);
        this.storage.clearState();
        window.location.reload();
      },
      reset() {
        this.storage.clearState();
        this.model = JSON.parse(JSON.stringify(this.value));
      }
    },
    computed: {
      hasChanges() {
        return JSON.stringify(this.model) !== JSON.stringify(this.value);
      }
    }
  };
  var _sfc_render$5 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-panel-inside", { staticClass: "k-user-view", attrs: { "data-has-tabs": false, "data-id": "responsive-images" } }, [_c("k-header", { staticClass: "k-user-view-header", scopedSlots: _vm._u([_vm.hasChanges ? { key: "buttons", fn: function() {
      return [_c("k-button-group", { staticClass: "k-form-buttons", attrs: { "layout": "collapsed" } }, [_c("k-button", { attrs: { "icon": "undo", "size": "sm", "variant": "filled", "text": _vm.$t("revert"), "responsive": true }, on: { "click": function($event) {
        $event.preventDefault();
        return _vm.reset.apply(null, arguments);
      } } }), _c("k-button", { attrs: { "icon": "check", "size": "sm", "variant": "filled", "text": _vm.$t("save"), "responsive": true }, on: { "click": function($event) {
        $event.preventDefault();
        return _vm.submit.apply(null, arguments);
      } } })], 1)];
    }, proxy: true } : null], null, true) }), _c("k-fieldset", { attrs: { "fields": _vm.fields, "value": _vm.model }, on: { "input": _vm.input } })], 1);
  };
  var _sfc_staticRenderFns$5 = [];
  _sfc_render$5._withStripped = true;
  var __component__$5 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$5,
    _sfc_render$5,
    _sfc_staticRenderFns$5,
    false,
    null,
    null,
    null,
    null
  );
  __component__$5.options.__file = "/Users/marcel/Code/packages/kirby3-responsive-images/src/components/ResponsiveImagesNew.vue";
  const ResponsiveImagesNew = __component__$5.exports;
  const _sfc_main$4 = {
    props: {
      focalpoints: {
        type: Array,
        default: () => []
      },
      label: {
        type: String,
        default: "Focal Points"
      },
      help: {
        type: String,
        default: ""
      },
      fieldModel: {
        type: Array,
        default: () => []
      },
      breakpoints: {
        type: Array,
        default: () => []
      },
      fileType: {
        type: String,
        default: ""
      },
      value: {
        type: Object,
        default: () => ({})
      }
    },
    methods: {
      updateModel(value) {
        this.$emit("input", value);
      }
    }
  };
  var _sfc_render$4 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", [_vm.fileType === "image" ? [_c("k-field", { attrs: { "label": _vm.label, "help": _vm.help } }, [_c("nerdcel-focal-points-dialog", { attrs: { "model": _vm.fieldModel, "breakpoints": _vm.breakpoints, "focal-model": _vm.value }, on: { "input": function($event) {
      return _vm.updateModel($event);
    } } })], 1)] : _c("k-field", [_c("k-box", { attrs: { "theme": "warning", "text": _vm.$t("nerdcel.responsive-images.focalpoints.field-not-supported") } })], 1)], 2);
  };
  var _sfc_staticRenderFns$4 = [];
  _sfc_render$4._withStripped = true;
  var __component__$4 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$4,
    _sfc_render$4,
    _sfc_staticRenderFns$4,
    false,
    null,
    null,
    null,
    null
  );
  __component__$4.options.__file = "/Users/marcel/Code/packages/kirby3-responsive-images/src/components/FocalPoints.vue";
  const FocalPoints = __component__$4.exports;
  const FocalPointsDialog_vue_vue_type_style_index_0_lang = "";
  const _sfc_main$3 = {
    data() {
      return {
        selectedBreakpoint: null,
        store: null
      };
    },
    props: {
      model: {
        type: Array,
        default: () => []
      },
      focalModel: {
        type: Object,
        default: () => ({})
      },
      breakpoints: {
        type: Array,
        default: () => []
      }
    },
    computed: {
      canSet() {
        return this.selectedBreakpoint && this.focalModel[this.selectedBreakpoint];
      },
      coords() {
        return this.transformCoords(this.focalModel[this.selectedBreakpoint]);
      },
      breakpointOptions() {
        return this.breakpoints.map((breakpoint) => ({
          text: `${breakpoint.name} - ${breakpoint.width}`,
          value: breakpoint.name
        }));
      }
    },
    methods: {
      transformCoords(value) {
        if (!value) {
          return null;
        }
        const [x, y] = value.split(" ");
        return {
          x: parseFloat(x),
          y: parseFloat(y)
        };
      },
      updateCoords(value) {
        this.setFocal(this.selectedBreakpoint, `${value.x.toFixed(1)}% ${value.y.toFixed(1)}%`);
      },
      setFocal(breakpoint, value = "50% 50%") {
        this.$emit("input", {
          ...this.focalModel,
          [breakpoint]: `${value}`
        });
      },
      removeFocal(breakpoint) {
        this.$emit("input", {
          ...this.focalModel,
          [breakpoint]: null
        });
      }
    }
  };
  var _sfc_render$3 = function render() {
    var _a;
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "nerdcel-focal-points" }, [_c("k-grid", { staticStyle: { "--grid-inline-gap": "0" }, attrs: { "variant": "columns" } }, [_c("k-column", { staticStyle: { "--width": "8/12" } }, [_c("div", { staticClass: "nerdcel-focal-points__image" }, [_c("k-coords-input", { class: { "nerdcel-focal-points__image-coords": _vm.canSet }, staticStyle: { "--opacity-disabled": "1" }, attrs: { "value": _vm.coords, "disabled": !_vm.canSet }, on: { "input": _vm.updateCoords } }, [_c("img", { attrs: { "src": _vm.model.url, "alt": (_a = _vm.model.content) == null ? void 0 : _a.alt } }), _c("nerdcel-pins", { attrs: { "pins": _vm.focalModel } })], 1)], 1)]), _c("k-column", { staticStyle: { "--width": "4/12" } }, [_c("div", { staticClass: "nerdcel-focal-points__details" }, [_c("k-field", { attrs: { "input": "breakpointOptions", "label": _vm.$t("nerdcel.responsove-images.focalpoints.label-breakpoints"), "help": _vm.$t("nerdcel.responsove-images.focalpoints.help-breakpoints") } }, [_c("k-select-input", { attrs: { "id": "breakpointOptions", "name": "select", "options": _vm.breakpointOptions, "value": _vm.selectedBreakpoint }, on: { "input": function($event) {
      _vm.selectedBreakpoint = $event;
    } } })], 1), _vm.selectedBreakpoint ? _c("k-field", [_vm.focalModel[_vm.selectedBreakpoint] ? _c("k-button", { attrs: { "theme": "light", "variant": "filled", "icon": "trash", "size": "sm" }, on: { "click": function($event) {
      return _vm.removeFocal(_vm.selectedBreakpoint);
    } } }, [_vm._v(_vm._s(_vm.focalModel[_vm.selectedBreakpoint]) + " ")]) : _c("k-button", { attrs: { "variant": "filled", "icon": "preview", "size": "sm" }, on: { "click": function($event) {
      return _vm.setFocal(_vm.selectedBreakpoint);
    } } }, [_vm._v(_vm._s(_vm.$t("nerdcel.responsive-images.field.label.set-focal-point")) + " ")])], 1) : _vm._e()], 1)])], 1)], 1);
  };
  var _sfc_staticRenderFns$3 = [];
  _sfc_render$3._withStripped = true;
  var __component__$3 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$3,
    _sfc_render$3,
    _sfc_staticRenderFns$3,
    false,
    null,
    null,
    null,
    null
  );
  __component__$3.options.__file = "/Users/marcel/Code/packages/kirby3-responsive-images/src/components/FocalPointsDialog.vue";
  const FocalPointsDialog = __component__$3.exports;
  const Pin_vue_vue_type_style_index_0_lang = "";
  const _sfc_main$2 = {
    props: {
      name: {
        type: String,
        default: ""
      },
      pin: {
        type: String,
        default: "50% 50%"
      }
    },
    computed: {
      getCoords() {
        const [x, y] = this.pin.split(" ");
        return {
          "--x": `${x}`,
          "--y": `${y}`
        };
      }
    }
  };
  var _sfc_render$2 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("span", { class: ["nerdcel-focal-points-pin", { "nerdcel-focal-points-pin--is-right": parseInt(_vm.getCoords["--x"]) > 80 }], style: _vm.getCoords }, [_c("i", [_vm._v(_vm._s(_vm.name))])]);
  };
  var _sfc_staticRenderFns$2 = [];
  _sfc_render$2._withStripped = true;
  var __component__$2 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$2,
    _sfc_render$2,
    _sfc_staticRenderFns$2,
    false,
    null,
    null,
    null,
    null
  );
  __component__$2.options.__file = "/Users/marcel/Code/packages/kirby3-responsive-images/src/components/Pin.vue";
  const Pin = __component__$2.exports;
  const Pins_vue_vue_type_style_index_0_lang = "";
  const _sfc_main$1 = {
    components: {
      Pin
    },
    props: {
      pins: {
        type: Array,
        default: () => []
      }
    }
  };
  var _sfc_render$1 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "nerdcel-focal-points-pins" }, _vm._l(_vm.pins, function(pin, name) {
      return _c("pin", { key: name, attrs: { "pin": pin, "name": name } });
    }), 1);
  };
  var _sfc_staticRenderFns$1 = [];
  _sfc_render$1._withStripped = true;
  var __component__$1 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$1,
    _sfc_render$1,
    _sfc_staticRenderFns$1,
    false,
    null,
    null,
    null,
    null
  );
  __component__$1.options.__file = "/Users/marcel/Code/packages/kirby3-responsive-images/src/components/Pins.vue";
  const Pins = __component__$1.exports;
  const _sfc_main = {};
  var _sfc_render = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-inside", { staticClass: "k-responsive-images-panel" }, [_c("k-view", [_c("k-header", [_c("h2", [_vm._v(_vm._s(_vm.$t("nerdcel.responsive-images.restricted.title")))])]), _c("p", [_vm._v(" " + _vm._s(_vm.$t("nerdcel.responsive-images.restricted.text")) + " ")])], 1)], 1);
  };
  var _sfc_staticRenderFns = [];
  _sfc_render._withStripped = true;
  var __component__ = /* @__PURE__ */ normalizeComponent(
    _sfc_main,
    _sfc_render,
    _sfc_staticRenderFns,
    false,
    null,
    null,
    null,
    null
  );
  __component__.options.__file = "/Users/marcel/Code/packages/kirby3-responsive-images/src/components/Restricted.vue";
  const Restricted = __component__.exports;
  window.panel.plugin("nerdcel/responsive-images", {
    components: {
      "nerdcel-responsive-images": ResponsiveImagesNew,
      "nerdcel-restricted": Restricted,
      "nerdcel-pins": Pins,
      "nerdcel-focal-points-dialog": FocalPointsDialog
    },
    fields: {
      "focalpoints": FocalPoints
    }
  });
})();
