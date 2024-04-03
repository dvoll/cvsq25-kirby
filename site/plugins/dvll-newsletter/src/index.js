import NewsletterActionSection from "./components/NewsletterActionSection.vue";
import NewsletterSendDialog from "./components/NewsletterSendDialog.vue";
import NewsletterResultSection from "./components/NewsletterResultSection.vue";

window.panel.plugin("getkirby/pluginkit", {
  components: {
    'k-newsletter-send-dialog': NewsletterSendDialog
  },
	sections: {
		"newsletter-action": NewsletterActionSection,
		"newsletter-result": NewsletterResultSection,
	},
});
