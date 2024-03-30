(function() {
  "use strict";
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
  const _sfc_main$1 = {
    // Put your section logic here
  };
  var _sfc_render$1 = function render() {
    var _vm = this;
    _vm._self._c;
    return _vm._m(0);
  };
  var _sfc_staticRenderFns$1 = [function() {
    var _vm = this, _c = _vm._self._c;
    return _c("section", { staticClass: "k-demo-section" }, [_c("header", { staticClass: "k-section-header" }, [_c("h2", { staticClass: "k-headline" }, [_vm._v("Your custom section")])])]);
  }];
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
  __component__$1.options.__file = "/home/dvoll/code/cvsq-kirby/site/plugins/dvll-newsletter/src/components/DemoSection.vue";
  const DemoSection = __component__$1.exports;
  const _sfc_main = {
    data() {
      return {
        status: "loading",
        id: void 0,
        sendingTest: false
      };
    },
    computed: {
      allowedToSend() {
        return this.status === "draft";
      },
      canSend() {
        return this.allowedToSend;
      },
      canSendTest() {
        return this.allowedToSend && !this.sendingTest;
      }
    },
    created() {
      this.load().then((response) => {
        this.status = response.status;
        this.id = response.id;
      });
    },
    methods: {
      onSend() {
        this.$panel.dialog.open({
          component: "k-text-dialog",
          props: {
            text: "Den Newsletter an wirklich an alle Abonnenten senden?",
            submitButton: {
              text: "Absenden",
              color: "orange"
            }
          },
          on: {
            submit: () => {
              this.sendNewsletter();
            },
            close: () => console.log("closed")
          }
        });
      },
      onSendTest() {
        this.sendTestNewsletter();
      },
      sendTestNewsletter() {
        if (!this.id) {
          this.$panel.notification.error("No newsletter found");
          return;
        }
        this.sendingTest = true;
        this.$api.get(`${this.id}/send/1`).then((response) => {
          this.sendingTest = false;
          if (response.data.successfulDelivery === 0) {
            this.$panel.notification.error("Keine Nachrichten versendet. Wurde mindestens ein Empfänger ausgewählt?");
            return;
          }
          if (response.data.errorDelivery > 0) {
            this.$panel.notification.error("Beim Senden an einzelne Empfänger ist ein Problem aufgetreten.");
            return;
          }
          this.$panel.notification.success("Testmail gesendet");
        }).catch((error) => {
          this.sendingTest = false;
          console.error(error.message);
          this.$panel.notification.error(error.message);
        });
      },
      sendNewsletter() {
        if (!this.id) {
          this.$panel.notification.error("No newsletter found");
          return;
        }
        this.$panel.dialog.isLoading = true;
        this.$api.get(`${this.id}/send`).then((response) => {
          this.$panel.dialog.isLoading = false;
          if (response.data.successfulDelivery === 0) {
            this.$panel.notification.error("Keine Nachrichten versendet. Wurde mindestens ein Empfänger ausgewählt?");
            return;
          }
          if (response.data.errorDelivery > 0) {
            this.$panel.notification.error("Beim Senden an einzelne Empfänger ist ein Problem aufgetreten.");
            return;
          }
          this.$panel.dialog.close();
          this.$panel.reload();
        }).catch((error) => {
          this.$panel.dialog.isLoading = false;
          console.error(error.message);
          this.$panel.notification.error(error.message);
        });
      }
    }
  };
  var _sfc_render = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("section", { staticClass: "k-newsletter-action-section" }, [_c("k-bar", [_c("k-button-group", [_c("k-button", { attrs: { "variant": "filled", "disabled": !_vm.canSendTest, "icon": _vm.sendingTest ? "loader" : "lab" }, on: { "click": _vm.onSendTest } }, [_vm._v(" Testmail senden ")]), _c("k-button", { attrs: { "variant": "filled", "disabled": !_vm.canSend, "icon": "plane" }, on: { "click": _vm.onSend } }, [_vm._v(" Newsletter abschicken ")])], 1), _vm._v(" " + _vm._s(_vm.status) + " ")], 1)], 1);
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
  __component__.options.__file = "/home/dvoll/code/cvsq-kirby/site/plugins/dvll-newsletter/src/components/NewsletterActionSection.vue";
  const NewsletterActionSection = __component__.exports;
  window.panel.plugin("getkirby/pluginkit", {
    sections: {
      demo: DemoSection,
      "newsletter-action": NewsletterActionSection
    }
  });
})();
