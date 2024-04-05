<template>
  <div style="margin-block: 2rem;">
    <k-bar>
      <k-button-group>
        <k-button @click="resendWithError" variant="filled" :disabled="!canSendAll"
          :icon="isSendingAll ? 'loader' : 'refresh'">
          Fehlgeschlagene E-Mails erneut senden
        </k-button>
      </k-button-group>
    </k-bar>
    <k-stats style="margin-top: 1rem;" :reports="[
      {
        value: successReportsLength || '0',
        label: 'Erfolgreich',
        icon: 'check',
        theme: 'positive',
        info: errorReportsLength <= 0 && 'Alle E-Mails wurden erfolgreich zugestellt.',
      },
      {
        value: errorReportsLength || '0',
        label: 'Fehlgeschlagen',
        icon: 'cancel',
        theme: 'negative',
        info: errorReportsLength > 0 && 'Es gab Fehler beim Versenden der E-Mails.',
      },
    ]" />

    <div class="k-table" style="margin-top: 1rem;">
      <table>
        <thead>
          <tr>
            <th class="k-table-index-column">#</th>
            <th data-mobile="true">E-Mail</th>
            <th>Name</th>
            <th data-mobile="true" class="k-dvll-nwsl-result-section-status-col"><k-icon type="alert" /></th>
            <th>Info</th>
            <th data-mobile="true">Aktion</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in reports" :key="item.email">
            <td class="k-table-index-column">
              <span class="k-table-index">{{ index }}</span>
            </td>
            <td data-mobile="true">{{ item.email }}</td>
            <td>{{ item.name }}</td>
            <td data-mobile="true" class="k-dvll-nwsl-result-section-status-col">
              <k-icon v-if="item.status === 'error'" type="alert" />
            </td>
            <td :class="{ 'k-dvll-nwsl-result-section-error-text': item.status === 'error' }">{{ item.info }}</td>
            <td data-mobile="true">
              <k-button v-if="item.status === 'error'" icon="refresh"
                :aria-label="`E-Mail erneut an ${item.email} senden`" :title="`E-Mail erneut an '${item.email}' senden`"
                @click="resendSingle(item.email)">Erneut senden</k-button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      id: undefined,
      /** @type {{[key: string]: string}[]} */
      reports: [],
    };
  },
  computed: {
    canSendAll() {
      return this.reports.filter( r => r.status === 'error').length > 0;
    },
    errorReportsLength() {
      return this.reports.filter(report => ['error', 'sending'].includes(report.status)).length;
    },
    successReportsLength() {
      return this.reports.filter(report => report.status === 'sent').length;
    },
  },
  created() {
    this.load().then(response => {
      this.reports = response.reports;
      this.id = response.id;
    });
  },
  methods: {
    resendSingle(email) {
      this.reports = this.reports.map(report => {
        if (report.email === email) {
          return {
            ...report,
            status: 'sending',
            statusicon: '📤',
            info: 'Wird gesendet...',
          }
        }
        return report;
      });
      return this.$api.post(`${this.id}/send-single`, { email: email })
        .then(response => {
          this.$panel.notification.success(`E-Mail an '${email}'' wurde erfolgreich versendet.`);

          const {
            status,
            statusicon,
            info,
          } = response.data;

          this.reports = this.reports.map(report => {
            if (report.email === email) {
              return {
                ...report,
                status,
                statusicon,
                info,
              }
            }
            return report;
          });
        })
        .catch(error => {
          console.error(error);
          this.$panel.notification.error(error);

          this.reports = this.reports.map(report => {
            if (report.email === email) {
              return {
                ...report,
                status: 'error',
                statusicon: '❌',
                info: error.details[0]?.message ?? error.message,
              }
            }
            return report;
          });
        })
    },
    resendWithError() {
      this.reports = this.reports.map(report => {
        if (report.status === 'error') {
          return {
            ...report,
            status: 'sending',
            statusicon: '📤',
            info: 'Wird gesendet...',
          }
        }
        return report;
      });
      return this.$api.post(`${this.id}/send-with-errors`)
        .then(response => {
          this.$panel.notification.success(`E-Mails wurden erfolgreich versendet.`);

          // const {
          //   status,
          //   statusicon,
          //   info,
          // } = response.data;

          const newReports = response.data;

          this.reports = this.reports.map(report => {
            const newReport = newReports.find(r => r.email === report.email);
            if (newReport) {
              return {
                ...report,
                ...newReport,
              }
            }
            return report;
          });
        })
        .catch(error => {
          console.error(error);
          this.$panel.notification.error(error);

          this.reports = this.reports.map(report => {
            if (report.email === email) {
              return {
                ...report,
                status: 'error',
                statusicon: '❌',
                info: error.details[0]?.message ?? error.message,
              }
            }
            return report;
          });
        })
    },
  },
};
</script>

<style>
.k-table .k-dvll-nwsl-result-section-status-col {
  width: calc(1.2 * var(--table-row-height)) !important;
}

.k-dvll-nwsl-result-section-error-text {
  color: var(--color-negative);
  font-size: var(--text-xs);
}
</style>
