<template>
  <div style="display: none;"></div>
</template>

<script>
import apiClient from "@/plugins/axios.js";

export default {
  name: 'AuthStatusChecker',
  data() {
    return {
      message: 'Checking authorization status...',
      detailMessage: null,
      isLoading: true, // Loading spinner flag.
    };
  },
  async mounted() {
    // 1. Check URL parameters after redirection from ZohoAuthController.
    const params = new URLSearchParams(window.location.search);
    const authStatus = params.get('auth_status');
    const authMessage = params.get('message');

    if (authStatus && authMessage) {
      this.detailMessage = decodeURIComponent(authMessage);
      history.replaceState({}, document.title, window.location.pathname); // Clear URL parameters.

      if (authStatus === 'success') {
        this.message = 'Zoho Inventory authorization successful!';
        // If successful, redirect to the order form after a short delay.
        setTimeout(() => {
          this.$router.push({ name: 'SalesOrderForm' });
        }, 1500); // Short delay for user to see the message.
      } else {
        this.message = 'Zoho Inventory authorization failed.';
        // If there's an error, redirect to the authorization required page.
        setTimeout(() => {
          this.$router.push({ name: 'AuthRequired', query: { message: authMessage, status: authStatus } });
        }, 1500);
      }
      this.isLoading = false;
      return; // Exit as URL parameters have been processed.
    }

    // 2. If no URL parameters, check authorization status via API.
    try {
      const response = await apiClient.get('/zoho/auth-status'); // Changed to /zoho/auth-status

      if (response.data.authenticated) {
        this.message = 'You are already authorized in Zoho Inventory.';
        this.detailMessage = 'Redirecting to order form...';
        setTimeout(() => {
          this.$router.push({ name: 'SalesOrderForm' });
        }, 1000);
      } else {
        this.message = 'Zoho Inventory authorization required.';
        this.detailMessage = 'Redirecting to authorization page...';
        setTimeout(() => {
          this.$router.push({ name: 'AuthRequired' });
        }, 1000);
      }
    } catch (error) {
      console.error('Error checking Zoho authorization status:', error);
      this.message = 'Failed to check Zoho authorization status.';
      this.detailMessage = 'Please try authorizing again.';
      this.$router.push({ name: 'AuthRequired', query: { error: true, message: 'Failed to check authorization status.' } });
    } finally {
      this.isLoading = false;
    }
  },
};
</script>

<style scoped>
.auth-status-checker {
  text-align: center;
  margin-top: 50px;
}
.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: #2196F3; /* Zoho blue */
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 20px auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
