// Registers global Alpine helpers for Moderation report button and form
// Used by blade: app/Domains/Moderation/Private/Resources/views/components/report-button.blade.php
(function(){
  if (window.__moderationReportRegistered) return;
  window.__moderationReportRegistered = true;

  // x-data entry used by <x-moderation::report-button>
  window.reportButton = function(topicKey, entityId) {
    return {
      loading: false,
      async loadForm() {
        if (this.loading) return;
        this.loading = true;
        try {
          const response = await fetch(`/moderation/report-form/${topicKey}/${entityId}`);
          if (response.redirected) {
            window.location.href = response.url;
            return;
          }
          if (response.ok) {
            const html = await response.text();
            this.$refs.modalContainer.innerHTML = html;
            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
              window.Alpine.initTree(this.$refs.modalContainer);
            }
          } else {
            if (response.status === 401 || response.status === 403) {
              window.location.href = '/login';
              return;
            }
            alert('An error occurred while loading the report form.');
          }
        } catch (error) {
          alert('An error occurred while loading the report form.');
        } finally {
          this.loading = false;
        }
      },
    };
  }

  // x-data entry used inside the dynamically loaded modal HTML
  window.reportForm = function(topicKey, entityId) {
    return {
      submitting: false,
      submitted: false,
      form: {
        topic_key: topicKey,
        entity_id: entityId,
        reason_id: '',
        description: '',
      },
      errors: {},
      errorMessage: '',
      successMessage: '',

      openModal() {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'report-modal-' + topicKey + '-' + entityId }));
      },

      closeModal() {
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'report-modal-' + topicKey + '-' + entityId }));
      },

      async submitReport() {
        this.submitting = true;
        this.errors = {};
        this.errorMessage = '';
        try {
          const response = await fetch('/moderation/report', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              'Accept': 'application/json',
            },
            body: JSON.stringify(this.form),
          });

          if (response.redirected) {
            window.location.href = response.url;
            return;
          }
          if (response.status === 401 || response.status === 403) {
            window.location.href = '/login';
            return;
          }

          const data = await response.json();
          if (data.success) {
            this.submitted = true;
            this.successMessage = data.message || 'Report submitted.';
          } else {
            if (data.errors) {
              Object.keys(data.errors).forEach(k => this.errors[k] = data.errors[k][0] ?? '');
            }
            this.errorMessage = data.message || 'An error occurred.';
          }
        } catch (e) {
          this.errorMessage = 'An error occurred.';
        } finally {
          this.submitting = false;
        }
      },
    };
  }
})();
