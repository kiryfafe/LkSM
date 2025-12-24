const API = {
  async makeRequest(url, options = {}) {
    try {
      const res = await fetch(url, options);
      
      // Проверяем Content-Type перед парсингом JSON
      const contentType = res.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        const text = await res.text();
        console.error("Non-JSON response:", text);
        return { 
          success: false, 
          error: `Server error (${res.status}): ${text.substring(0, 100)}` 
        };
      }
      
      const text = await res.text();
      if (!text || text.trim() === "") {
        return { 
          success: false, 
          error: `Empty response from server (${res.status})` 
        };
      }
      
      let data;
      try {
        data = JSON.parse(text);
      } catch (parseError) {
        console.error("JSON parse error:", parseError, "Response text:", text);
        return { 
          success: false, 
          error: `Invalid JSON response: ${text.substring(0, 200)}` 
        };
      }
      
      if (!res.ok) {
        // Если сервер вернул JSON с ошибкой, используем его
        return { success: false, error: data.error || `HTTP error! status: ${res.status}` };
      }
      return data;
    } catch (e) {
      console.error("API error:", e);
      return { success: false, error: e.message || "Network error" };
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
  // ==================== PYRUS ====================
  async getPyrusRestaurants() {
    return this.makeRequest("/api/restaurants.php", {
      headers: { "Authorization": `Bearer ${Auth.getToken()}` }
    });
  },
  async getPyrusTasks(restaurant = "") {
    const qs = restaurant ? `?restaurant=${encodeURIComponent(restaurant)}` : "";
    return this.makeRequest(`/api/pyrus_tasks.php${qs}`, {
      headers: { "Authorization": `Bearer ${Auth.getToken()}` }
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