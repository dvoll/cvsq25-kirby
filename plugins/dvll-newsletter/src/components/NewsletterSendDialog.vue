<template>
  <k-dialog cancel-button="Nicht senden" :size="size" :submit-button="submitButton" :visible="true"
    @cancel="$emit('cancel')" @submit="$emit('submit')">
    <k-headline v-if="headline">
      {{ headline }}
    </k-headline>

    <k-stats style="margin-top: 1rem;" :reports=" [{ value: recipients.length, label: 'Empfänger' , icon: 'users' }]" />

    <k-text style="margin-top: 1rem;">Liste aller Empfänger:</k-text>
    <div class="k-table" style="margin-top: 1rem;">
      <table>
        <thead>
          <tr>
            <th data-mobile="true">E-Mail</th>
            <th data-mobile="true">Name</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="value in visibleRecipientsForPage" :key="value.email">
            <td data-mobile="true">{{ value.email }}</td>
            <td data-mobile="true">{{ value.firstname }} {{ value.name }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <k-pagination style="margin-top: .5rem;" :page="recipientPage" :total="total"
      @paginate="recipientPage = $event.page" :details="true" :limit="recipientPageLimit" />
    <!-- <k-box style="margin-top: 2rem;" theme="warning" text="Das Versenden kann mehrere Minuten dauern. Solange den Tab bitte nicht schließen." icon="box" /> -->
  </k-dialog>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    headline: {
      type: String,
      default: 'Newsletter versenden'
    },
    size: {
      type: String,
      default: "medium"
    },
    id: {
      type: String
    },
    recipients: {
      /** @type import('vue').PropType<{[key: string]: string}[]> */
      type: Array,
      default: () => []
    },
    canSubmit: {
      type: Boolean,
      default: false
    },
  },
  emits: ["cancel", "submit", "success"],
  data() {
    return {
      canSubmit: true,
      detailsOpen: true,
      recipientPage: 1,
      recipientPageLimit: 10,
    };
  },
  computed: {
    visibleRecipientsForPage() {
      const pageItems = this.recipients.slice((this.recipientPage - 1) * this.recipientPageLimit, this.recipientPage * this.recipientPageLimit);

      // Fill last page with empty rows
      if (this.recipientPage > 1 && pageItems.length < this.recipientPageLimit) {
        pageItems.push(...Array(this.recipientPageLimit - pageItems.length).fill({}));
      }

      return pageItems;
    },
    total() {
      return this.recipients.length;
    },
    isLoading() {
      return this.$panel.dialog.isLoading;
    },
    submitButton() {
      return {
        disabled: !this.canSubmit || this.$panel.dialog.isLoading,
        text: 'Versenden',
        icon: 'plane',
      };
    },
  },
  methods: {
  }
};
</script>

<style>

</style>
