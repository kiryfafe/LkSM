const API = {
  async makeRequest(url, options = {}) {
    try {
      const res = await fetch(url, options);
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return await res.json();
    } catch (e) {
      console.error("API error:", e);
      return { success: false, error: "Network error" };
    }
  },
  // ==================== АВТОРИЗАЦИЯ ====================
  async loginUser({ identifier, password }) {
    return this.makeRequest("/api/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ identifier, password })
    });
  },
  async registerUser(data) {
    return this.makeRequest("/api/register.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    });
  },
  // ==================== ЗАЯВКИ ====================
  async getRequests(userId) {
    return this.makeRequest(`/api/requests.php?userId=${userId}`, {
      headers: { "Authorization": `Bearer ${Auth.getToken()}` }
    });
  },
  async addRequest(data) {
    return this.makeRequest("/api/requests.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Authorization": `Bearer ${Auth.getToken()}`
      },
      body: JSON.stringify(data)
    });
  },
  // ==================== ЗАВЕДЕНИЯ ====================
  async getApprovedRestaurants(userId) {
    return this.makeRequest(`/api/restaurants.php?userId=${userId}`, {
      headers: { "Authorization": `Bearer ${Auth.getToken()}` }
    });
  },
  // ==================== ПРОФИЛЬ ====================
  async updateUserProfile(userData) {
    return this.makeRequest("/api/profile.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Authorization": `Bearer ${Auth.getToken()}`
      },
      body: JSON.stringify(userData)
    });
  }
};
window.API = API; // <-- Эта строка делает API глобальным