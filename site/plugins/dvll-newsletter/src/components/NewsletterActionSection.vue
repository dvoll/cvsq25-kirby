<template>
  <section class="k-newsletter-action-section">
    <k-bar>
      <k-button-group>
        <k-button @click="onSendTest" variant="filled" :disabled="!canSendTest" :icon="sendingTest ? 'loader' : 'lab'">
          Testmail senden
        </k-button>
        <k-button @click="onSend" variant="filled" :disabled="!canSend" icon="plane">
          Newsletter abschicken
        </k-button>
      </k-button-group>
    </k-bar>
  </section>
</template>

<script>
export default {
  data() {
    return {
      status: 'loading',
      id: undefined,
      sendingTest: false,
    }
  },
  computed: {
    allowedToSend() {
      return this.status === 'draft'
    },
    canSend() {
      return this.allowedToSend;
    },
    canSendTest() {
      return this.allowedToSend && !this.sendingTest;
    },
  },
  created() {
    this.load().then(response => {
      this.status = response.status;
      this.id = response.id;
    });
  },
  methods: {
    onSend() {
      this.$panel.dialog.open({
        component: 'k-text-dialog',
        props: {
          text: 'Den Newsletter an wirklich an alle Abonnenten senden?',
          submitButton: {
            text: 'Absenden',
            color: 'orange'
          },
        },
        on: {
          submit: () => {
            this.sendNewsletter();
          },
          close: () => console.log('closed'),
        }
      });
    },
    onSendTest() {
      this.sendTestNewsletter();
    },
    sendTestNewsletter() {
      if (!this.id) {
        this.$panel.notification.error('No newsletter found');
        return;
      };

      this.sendingTest = true;

      this.$api.get(`${this.id}/send/1`)
        .then(response => {
          this.sendingTest = false;

          if (response.data.successfulDelivery === 0) {
            this.$panel.notification.error('Keine Nachrichten versendet. Wurde mindestens ein Empfänger ausgewählt?');
            return;
          }

          if (response.data.errorDelivery > 0) {
            this.$panel.notification.error('Beim Senden an einzelne Empfänger ist ein Problem aufgetreten.');
            return;
          }

          this.$panel.notification.success('Testmail gesendet');
        })
        .catch(error => {
          this.sendingTest = false;
          console.error(error.message);
          this.$panel.notification.error(error.message);
        })
    },
    sendNewsletter() {
      if (!this.id) {
        this.$panel.notification.error('No newsletter found');
        return;
      };

      this.$panel.dialog.isLoading = true;

      this.$api.get(`${this.id}/send`)
        .then(response => {
          this.$panel.dialog.isLoading = false;


          if (response.data.successfulDelivery === 0) {
            this.$panel.notification.error('Keine Nachrichten versendet. Wurde mindestens ein Empfänger ausgewählt?');
            return;
          }

          if (response.data.errorDelivery > 0) {
            this.$panel.notification.error('Beim Senden an einzelne Empfänger ist ein Problem aufgetreten.');
            return;
          }

          this.$panel.dialog.close();
          this.$panel.reload();
        })
        .catch(error => {
          this.$panel.dialog.isLoading = false;
          console.error(error.message);
          this.$panel.notification.error(error.message);
        })
    }
  }
};
</script>

<style>
/** Put your CSS here **/
.k-newsletter-action-section {
  margin-bottom: var(--spacing-8)
}
</style>
