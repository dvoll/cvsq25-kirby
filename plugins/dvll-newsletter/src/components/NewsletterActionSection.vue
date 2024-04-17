<template>
  <section class="k-newsletter-action-section">
    <k-bar>
      <k-button-group>
        <k-button @click="onSendTest" variant="filled" :disabled="!canSendTest" :icon="sendingTest ? 'loader' : 'lab'">
          Testmail senden
        </k-button>
        <k-button @click="onSend" variant="filled" :disabled="!canSend" icon="plane">
          Newsletter versenden
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
      errorDetailsList: [],
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
      if (this.$store.getters['content/hasChanges']()) {
        this.$panel.notification.error('Änderungen müssen vor dem Senden gespeichert oder verworfen werden.');
        return;
      }

      this.$api.get(`${this.id}/check-send`)
        .then(response => {
          this.openDialog(response.data);
        })
        .catch(error => {
          this.$panel.notification.error(error);
      });
      return;
    },
    openDialog(recipients = []) {
      this.$panel.dialog.open({
        component: 'k-newsletter-send-dialog',
        props: {
          id: this.id,
          errorDetailsList: this.errorDetailsList,
          recipients: recipients.map(r => ({
            email: r.email,
            firstname: r.firstname,
            name: r.name,
          })),
          canSubmit: recipients.length > 0,
          text: 'Den Newsletter an wirklich an alle Abonnenten senden?',
        },
        on: {
          submit: () => {
            this.sendNewsletter();
          },
        }
      });
    },
    onSendTest() {
      if (this.$store.getters['content/hasChanges']()) {
        this.$panel.notification.error('Änderungen müssen vor dem Senden gespeichert oder verworfen werden.');
        return;
      }

      this.sendTestNewsletter();
    },
    sendTestNewsletter() {
      if (!this.id) {
        this.$panel.notification.error('Kein Newsletter zum versenden gefunden.');
        return;
      };

      this.sendingTest = true;

      this.$api.post(`${this.id}/send/1`)
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
          console.error(error);
          this.$panel.notification.error(error);
        })
    },
    sendNewsletter() {
      if (!this.id) {
        this.$panel.notification.error('No newsletter found');
        return;
      };

      this.$panel.dialog.isLoading = true;

      this.$api.post(`${this.id}/send`)
        .then(response => {
          this.$panel.dialog.isLoading = false;


          if (response.data.successfulDelivery === 0) {
            this.$panel.notification.error('Keine Nachrichten versendet. Wurde mindestens ein Empfänger ausgewählt?');
            return;
          }

          this.$panel.dialog.close();
          this.$panel.reload();
        })
        .catch(error => {
          this.$panel.dialog.isLoading = false;
          console.error(error);
          this.$panel.notification.error(error);
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
